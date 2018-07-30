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
use yii\web\NotFoundHttpException;

class WebhookController extends BaseController
{
    // Disable CSRF validation for the entire controller
    public $enableCsrfValidation = false;

    protected $allowAnonymous = ['actionStripe'];

    /**
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionStripe()
    {
        // Retrieve the request's body and parse it as JSON:
        $input = @file_get_contents('php://input');
        $eventJson = json_decode($input, true);
        Craft::info(json_encode($eventJson), __METHOD__);

        $stripeId = $eventJson['data']['object']['id'] ?? null;

        $order = Stripe::$app->orders->getOrderByStripeId($stripeId);

        if ($order === null) {
            throw new NotFoundHttpException(Stripe::t('Order not found'));
        }

        switch ($eventJson['type']) {
            case 'source.chargeable':

                break;
            case 'source.failed':

                break;
            case 'source.canceled':

                break;
        }

        // Do something with $event_json

        http_response_code(200); // PHP 5.4 or greater

        $return['success'] = true;
        return $this->asJson($return);
    }
}
