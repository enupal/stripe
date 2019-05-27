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

class WebhookController extends BaseController
{
    // Disable CSRF validation for the entire controller
    public $enableCsrfValidation = false;

    protected $allowAnonymous = ['actionStripe'];

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

        if ($order === null || $stripeId === null) {
            Stripe::$app->orders->triggerWebhookEvent($eventJson, $order);
            $return['success'] = false;
            return $this->asJson($return);
        }
        // Let's add a message to the order
        Stripe::$app->messages->addMessage($order->id, $eventJson['type'], $eventJson);

        switch ($eventJson['type']) {
            case 'source.chargeable':
                // iDEAL or SOFORT
                $type = $eventJson['data']['object']['type'];
                $order = Stripe::$app->orders->asynchronousCharge($order, $eventJson, $type);

                break;
            case 'source.failed':
                Craft::error('Stripe Payments - Source Failed, order: '.$order->number, __METHOD__);
                break;
            case 'source.canceled':
                Craft::error('Stripe Payments - Source Canceled,  order: '.$order->number, __METHOD__);
                break;
            case 'charge.pending':
                // Sofort may require days for the funds to be confirmed and the charge to succeed.
                // Let's update the order message
                break;
            case 'charge.succeeded':
                // Finalize the order and trigger order complete event to send a confirmation to the customer over email.
                if (!$order->isCompleted){
                    $order->isCompleted = true;
                    Stripe::$app->orders->saveOrder($order);
                }
                break;
            case 'charge.failed':
                // Finalize the order and trigger order complete event to send a confirmation to the customer over email.
                Craft::error('Stripe Payments - Charge Failed,  order: '.$order->number, __METHOD__);
                break;

            case 'charge.captured':
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
        }

        Stripe::$app->orders->triggerWebhookEvent($eventJson, $order);

        http_response_code(200); // PHP 5.4 or greater

        $return['success'] = true;
        return $this->asJson($return);
    }
}
