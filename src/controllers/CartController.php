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
use enupal\stripe\services\Carts;
use enupal\stripe\Stripe as StripePlugin;
use craft\web\Controller as BaseController;
use yii\filters\AccessControl;
use yii\web\Response;

class CartController extends BaseController
{
    protected $allowAnonymous = true;
    public $enableCsrfValidation = false;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['access'] = [
            'class' => AccessControl::class,
            'rules' => [
                [
                    'allow' => true,
                    'actions' => ['add', 'update', 'clear'],
                    'verbs' => ['POST']
                ],
                [
                    'allow' => true,
                    'actions' => ['index'],
                    'verbs' => ['GET']
                ],
            ],
        ];

        return $behaviors;
    }

    /**
     * @return \yii\web\Response
     * @throws \Throwable
     */
    public function actionAdd()
    {
        $this->requireAcceptsJson();

        $cart = StripePlugin::$app->carts->getCart() ?? new Cart();
        $postData = Craft::$app->getRequest()->getBodyParams();

        try {
            StripePlugin::$app->carts->addOrUpdateCart($cart, $postData);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->asJson($cart->asArray());
    }

    /**
     * @return \yii\web\Response
     * @throws \Throwable
     */
    public function actionUpdate()
    {
        $this->requireAcceptsJson();

        $cart = StripePlugin::$app->carts->getCart() ?? new Cart();
        $postData = Craft::$app->getRequest()->getBodyParams();

        try {
            StripePlugin::$app->carts->addOrUpdateCart($cart, $postData, true);
        } catch (CartItemException $exception) {
            return $this->errorResponse($exception);
        } catch (\Exception $exception) {
            return $this->errorResponse(new CartItemException($exception, Carts::INTERNAL_SERVER_ERROR));
        }

        return $this->asJson($cart->asArray());
    }

    /**
     * @return \yii\web\Response
     * @throws \Throwable
     */
    public function actionIndex()
    {
        $this->requireAcceptsJson();

        $cart = StripePlugin::$app->carts->getCart() ?? new Cart();

        return $this->asJson($cart->asArray());
    }

    /**
     * @return \yii\web\Response
     * @throws \Throwable
     */
    public function actionClear()
    {
        $this->requireAcceptsJson();

        $cart = StripePlugin::$app->carts->getCart() ?? new Cart();

        try {
            StripePlugin::$app->carts->clearCart($cart);
        }catch (CartItemException $exception){
            return $this->errorResponse($exception);
        }catch (\Exception $exception) {
            return $this->errorResponse(new CartItemException($exception, Carts::INTERNAL_SERVER_ERROR));
        }

        return $this->asJson($cart->asArray());
    }

    /**
     * @return Response
     */
    private function errorResponse(CartItemException $exception)
    {
        Craft::error($exception->getMessage(), __METHOD__);
        Craft::$app->getResponse()->statusCode = $exception->getCode();

        return $this->asJson(['error' => true, 'message' => $exception->getMessage()]);
    }
}
