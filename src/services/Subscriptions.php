<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use Craft;
use enupal\stripe\elements\Order;
use Stripe\Subscription;
use enupal\stripe\models\Subscription as SubscriptionModel;
use yii\base\Component;
use enupal\stripe\Stripe as StripePlugin;

class Subscriptions extends Component
{
    /**
     * @param $id
     * @return null|\Stripe\StripeObject
     */
    public function getStripeSubscription($id)
    {
        $subscription = null;

        try {
            StripePlugin::$app->settings->initializeStripe();

            $subscription = Subscription::retrieve($id);

        } catch (\Exception $e) {
            Craft::error('Unable to get subscription: '.$e->getMessage(), __METHOD__);
        }

        return $subscription;
    }

    /**
     * @param $id
     * @return SubscriptionModel
     */
    public function getSubscriptionModel($id)
    {
        $subscription = $this->getStripeSubscription($id);

        $subscriptionModel = new SubscriptionModel($subscription);

        return $subscriptionModel;
    }

    /**
     * @param $id
     * @return bool
     * @throws \Exception
     */
    public function cancelStripeSubscription($id)
    {
        $response = false;

        try {
            StripePlugin::$app->settings->initializeStripe();

            $subscription = $this->getStripeSubscription($id);

            if ($subscription === null){
                Craft::error('Subscription not found: '.$id, __METHOD__);
                return $response;
            }

            $subscription->cancel();
            $response = true;
        } catch (\Exception $e) {
            Craft::error('Unable to cancel subscription: '.$e->getMessage(), __METHOD__);
            throw $e;
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

    /**
     * @param $status
     * @return string
     */
    public function getSubscriptionStatusHtml($status)
    {
        $statuses = [
            'trialing' => 'grey',
            'active' => 'green',
            'past_due' => 'orange',
            'canceled' => 'red',
            'unpaid' => 'yellow',
        ];

        $color = $statuses[$status] ?? '';
        $html = "<span class='status ".$color."'> </span>".$status;

        return $html;
    }

    /**
     * @param $userId
     * @return array|\craft\base\ElementInterface|null
     */
    public function getSubscriptionsByUser($userId)
    {
        $query = Order::find();
        $query->userId = $userId;
        $query->isSubscription = true;

        return $query->all();
    }

    /**
     * @param $email
     * @return array|\craft\base\ElementInterface|null
     */
    public function getSubscriptionsByEmail($email)
    {
        $query = Order::find();
        $query->email = $email;
        $query->isSubscription = true;

        return $query->all();
    }
}
