<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\controllers;

use craft\web\Controller as BaseController;
use Craft;
use enupal\stripe\Stripe;

class WebhookController extends FrontEndController
{
    /**
     * @return \yii\web\Response
     * @throws \Throwable
     */
    public function actionStripe()
    {
        // Retrieve the request's body and parse it as JSON:
        $input = @file_get_contents('php://input');
        $eventJson = json_decode($input, true);
        Craft::info(json_encode($eventJson), __METHOD__);

        $stripeId = $eventJson['data']['object']['id'] ?? null;

        $order = Stripe::$app->orders->getOrderByStripeId($stripeId);
        $return = [];

        switch ($eventJson['type']) {
            case 'source.chargeable':
                if ($order === null){
                    break;
                }
                // iDEAL or SOFORT
                $type = $eventJson['data']['object']['type'];
                $order = Stripe::$app->orders->asynchronousCharge($order, $eventJson, $type);

                break;
            case 'source.failed':
                if ($order === null){
                    break;
                }
                Craft::error('Stripe Payments - Source Failed, order: '.$order->number, __METHOD__);
                break;
            case 'source.canceled':
                if ($order === null){
                    break;
                }
                Craft::error('Stripe Payments - Source Canceled,  order: '.$order->number, __METHOD__);
                break;
            case 'charge.pending':
                // Sofort may require days for the funds to be confirmed and the charge to succeed.
                // Let's update the order message
                break;
            case 'charge.succeeded':
                if ($order === null){
                    break;
                }
                // Finalize the order and trigger order complete event to send a confirmation to the customer over email.
                if (!$order->isCompleted){
                    $order->isCompleted = true;
                    Stripe::$app->orders->saveOrder($order);
                }
                break;
            case 'charge.failed':
                if ($order === null){
                    break;
                }
                // Finalize the order and trigger order complete event to send a confirmation to the customer over email.
                Craft::error('Stripe Payments - Charge Failed,  order: '.$order->number, __METHOD__);
                break;

            case 'charge.captured':
                if ($order === null){
                    break;
                }
                // Capture Order
                $object = $eventJson['data']['object'];
                $order = Stripe::$app->orders->getOrderByStripeId($object['id']);
                if (isset($object['captured']) && $object['captured'] && $order) {
                    $order->isCompleted = true;
                    Stripe::$app->orders->saveOrder($order, false);
                    Stripe::$app->messages->addMessage($order->id, 'Webhook - Payment captured', $object);

                    Stripe::$app->orders->triggerOrderCaptureEvent($order);
                    Craft::info('Stripe Payments - Payment Captured order: '.$order->number, __METHOD__);
                }
                break;
            // New checkout
            case 'checkout.session.completed':
                // Capture Order
                $checkoutSession = $eventJson['data']['object'];
                $paymentIntentId = $checkoutSession['payment_intent'];
                $order = null;

                if ($paymentIntentId === null){
                    // We have a subscription
                    $subscriptionId = $checkoutSession['subscription'];
                    $subscription = Stripe::$app->subscriptions->getStripeSubscription($subscriptionId);
                    if ($subscription){
                        $order = Stripe::$app->paymentIntents->createOrderFromSubscription($subscription, $checkoutSession);
                    }
                }else{
                    $paymentIntent = Stripe::$app->paymentIntents->getPaymentIntent($paymentIntentId);

                    if ($paymentIntent){
                        $order = Stripe::$app->paymentIntents->createOrderFromPaymentIntent($paymentIntent, $checkoutSession);
                    }
                }

                if ($order === null){
                    Craft::error('Something went wrong creating the Order from checkout session', __METHOD__);
                }
                break;
        }

        // Let's add a message to the order
        if ($order !== null){
            Stripe::$app->messages->addMessage($order->id, $eventJson['type'], $eventJson);
        }

        Stripe::$app->orders->triggerWebhookEvent($eventJson, $order);

        http_response_code(200); // PHP 5.4 or greater

        $return['success'] = true;
        return $this->asJson($return);
    }
}
