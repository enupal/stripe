<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\controllers;

use Craft;
use craft\web\Controller as BaseController;
use enupal\stripe\Stripe;

class SubscriptionsController extends BaseController
{
    protected $allowAnonymous = true;

    /**
     * Get Subscription via ajax
     *
     * @return \yii\web\Response
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionRefreshSubscription()
    {
        $this->requireAcceptsJson();
        $request = Craft::$app->getRequest();
        $subscriptionId = $request->getRequiredBodyParam('subscriptionId');

        try {
            $subscription = Stripe::$app->subscriptions->getSubscriptionModel($subscriptionId);
        } catch (\Throwable $e) {
            return $this->asErrorJson($e->getMessage());
        }

        return $this->asJson(['success'=> true, 'subscription' => $subscription]);
    }

    /**
     * Cancel Subscription via ajax
     *
     * @return \yii\web\Response
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionCancelSubscription()
    {
        $this->requireAcceptsJson();
        $request = Craft::$app->getRequest();
        $subscriptionId = $request->getRequiredBodyParam('subscriptionId');
        $settings = Stripe::$app->settings->getSettings();
        $result = Stripe::$app->subscriptions->cancelStripeSubscription($subscriptionId, $settings->cancelAtPeriodEnd);

        if (!$result){
            return $this->asErrorJson("Unable to cancel subscription, please check your logs.");
        }

        return $this->asJson(['success'=> true]);
    }

    /**
     * Reactivate Subscription via ajax
     *
     * @return \yii\web\Response
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionReactivateSubscription()
    {
        $this->requireAcceptsJson();
        $request = Craft::$app->getRequest();
        $subscriptionId = $request->getRequiredBodyParam('subscriptionId');
        $result = Stripe::$app->subscriptions->reactivateStripeSubscription($subscriptionId);

        if (!$result){
            return $this->asErrorJson("Unable to reactivate subscription, please check your logs.");
        }

        return $this->asJson(['success'=> true]);
    }
}
