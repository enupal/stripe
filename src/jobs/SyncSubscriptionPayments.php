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
use Stripe\Invoice;
use yii\queue\RetryableJobInterface;
use Craft;

/**
 * SyncSubscriptionPayments job
 */
class SyncSubscriptionPayments extends BaseJob implements RetryableJobInterface
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
        return StripePlugin::t('Syncing Subscription Orders');
    }

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        $result = false;
        StripePlugin::$app->settings->initializeStripe();

        try{
            $params = [];

            if ($this->enableDateRange && $this->startDate && $this->endDate){
                $startDate = strtotime($this->startDate);
                $endDate = strtotime($this->endDate);

                $params = [
                    'date' => [
                        'gte' => $startDate,
                        'lte' => $endDate
                    ]
                ];
            }

            $invoices = Invoice::all($params);
            $step = 0;
            $failed = 0;
            $alreadyExists = 0;
            $skipped = 0;

            foreach ($invoices->autoPagingIterator() as $invoice) {
                $testMode = !$invoice['livemode'];
                foreach ($invoice['lines']['data'] as $subscription) {
                    $subscriptionId = $subscription['subscription'] ?? $subscription['id'];
                    if (isset($subscription['plan']) && $subscription['plan'] && $subscriptionId){
                        $order = StripePlugin::$app->orders->getOrderByStripeId($subscriptionId);
                        if ($order === null) {
                            // Check if customer exists
                            $customerRecord = CustomerRecord::findOne([
                                'stripeId' => $invoice['customer']
                            ]);
                            $email = null;
                            $userId = null;

                            if ($customerRecord) {
                                $email = $customerRecord->email;
                            } else {
                                $stripeCustomer = Customer::retrieve($invoice['customer']);
                                $email = $stripeCustomer['email'];
                                StripePlugin::$app->customers->createCustomer($email, $invoice['customer'], $testMode);
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
                            $charge = null;

                            if ($invoice['charge']){
                                $charge = Charge::retrieve($invoice['charge']);
                            }
                            // @todo - Add sepa debit payment let's default to sofort
                            $paymentType = isset($charge['source']['type']) ? 'sofort'  : $charge['source']['object'] ?? null;

                            $newOrder = new Order();
                            $newOrder->isSubscription = true;
                            $newOrder->formId = $this->defaultPaymentFormId;
                            $newOrder->userId = $userId;
                            $newOrder->testMode = $testMode;
                            $newOrder->paymentType = $paymentTypes[$paymentType] ?? PaymentType::CC;
                            $newOrder->number = StripePlugin::$app->orders->getRandomStr();
                            $newOrder->currency = strtoupper($invoice['currency']);
                            $newOrder->totalPrice = StripePlugin::$app->orders->convertFromCents($invoice['amount_paid'], $newOrder->currency);
                            $newOrder->quantity = $subscription['quantity'];
                            $newOrder->dateOrdered = DateTimeHelper::toDateTime($invoice['created'])->format('Y-m-d H:i:s');
                            $newOrder->dateCreated = $newOrder->dateOrdered;
                            $newOrder->orderStatusId = $this->defaultStatusId;
                            $newOrder->stripeTransactionId = $subscriptionId;
                            $newOrder->email = $email;
                            if (isset($charge['status'])){
                                $newOrder->isCompleted = $charge['status'] == 'succeeded' ? true : false;
                            }else{
                                $newOrder->isCompleted = false;
                            }
                            if ($charge) {
                                if (isset($charge['shipping'])){
                                    $addressId = StripePlugin::$app->addresses->getAddressIdFromCharge($charge['shipping']);
                                    if ($addressId){
                                        $newOrder->shippingAddressId = $addressId;
                                    }
                                }

                                if (isset($charge['billing'])){
                                    $addressId = StripePlugin::$app->addresses->getAddressIdFromCharge($charge['billing']);
                                    if ($addressId){
                                        $newOrder->billingAddressId = $addressId;
                                    }
                                }

                                // new address format
                                if (isset($charge['shipping']) && !$newOrder->shippingAddressId){
                                    $addressId = StripePlugin::$app->addresses->getNewAddressIdFromCharge($charge['shipping']);
                                    if ($addressId){
                                        $newOrder->shippingAddressId = $addressId;
                                    }
                                }
                                if (isset($charge['billing_details']) && !$newOrder->billingAddressId){
                                    $addressId = StripePlugin::$app->addresses->getNewAddressIdFromCharge($charge['billing_details']);
                                    if ($addressId){
                                        $newOrder->billingAddressId = $addressId;
                                    }
                                }

                                $newOrder->refunded = $charge['refunded'];
                            }

                            $result = StripePlugin::$app->orders->saveOrder($newOrder, false);

                            if ($result) {
                                StripePlugin::$app->messages->addMessage($newOrder->id, "Order Synced - Invoice", $invoice);
                                StripePlugin::$app->messages->addMessage($newOrder->id, "Order Synced - Charge", $charge);
                            } else {
                                $failed++;
                                Craft::error('Unable to sync Order: ' . $subscriptionId, __METHOD__);
                            }

                            $step++;

                            $this->setProgress($queue, $step / $this->totalSteps);

                            if ($step >= $this->totalSteps) {
                                break 2;
                            }
                        }else{
                            $alreadyExists++;
                        }
                    }else{
                        $skipped++;
                    }
                }
            }

            Craft::info('Sync process finished, Total: '.$step. ', Failed: '.$failed. ', Already exists: '.$alreadyExists. ', Skipped: '.$skipped, __METHOD__);
            $result = true;

        }catch (\Exception $e) {
            Craft::error('Sync process failed: '.$e->getMessage(), __METHOD__);
        }

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