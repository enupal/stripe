<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use Craft;
use Stripe\Subscription;
use enupal\stripe\models\Subscription as SubscriptionModel;
use yii\base\Component;
use enupal\stripe\Stripe as StripePlugin;

class Subscriptions extends Component
{
    /**
     * @param $id
     * @return null|SubscriptionModel
     */
    public function getStripeSubscription($id)
    {
        $subscriptionModel = null;

        try {
            StripePlugin::$app->settings->initializeStripe();

            $subscription = Subscription::retrieve($id);

            $subscriptionModel = new SubscriptionModel($subscription);

        } catch (\Exception $e) {
            Craft::error('Unable to get subscription: '.$e->getMessage(), __METHOD__);
        }

        return $subscriptionModel;
    }

    /**
     * @param $id
     * @return bool
     */
    public function cancelStripeSubscription($id)
    {
        $response = false;

        try {
            StripePlugin::$app->settings->initializeStripe();

            $subscription = $this->getStripeSubscription($id);
            $subscription->cancel();
            $response = true;
        } catch (\Exception $e) {
            Craft::error('Unable to cancel subscription: '.$e->getMessage(), __METHOD__);
        }

        return $response;
    }

    /**
     * @param $stripeTransactionId
     * @return bool
     */
    public function getIsSubscription($stripeTransactionId)
    {
        $transactionId = substr($stripeTransactionId, 0, 3);

        if ($transactionId != 'sub'){
            return false;
        }

        return true;
    }
}
