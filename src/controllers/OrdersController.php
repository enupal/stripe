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
use yii\web\NotFoundHttpException;
use yii\base\Exception;

use enupal\stripe\elements\PaymentForm as StripeElement;

class OrdersController extends BaseController
{
    /**
     * Save an Order
     *
     * @return null|\yii\web\Response
     * @throws NotFoundHttpException
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionSaveOrder()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $order = new StripeElement;

        $orderId = $request->getBodyParam('orderId');

        if ($orderId) {
            $order = Stripe::$app->orders->getOrderById($orderId);
        }

        if (!$order) {
            throw new NotFoundHttpException(Stripe::t('Order not found'));
        }

        $order = Stripe::$app->orders->populateButtonFromPost($order);

        // Save it
        if (!Stripe::$app->orders->saveOrder($order)) {
            Craft::$app->getSession()->setError(Stripe::t('Couldn’t save Order.'));

            Craft::$app->getUrlManager()->setRouteParams([
                    'order' => $order
                ]
            );

            return null;
        }

        Craft::$app->getSession()->setNotice(Stripe::t('Order saved.'));

        return $this->redirectToPostedUrl($order);
    }

    /**
     * Edit a Button.
     *
     * @param int|null           $orderId The form's ID, if editing an existing form.
     * @param StripeElement|null $order   The order send back by setRouteParams if any errors on savePaymentForm
     *
     * @return \yii\web\Response
     * @throws HttpException
     * @throws Exception
     */
    public function actionEditOrder(int $orderId = null, StripeElement $order = null)
    {
        if ($order === null) {
            $order = Stripe::$app->orders->getOrderById($orderId);
        }

        if (!$order) {
            throw new NotFoundHttpException(Stripe::t('Order not found'));
        }

        $variables['order'] = $order;

        // Set the "Continue Editing" URL
        $variables['continueEditingUrl'] = 'enupal-stripe/orders/edit/{id}';

        $variables['settings'] = Stripe::$app->settings->getSettings();

        return $this->renderTemplate('enupal-stripe/orders/_edit', $variables);
    }

    /**
     *  Delete a Order.
     *
     * @return \yii\web\Response
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\db\Exception
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionDeleteOrder()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $orderId = $request->getRequiredBodyParam('orderId');
        $order = Stripe::$app->orders->getOrderById($orderId);

        // @TODO - handle errors
        Stripe::$app->orders->deleteOrder($order);

        return $this->redirectToPostedUrl($order);
    }
}
