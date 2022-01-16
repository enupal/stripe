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
use enupal\stripe\Stripe as StripePlugin;
use craft\web\Controller as BaseController;

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
        StripePlugin::$app->carts->populateCart($cart, $postData);


        return $this->asJson(['success' => true, 'sessionId' => $session['id']]);
    }
}
