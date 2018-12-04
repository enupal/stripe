<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\jobs;

use craft\helpers\Db;
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

    /**
     * Returns the default description for this job.
     *
     * @return string
     */
    protected function defaultDescription(): string
    {
        return StripePlugin::t('Syncing One Time Payments');
    }

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {

        StripePlugin::$app->settings->initializeStripe();

        $charges = Charge::all();

        $step = 1;
        $failed = 1;

        foreach ($charges->autoPagingIterator() as $charge) {
            $order = StripePlugin::$app->orders->getOrderByStripeId($charge['id']);

            if ($order === null){
                // Check if customer exists
                $customerRecord = CustomerRecord::findOne([
                    'stripeId' => $charge['customer']
                ]);
                $email = null;
                $userId = null;

                if ($customerRecord){
                    $email = $customerRecord->email;
                }else{
                    $stripeCustomer = Customer::retrieve($charge['customer']);
                    $email = $stripeCustomer['email'];

                    $customerRecord = new CustomerRecord();
                    $customerRecord->email = $email;
                    $customerRecord->stripeId =  $charge['customer'];
                    $customerRecord->testMode = !$charge['livemode'];
                    $customerRecord->save(false);
                }

                if ($email){
                    $user = Craft::$app->getUsers()->getUserByUsernameOrEmail($email);
                    if ($user){
                        $userId = $user->id;
                    }
                }

                $paymentTypes = StripePlugin::$app->paymentForms->getPaymentTypesIds();
                $paymentType = $charge['source']['object'] ?? null;

                $newOrder = new Order();
                $newOrder->formId = $this->defaultPaymentFormId;
                $newOrder->userId = $userId;
                $newOrder->testMode = !$charge['livemode'];
                $newOrder->paymentType = $paymentTypes[$paymentType] ?? PaymentType::CC;
                $newOrder->number = StripePlugin::$app->orders->getRandomStr();
                $newOrder->currency = $charge['currency'];
                $newOrder->totalPrice = StripePlugin::$app->orders->convertFromCents($charge['currency'],  $charge['currency']);
                $newOrder->quantity = 1;
                $newOrder->dateOrdered = Db::prepareDateForDb(new \DateTime($charge['created']));
                $newOrder->orderStatusId = $this->defaultStatusId;
                $newOrder->stripeTransactionId = $charge['id'];
                $newOrder->email = $email;
                $newOrder->isCompleted = $charge['status'] == 'succeeded' ? true : false;
                $newOrder->firstName = $charge['shipping']['name'] ?? null;
                $newOrder->addressCity = $charge['shipping']['address_city'] ?? null;
                $newOrder->addressCountry = $charge['shipping']['address_country'] ?? null;
                $newOrder->addressState = $charge['shipping']['address_state'] ?? null;
                $newOrder->addressStreet = $charge['shipping']['address_line1'] ?? null;
                $newOrder->addressZip = $charge['shipping']['address_zip'] ?? null;
                $newOrder->refunded = $charge['refunded'];

                $result = StripePlugin::$app->orders->saveOrder($order, false);

                if ($result){
                    StripePlugin::$app->messages->addMessage($order->id, "Synced Order", $charge);
                }else{
                    $failed++;
                    Craft::error('Unable to sync Order: '.$charge['id'], __METHOD__);
                }

                $step++;
                $this->setProgress($queue, $step / $this->totalSteps);

                if ($step > $this->totalSteps){
                    break;
                }
            }
        }

        Craft::info('Sync process finished, Total: '.$step. ', Failed: '.$failed, __METHOD__);

        return true;
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