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
     * @throws \Throwable
     * @throws \craft\errors\MissingComponentException
     */
    public function actionSaveOrder()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $orderId = $request->getBodyParam('orderId');

        $triggerEvent = $request->getBodyParam('triggerEvent') ?? false;

        $order = Stripe::$app->orders->getOrderById($orderId);

        if (is_null($order)) {
            throw new NotFoundHttpException(Stripe::t('Order not found'));
        }

        $order = Stripe::$app->orders->populatePaymentFormFromPost($order);

        // Save it
        if (!Stripe::$app->orders->saveOrder($order, $triggerEvent)) {
            Craft::$app->getSession()->setError(Stripe::t('Couldn’t save Order.'));

            // Respond to ajax requests with JSON
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                    'errors' => $order->getErrors(),
                ]);
            }

            Craft::$app->getUrlManager()->setRouteParams([
                    'order' => $order
                ]
            );

            return null;
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson([
                'success' => true
            ]);
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
	 * @throws \Throwable
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

    /**
     * Refund payment via ajax
     *
     * @return \yii\web\Response
     * @throws \Throwable
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionRefundPayment()
    {
        $this->requireAcceptsJson();
        $request = Craft::$app->getRequest();
        $orderNumber = $request->getRequiredBodyParam('orderNumber');
        $order = Stripe::$app->orders->getOrderByNumber($orderNumber);

        if (is_null($order)){
            return $this->asErrorJson("Order not found: ".$orderNumber);
        }

        $result = Stripe::$app->orders->refundOrder($order);

        if (!$result){
            return $this->asErrorJson("Unable to refund payment, please check messages tab.");
        }

        return $this->asJson(['success'=> true]);
    }

    /**
     * Capture payment via ajax
     *
     * @return \yii\web\Response
     * @throws \Throwable
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionCapturePayment()
    {
        $this->requireAcceptsJson();
        $request = Craft::$app->getRequest();
        $orderNumber = $request->getRequiredBodyParam('orderNumber');
        $order = Stripe::$app->orders->getOrderByNumber($orderNumber);

        if (is_null($order)){
            return $this->asErrorJson("Order not found: ".$orderNumber);
        }

        $result = Stripe::$app->orders->captureOrder($order);

        if (!$result){
            return $this->asErrorJson("Unable to capture payment, please check messages tab.");
        }

        return $this->asJson(['success'=> true]);
    }
}
