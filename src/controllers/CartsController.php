<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\controllers;

use Craft;
use enupal\stripe\elements\Cart;
use enupal\stripe\exceptions\CartItemException;
use enupal\stripe\Stripe as StripePlugin;
use craft\web\Controller as BaseController;
use yii\web\Response;

class CartsController extends BaseController
{
    protected $allowAnonymous = true;

    /**
     * @return \yii\web\Response
     * @throws \Throwable
     */
    public function actionAdd()
    {
        $this->requireAcceptsJson();
        $cart = StripePlugin::$app->carts->getCart() ?? new Cart();
        $postData = $_POST;

        try {
            StripePlugin::$app->carts->populateCart($cart, $postData);
        } catch (CartItemException $e) {
            Craft::error($e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }

        //@todo add serialize cart for response and add the stripe object for each item

        return $this->asJson(['success' => true, 'sessionId' => $session['id']]);
    }

    /**
     * @param string $message
     * @param int $statusCode
     * @return Response
     */
    private function errorResponse(string $message, int $statusCode = 400)
    {
        Craft::$app->getResponse()->statusCode = $statusCode;

        return $this->asJson(['error' => true, 'message' => $message]);
    }
}
