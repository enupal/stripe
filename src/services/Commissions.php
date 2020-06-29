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
use enupal\stripe\elements\Connect;
use enupal\stripe\elements\Order as StripePaymentsOrder;
use enupal\stripe\elements\PaymentForm;
use enupal\stripe\events\CommissionPaidEvent;
use enupal\stripe\Stripe;
use yii\base\Component;
use Craft;

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
                throw new \Exception(StripePlugin::t('No Commission exists with the ID “{id}”', ['id' => $commission->id]));
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
     * Process transfer for when Separate Charges are enabled
     * @param StripePaymentsOrder $order
     */
    public function processStripePaymentsOrder(StripePaymentsOrder $order)
    {
        $paymentForm = $order->getPaymentForm();
        $connects = Stripe::$app->connects->getConnectByPaymentFormId($paymentForm->id);

        /** @var Connect $connect */
        foreach ($connects as $connect) {
            if (!$order->isCompleted) {
                continue;
            }

            $commission = new Commission();
            $commission->totalPrice = $order->totalPrice * ($connect->rate / 100);
            $commission->connectId = $connect->id;
            $commission->orderId = $order->id;
            $commission->currency = strtoupper($order->currency);
            $commission->commissionStatus = self::STATUS_PENDING;
            $commission->orderType = StripePaymentsOrder::class;

            $result = $this->processCommission($commission);
            // @todo add message about commission
        }
    }

    /**
     * @param Commission $commission
     * @param bool $totalIsInCents
     * @return bool
     */
    public function processCommission(Commission $commission, $totalIsInCents = false)
    {
        $connect = $commission->getConnect();
        $vendor = $connect->getVendor();

        if (is_null($vendor)) {
            Craft::error('Unable to process commission as vendor does not exists');
            return false;
        }

        if (empty($vendor->stripeId)) {
            Craft::error('Unable to process commission as vendor does not have a Stripe account linked');
            return false;
        }

        $amountInCents = $commission->totalPrice;

        if (!$totalIsInCents) {
            $amountInCents = Stripe::$app->orders->convertToCents($commission->totalPrice, $commission->currency);
        }

        //@todo add transfer

        return true;
    }


    /**
     * This will check commissions for Direct Charges nad Destination Charges
     * @param StripePaymentsOrder $order
     * @return bool
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     */
    public function checkForCommissions(StripePaymentsOrder $order)
    {
        if (!$order->isCompleted) {
            return false;
        }

        if ($order->isSubscription) {
            // @todo add support for subscriptions
            return false;
        }

        $paymentForm = $order->getPaymentForm();
        $chargeId = Stripe::$app->orders->getChargeIdFromOrder($order);
        $charge = Stripe::$app->orders->getCharge($chargeId);
        $stripeAccountId = $charge->destination;

        if ($stripeAccountId === null) {
            Craft::error('Unable to get the destination from Charge', __METHOD__);
            return false;
        }

        $vendor = Stripe::$app->vendors->getVendorByStripeId($stripeAccountId);

        if ($vendor === null) {
            Craft::error('Unable to find vendor with account id: '.$stripeAccountId, __METHOD__);
            return false;
        }

        $connect = Stripe::$app->connects->getConnectByPaymentFormId($paymentForm->id, $vendor->id);

        if ($connect === null) {
            Craft::error('Unable to find the connect associated to vendor: '.$vendor->id, __METHOD__);
            return false;
        }

        $commission = new Commission();
        $commission->stripeId = $charge->id;
        $commission->orderId = $order->id;
        $commission->orderType = StripePaymentsOrder::class;
        $commission->connectId = $connect->id;
        $commission->totalPrice = Stripe::$app->orders->convertFromCents($charge->amount - $charge->application_fee_amount, $order->currency);
        $commission->currency = $order->currency;
        $now = Db::prepareDateForDb(new \DateTime());
        $commission->datePaid = $now;
        $commission->commissionStatus = self::STATUS_PAID;

        if (!$this->saveCommission($commission)) {
            Craft::error('Unable to save commission: '.json_encode($commission->getErrors()), __METHOD__);
            return false;
        }

        return true;
    }
}
