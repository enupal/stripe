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
use yii\base\Component;
use enupal\stripe\Stripe as StripePlugin;

class Subscriptions extends Component
{
    /**
     * @param $id
     * @return null|\Stripe\StripeObject
     * @throws \Exception
     */
    public function getStripeSubscription($id)
    {
        $subscription = null;
        StripePlugin::$app->settings->initializeStripe();

        $subscription = Subscription::retrieve($id);

        return $subscription;
    }

    /**
     * @param $id
     * @return null
     * @throws \Exception
     */
    public function cancelStripeSubscription($id)
    {
        $plan = null;
        StripePlugin::$app->settings->initializeStripe();

        $subscription = $this->getStripeSubscription($id);
        $subscription->cancel();

        return $plan;
    }
}
