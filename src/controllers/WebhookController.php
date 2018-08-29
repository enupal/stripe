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
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
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

        switch ($eventJson['type']) {
            case 'source.chargeable':

                $order = Stripe::$app->orders->idealCharge($order, $eventJson);

                break;
            case 'source.failed':
                Craft::error('Stripe Payments - Source Failed, order: '.$order->number, __METHOD__);
                Stripe::$app->orders->addMessageToOrder($order, "source failed");
                break;
            case 'source.canceled':
                Craft::error('Stripe Payments - Source Canceled,  order: '.$order->number, __METHOD__);
                Stripe::$app->orders->addMessageToOrder($order, "source canceled");
                break;
        }

        Stripe::$app->orders->triggerWebhookEvent($eventJson, $order);

        http_response_code(200); // PHP 5.4 or greater

        $return['success'] = true;
        return $this->asJson($return);
    }
}
