<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\jobs;

use enupal\stripe\Stripe as StripePlugin;
use craft\queue\BaseJob;
use enupal\stripe\elements\Order;
use yii\queue\RetryableJobInterface;
use Craft;

/**
 * UpdateEmailAddressOnOrders job
 */
class UpdateEmailAddressOnOrders extends BaseJob implements RetryableJobInterface
{
    public $orders;

    public $newEmail;

    /**
     * Returns the default description for this job.
     *
     * @return string
     */
    protected function defaultDescription(): string
    {
        return StripePlugin::t('Updating order emails');
    }

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        $result = false;
        $totalSteps = count($this->orders);

        $step = 0;
        /** @var Order $order */
        foreach ($this->orders as $order) {
            $oldEmail = $order->email;
            $order->email = $this->newEmail;

            $result = StripePlugin::$app->orders->saveOrder($order, false);

            if ($result){
                StripePlugin::$app->messages->addMessage($order->id, "Email updated after Craft user email updated, from: ".$oldEmail." -> ".$this->newEmail, []);
            }else{
                Craft::error('Unable to update email in Order: '.$order->id, __METHOD__);
            }

            $step++;

            $this->setProgress($queue, $step / $totalSteps);
        }

        Craft::info('Updated email addresses for orders, total: '.$totalSteps, __METHOD__);
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