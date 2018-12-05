<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\jobs;

use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
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
            $invoices = Invoice::all();

            $step = 0;
            $failed = 0;

            foreach ($invoices->autoPagingIterator() as $invoice) {
                $testMode = !$invoice['livemode'];
                foreach ($invoice['lines']['data'] as $subscription) {
                    $subscriptionId = $subscription['subscription'] ?? null;
                    if (isset($subscription['plan']) && $subscription['plan'] && $subscriptionId){
                        $plan = $subscription['plan'];
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

                                $customerRecord = new CustomerRecord();
                                $customerRecord->email = $email;
                                $customerRecord->stripeId = $invoice['customer'];
                                $customerRecord->testMode = $testMode;
                                $customerRecord->save(false);
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
                            $charge = Charge::retrieve($invoice['charge']);
                            // @todo - Add sepa debit payment let's default to sofort
                            $paymentType = isset($charge['source']['type']) ? 'sofort'  : $invoice['source']['object'] ?? null;

                            $newOrder = new Order();
                            $newOrder->formId = $this->defaultPaymentFormId;
                            $newOrder->userId = $userId;
                            $newOrder->testMode = $testMode;
                            $newOrder->paymentType = $paymentTypes[$paymentType] ?? PaymentType::CC;
                            $newOrder->number = StripePlugin::$app->orders->getRandomStr();
                            $newOrder->currency = strtoupper($invoice['currency']);
                            $newOrder->totalPrice = StripePlugin::$app->orders->convertFromCents($invoice['amount_paid'], $newOrder->currency);
                            $newOrder->quantity = $subscription['quantity'];
                            $newOrder->dateOrdered = Db::prepareDateForDb(DateTimeHelper::toDateTime($invoice['created'])->getTimestamp());
                            $newOrder->dateCreated = $newOrder->dateOrdered;
                            $newOrder->orderStatusId = $this->defaultStatusId;
                            $newOrder->stripeTransactionId = $invoice['id'];
                            $newOrder->email = $email;
                            $newOrder->isCompleted = $invoice['status'] == 'succeeded' ? true : false;
                            $newOrder->firstName = $invoice['shipping']['name'] ?? null;
                            $newOrder->addressCity = $invoice['shipping']['address_city'] ?? null;
                            $newOrder->addressCountry = $invoice['shipping']['address_country'] ?? null;
                            $newOrder->addressState = $invoice['shipping']['address_state'] ?? null;
                            $newOrder->addressStreet = $invoice['shipping']['address_line1'] ?? null;
                            $newOrder->addressZip = $invoice['shipping']['address_zip'] ?? null;
                            $newOrder->refunded = $invoice['refunded'];

                            $result = StripePlugin::$app->orders->saveOrder($newOrder, false);

                            if ($result) {
                                StripePlugin::$app->messages->addMessage($newOrder->id, "Order Synced", $invoice);
                            } else {
                                $failed++;
                                Craft::error('Unable to sync Order: ' . $invoice['id'], __METHOD__);
                            }

                            $step++;

                            $this->setProgress($queue, $step / $this->totalSteps);

                            if ($step >= $this->totalSteps) {
                                break;
                            }
                        }
                    }
                }
            }

            Craft::info('Sync process finished, Total: '.$step. ', Failed: '.$failed, __METHOD__);
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