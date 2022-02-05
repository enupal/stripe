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
                    'actions' => ['add', 'update'],
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
        $this->requirePostRequest();

        $cart = StripePlugin::$app->carts->getCart() ?? new Cart();
        $postData = Craft::$app->getRequest()->getBodyParams();

        try {
            StripePlugin::$app->carts->addCart($cart, $postData);
        } catch (\Exception $e) {
            Craft::error($e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
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
