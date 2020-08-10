<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\jobs;

use craft\helpers\DateTimeHelper;
use enupal\stripe\enums\PaymentType;
use enupal\stripe\records\Customer as CustomerRecord;
use enupal\stripe\Stripe as StripePlugin;
use craft\queue\BaseJob;
use enupal\stripe\elements\Order;

use Stripe\Charge;
use Stripe\Customer;
use yii\queue\RetryableJobInterface;
use Craft;

/**
 * SyncOneTimePayments job
 */
class SyncOneTimePayments extends BaseJob implements RetryableJobInterface
{
    public $totalSteps = 500;

    public $defaultPaymentFormId;

    public $defaultStatusId;

    public $syncIfUserExists = false;

    public $enableDateRange;

    public $startDate;

    public $endDate;

    /**
     * Returns the default description for this job.
     *
     * @return string
     */
    protected function defaultDescription(): string
    {
        return StripePlugin::t('Syncing One-Time Orders');
    }

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        $result = false;
        StripePlugin::$app->settings->initializeStripe();

        $params = [];
        if ($this->enableDateRange){
            $startDate = strtotime($this->startDate);
            $endDate = strtotime($this->endDate);

            $params = [
                'created' => [
                    'gte' => $startDate,
                    'lte' => $endDate
                ]
            ];
        }

        $charges = Charge::all($params);
        $step = 0;
        $failed = 0;

        foreach ($charges->autoPagingIterator() as $charge) {
            $order = StripePlugin::$app->orders->getOrderByStripeId($charge['id']);
            try {
                if ($order !== null) {
                    continue;
                }
                // Check if customer exists
                $customerRecord = CustomerRecord::findOne([
                    'stripeId' => $charge['customer']
                ]);
                $email = null;
                $userId = null;

                if ($customerRecord) {
                    $email = $customerRecord->email;
                } else {
                    $stripeCustomer = Customer::retrieve($charge['customer']);
                    $email = $stripeCustomer['email'];

                    StripePlugin::$app->customers->createCustomer($email, $charge['customer'], !$charge['livemode']);
                }

                if ($email) {
                    $user = Craft::$app->getUsers()->getUserByUsernameOrEmail($email);
                    if ($user) {
                        $userId = $user->id;
                    } else {
                        if ($this->syncIfUserExists) {
                            continue;
                        }
                    }
                }

                $paymentTypes = StripePlugin::$app->paymentForms->getPaymentTypesIds();
                $paymentType = $charge['source']['type'] ?? $charge['source']['object'] ?? null;

                $newOrder = new Order();
                $newOrder->formId = $this->defaultPaymentFormId;
                $newOrder->userId = $userId;
                $newOrder->testMode = !$charge['livemode'];
                $newOrder->paymentType = $paymentTypes[$paymentType] ?? PaymentType::CC;
                $newOrder->number = StripePlugin::$app->orders->getRandomStr();
                $newOrder->currency = strtoupper($charge['currency']);
                $newOrder->totalPrice = StripePlugin::$app->orders->convertFromCents($charge['amount'], $newOrder->currency);
                $newOrder->quantity = 1;
                $newOrder->isSubscription = false;
                $newOrder->dateOrdered = DateTimeHelper::toDateTime($charge['created'])->format('Y-m-d H:i:s');
                $newOrder->dateCreated = $newOrder->dateOrdered;
                $newOrder->orderStatusId = $this->defaultStatusId;
                $newOrder->stripeTransactionId = $charge['id'];
                $newOrder->email = $email;
                $newOrder->isCompleted = $charge['status'] == 'succeeded' ? true : false;

                if (isset($charge['shipping'])) {
                    $addressId = StripePlugin::$app->addresses->getAddressIdFromCharge($charge['shipping']);
                    if ($addressId) {
                        $newOrder->shippingAddressId = $addressId;
                    }
                }
                if (isset($charge['billing'])) {
                    $addressId = StripePlugin::$app->addresses->getAddressIdFromCharge($charge['billing']);
                    if ($addressId) {
                        $newOrder->billingAddressId = $addressId;
                    }
                }
                // new address format
                if (isset($charge['shipping']) && !$newOrder->shippingAddressId) {
                    $addressId = StripePlugin::$app->addresses->getNewAddressIdFromCharge($charge['shipping']);
                    if ($addressId) {
                        $newOrder->shippingAddressId = $addressId;
                    }
                }
                if (isset($charge['billing_details']) && !$newOrder->billingAddressId) {
                    $addressId = StripePlugin::$app->addresses->getNewAddressIdFromCharge($charge['billing_details']);
                    if ($addressId) {
                        $newOrder->billingAddressId = $addressId;
                    }
                }

                $newOrder->refunded = $charge['refunded'];

                $result = StripePlugin::$app->orders->saveOrder($newOrder, false);

                if ($result) {
                    StripePlugin::$app->messages->addMessage($newOrder->id, "Order Synced", $charge);
                } else {
                    $failed++;
                    Craft::error('Unable to sync Order: ' . $charge['id'], __METHOD__);
                }
            }catch (\Exception $e) {
                $failed++;
                Craft::error('Unable to sync Order: ' . $e->getMessage(), __METHOD__);
            }

            $step++;

            $this->setProgress($queue, $step / $this->totalSteps);

            if ($step >= $this->totalSteps) {
                break;
            }
        }

        Craft::info('Sync process finished, Total: '.$step. ', Failed: '.$failed, __METHOD__);
        $result = true;

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getTtr()
    {
        return 3600;
    }

    /**
     * @inheritdoc
     */
    public function canRetry($attempt, $error)
    {
        return ($attempt < 5) && ($error instanceof \Exception);
    }
}