<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use craft\base\ElementInterface;
use craft\helpers\Db;
use enupal\stripe\elements\Commission;
use enupal\stripe\elements\Order as StripePaymentsOrder;
use enupal\stripe\elements\Vendor;
use enupal\stripe\events\CommissionPaidEvent;
use enupal\stripe\Stripe;
use Imagine\Filter\Basic\Strip;
use yii\base\Component;
use Craft;
use Stripe\Transfer;

class Commissions extends Component
{
    const STATUS_PAID = 'paid';
    const STATUS_PENDING = 'pending';
    const EVENT_COMMISSION_PAID = 'afterCommissionPaid';

    /**
     * Returns a Commission model if one is found in the database by id
     *
     * @param int $id
     *
     * @return null|ElementInterface
     */
    public function getCommissionById(int $id)
    {
        $commission = Craft::$app->getElements()->getElementById($id);

        return $commission;
    }

    /**
     * Returns a Commission model if one is found in the database by stripe id
     *
     * @param $stripeId
     *
     * @return null|ElementInterface
     */
    public function getCommissionByStripeId($stripeId)
    {
        $query = Commission::find();
        $query->stripeId = $stripeId;

        return $query->one();
    }

    /**
     * @param $commission Commission
     * @param $triggerEvent boolean
     *
     * @throws \Exception
     * @return bool
     * @throws \Throwable
     */
    public function saveCommission(Commission $commission, $triggerEvent = true)
    {
        if ($commission->id) {
            $commissionRecord = $this->getCommissionById($commission->id);

            if (is_null($commissionRecord)) {
                throw new \Exception(Craft::t('enupal-stripe','No Commission exists with the ID “{id}”', ['id' => $commission->id]));
            }
        }

        if (!$commission->validate()) {
            return false;
        }

        try {
            $transaction = Craft::$app->db->beginTransaction();
            $result = Craft::$app->elements->saveElement($commission);

            if ($result) {
                $transaction->commit();

                if ($commission->commissionStatus === self::STATUS_PAID && !Craft::$app->getRequest()->getIsCpRequest() && $triggerEvent){
                    $event = new CommissionPaidEvent([
                        'commission' => $commission
                    ]);

                    $this->trigger(self::EVENT_COMMISSION_PAID, $event);
                }
                if ($commission->commissionStatus === self::STATUS_PAID){
                    Stripe::$app->emails->sendVendorNotificationEmail($commission);
                }
            }else{
                $transaction->rollback();
                return false;
            }
        } catch (\Exception $e) {
            $transaction->rollback();

            throw $e;
        }

        return true;
    }

    /**
     * @param Commission $commission
     * @return bool
     * @throws \Throwable
     */
    public function processTransfer(Commission $commission)
    {
        $connect = $commission->getConnect();
        $vendor = $connect->getVendor();
        Stripe::$app->settings->initializeStripe();

        if (is_null($vendor)) {
            Craft::error('Unable to process commission as vendor does not exists', __METHOD__);
            return false;
        }

        if (empty($vendor->stripeId)) {
            Craft::error('Unable to process commission as vendor does not have a Stripe account linked', __METHOD__);
            return false;
        }

        if ($commission->commissionStatus === self::STATUS_PAID) {
            Craft::error('Transfer was already processed', __METHOD__);
            return false;
        }

        $amountInCents = Stripe::$app->orders->convertToCents($commission->totalPrice, $commission->currency);

        try {
            $transfer = Transfer::create([
                'amount' => $amountInCents,
                'currency' => strtolower($commission->currency),
                'destination' => $vendor->stripeId
            ]);
        } catch (\Exception $e){
            Craft::error('Unable to process transfer: '.$e->getMessage(), __METHOD__);
            return false;
        }

        if ($transfer->id) {
            $commission->commissionStatus = Commissions::STATUS_PAID;
            $commission->stripeId = $transfer->id;
            $commission->datePaid = Db::prepareDateForDb(new \DateTime());
            $this->saveCommission($commission);
        }

        return true;
    }

    /**
     * After Order is completed we will process transfers if the setting is set to on checkout
     * @param StripePaymentsOrder $order
     * @return bool
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     */
    public function processSeparateCharges(StripePaymentsOrder $order)
    {
        $settings = Stripe::$app->settings->getSettings();
        if (!$settings->enableConnect) {
            return false;
        }

        if (!$order->isCompleted) {
            return false;
        }

        if ($order->isSubscription) {
            // @todo add support for subscriptions
            return false;
        }

        $paymentForm = $order->getPaymentForm();

        $connects = Stripe::$app->connects->getConnectsByPaymentFormId($paymentForm->id);
        $connectsWithAllProducts = Stripe::$app->connects->getConnectsByPaymentFormId($paymentForm->id, null, true);
        $connects = array_merge($connects, $connectsWithAllProducts);

        if (empty($connects)) {
            // No connects for this payment form
            return false;
        }

        $chargeId = Stripe::$app->orders->getChargeIdFromOrder($order);
        $charge = Stripe::$app->orders->getCharge($chargeId);

        if (!isset($charge['id'])) {
            Craft::error('Unable to process connect transfer as the Charge id does not exists', __METHOD__);
            return false;
        }

        foreach ($connects as $connect) {
            if (!$connect->enabled) {
                Craft::error("Unable to process commission as connect it's disabled", __METHOD__);
                continue;
            }

            /** @var Vendor $vendor */
            $vendor = $connect->getVendor();

            if (is_null($vendor) || !$vendor->enabled) {
                Craft::error("Unable to process commission as vendor does not exists or it's disabled", __METHOD__);
                continue;
            }

            if (empty($vendor->stripeId)) {
                Craft::error('Unable to process commission as vendor does not have a Stripe account linked', __METHOD__);
                continue;
            }

            $vendorAmount = $order->totalPrice * ($connect->rate / 100);
            $commission   = $this->createPendingCommission($order, $connect, $vendorAmount, $order->currency, $paymentForm->id);

            if ($commission === null) {
                return false;
            }

            if ($vendor->paymentType === Vendors::PAYMENT_TYPE_ON_CHECKOUT) {
                $this->processTransfer($commission);
            }
        }

        return true;
    }

    /**
     * @param $order
     * @param $connect
     * @param $totalPrice
     * @param $currency
     * @param $productId
     * @return Commission|null
     * @throws \Throwable
     */
    public function createPendingCommission($order, $connect, $totalPrice, $currency, $productId)
    {
        $settings = Stripe::$app->settings->getSettings();
        $commission = new Commission();
        $commission->orderId = $order->id;
        $commission->orderType = get_class($order);
        $commission->productId = $productId;
        $commission->number = Stripe::$app->orders->getRandomStr();
        $commission->connectId = $connect->id;
        $commission->currency = $currency;
        $commission->totalPrice = $totalPrice;
        $commission->commissionStatus = self::STATUS_PENDING;
        $commission->testMode = $settings->testMode;

        if (!$this->saveCommission($commission)) {
            Craft::error('Unable to create pending commission: '.json_encode($commission->getErrors()), __METHOD__);
            return null;
        }

        return $commission;
    }
}
