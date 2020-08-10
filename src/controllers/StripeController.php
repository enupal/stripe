<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\controllers;

use craft\helpers\UrlHelper;
use craft\web\Controller as BaseController;
use enupal\stripe\elements\PaymentForm;
use enupal\stripe\enums\PaymentType;
use enupal\stripe\Stripe as StripePlugin;
use Craft;
use yii\web\NotFoundHttpException;

class StripeController extends BaseController
{
    /**
     * @inheritdoc
     */
    protected $allowAnonymous = true;

    /**
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionSaveOrder()
    {
        $this->requirePostRequest();

        $enableCheckout = Craft::$app->getRequest()->getBodyParam('enableCheckout') ?? true;
        $postData = $_POST;

        if (isset($postData['billingAddress'])) {
            $postData['enupalStripe']['billingAddress'] = $postData['billingAddress'];
        }

        if (isset($postData['address'])) {
            $postData['enupalStripe']['address'] = $postData['address'];

            if (isset($postData['enupalStripe']['sameAddressToggle']) && $postData['enupalStripe']['sameAddressToggle'] == 'on') {
                $postData['enupalStripe']['address'] = $postData['billingAddress'];
            }
        }

        // Stripe Elements
        if (!$enableCheckout) {
            $paymentType = Craft::$app->getRequest()->getBodyParam('paymentType');

            if ($paymentType == PaymentType::IDEAL || $paymentType == PaymentType::SOFORT) {
                $response = StripePlugin::$app->orders->processAsynchronousPayment();

                if (is_null($response) || !isset($response['source'])) {
                    throw new NotFoundHttpException("Unable to process the Asynchronous Payment");
                }

                $source = $response['source'];

                return $this->redirect($source->redirect->url);
            } else if ($paymentType == PaymentType::CC) {
                $postData['enupalStripe']['email'] = Craft::$app->getRequest()->getBodyParam('stripeElementEmail') ?? null;
            }
        }

        // Stripe Checkout or Stripe Elements CC
        $order = StripePlugin::$app->orders->processPayment($postData);

        if (is_null($order)) {
            throw new NotFoundHttpException("Unable to process the Payment");
        }

        if ($order->getErrors()) {
            // Respond to ajax requests with JSON
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                    'errors' => $order->getErrors(),
                ]);
            }

            // Return the form using it's name as a variable on the front-end
            Craft::$app->getUrlManager()->setRouteParams([
                $order->getPaymentForm()->handle => $order
            ]);

            return null;
        }

        return $this->redirectToPostedUrl($order);
    }

    /**
     * @return \yii\web\Response|null
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionCancelSubscription()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $subscriptionId = $request->getRequiredBodyParam('subscriptionId');
        $cancelAtPeriodEnd = null;
        if (isset($_POST['cancelAtPeriodEnd'])) {
            $cancelAtPeriodEnd = filter_var($_POST['cancelAtPeriodEnd'], FILTER_VALIDATE_BOOLEAN);
        }

        if (is_null($cancelAtPeriodEnd)) {
            $settings = StripePlugin::$app->settings->getSettings();
            $cancelAtPeriodEnd = $settings->cancelAtPeriodEnd;
        }

        $result = StripePlugin::$app->subscriptions->cancelStripeSubscription($subscriptionId, $cancelAtPeriodEnd);

        if (!$result) {
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                ]);
            }

            Craft::$app->getUrlManager()->setRouteParams([
                    'success' => false
                ]
            );

            return null;
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson([
                'success' => true
            ]);
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * @return \yii\web\Response|null
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\base\Exception
     */
    public function actionCreateCustomerPortal()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $returnUrl = $request->getBodyParam('returnUrl') ?? UrlHelper::siteUrl('/');
        $user = Craft::$app->getUser()->getIdentity();

        if (is_null($user)) {
            return $this->redirectToPostedUrl();
        }

        $stripeUser = StripePlugin::$app->customers->getStripeCustomerByEmail($user->email);

        if (is_null($stripeUser)) {
            return $this->redirectToPostedUrl();
        }

        $result = StripePlugin::$app->customers->createCustomerPortalSession($stripeUser['id'], $returnUrl);

        if ($result === null) {
            return $this->redirectToPostedUrl();
        }

        return $this->redirect($result['url']);
    }

    /**
     * @return \yii\web\Response|null
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionReactivateSubscription()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $subscriptionId = $request->getRequiredBodyParam('subscriptionId');

        $result = StripePlugin::$app->subscriptions->reactivateStripeSubscription($subscriptionId);

        if (!$result) {
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                ]);
            }

            Craft::$app->getUrlManager()->setRouteParams([
                    'success' => false
                ]
            );

            return null;
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson([
                'success' => true
            ]);
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * This action handles the request after Stripe Checkout is done
     *
     * @return \yii\web\Response
     * @throws \Throwable
     * @throws \yii\base\Exception
     */
    public function actionFinishOrder()
    {
        $sessionId = $_GET['session_id'] ?? null;
        // Lets wait 5 seconds until Stripe is done
        sleep(5);

        $checkoutSession = StripePlugin::$app->checkout->getCheckoutSession($sessionId);
        if ($checkoutSession === null) {
            Craft::error('Unable to find the chekout session id', __METHOD__);
            return $this->redirect('/');
        }
        // Get the order from the payment intent id
        $stripeId = null;
        $paymentIntent = null;
        if ($checkoutSession['payment_intent'] !== null) {
            $paymentIntent = StripePlugin::$app->paymentIntents->getPaymentIntent($checkoutSession['payment_intent']);
            if ($paymentIntent === null) {
                Craft::error('Unable to find the payment intent id: ' . $checkoutSession['payment_intent'], __METHOD__);
                return $this->redirect('/');
            }

            $stripeId = $paymentIntent['charges']['data'][0]['id'];
        } else if ($checkoutSession['subscription'] !== null) {
            $stripeId = $checkoutSession['subscription'];
        }

        if ($stripeId === null) {
            Craft::error('Unable to find the stripe id from the checkout session: ', __METHOD__);
            return $this->redirect('/');
        }

        $order = StripePlugin::$app->orders->getOrderByStripeId($stripeId);

        if ($order === null) {
            Craft::error('Unable to find the order by stripe id: ' . $stripeId, __METHOD__);
            return $this->redirect('/');
        }

        if ($paymentIntent){
            try {
                StripePlugin::$app->paymentIntents->updateDescriptionToPaymentIntent($paymentIntent, $order);
            }catch (\Exception $e){
                Craft::error('Unable to update description to payment intent', __METHOD__);
            }
        }

        $sessionSuccessUrl = Craft::$app->getSession()->get(PaymentForm::SESSION_CHECKOUT_SUCCESS_URL);
        Craft::$app->getSession()->remove(PaymentForm::SESSION_CHECKOUT_SUCCESS_URL);
        $returnUrl = $sessionSuccessUrl ? $sessionSuccessUrl : $order->getPaymentForm()->checkoutSuccessUrl;
        $url = '/';

        if ($returnUrl) {
            $url = Craft::$app->getView()->renderObjectTemplate($returnUrl, $order);
        }

        return $this->redirect($url);
    }

    /**
     * @return \yii\web\Response|null
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionUpdateBillingInfo()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $stripeToken = $request->getRequiredBodyParam('stripeToken');
        $stripeEmail = $request->getRequiredBodyParam('stripeEmail');

        $stripeCustomer = StripePlugin::$app->customers->updateBillingInfo($stripeToken, $stripeEmail);

        if (is_null($stripeCustomer)) {
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                ]);
            }

            Craft::$app->getUrlManager()->setRouteParams([
                    'success' => false
                ]
            );

            return null;
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'stripeCustomer' => $stripeCustomer
            ]);
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * This action handles the request after Stripe Checkout setup session is done
     * @return \yii\web\Response
     * @throws \Throwable
     * @throws \yii\base\Exception
     */
    public function actionFinishSetupSession()
    {
        $sessionId = $_GET['session_id'] ?? null;
        // Lets wait 5 seconds until Stripe is done
        sleep(5);

        $checkoutSession = StripePlugin::$app->checkout->getCheckoutSession($sessionId);

        if ($checkoutSession === null){
            Craft::error('Unable to find the chekout session id',__METHOD__);
            return $this->redirect('/');
        }

        $setupIntentId = $checkoutSession['setup_intent'] ?? null;
        $setupIntent = StripePlugin::$app->checkout->getSetupIntent($setupIntentId);

        if ($setupIntent === null){
            Craft::error('Unable to find the setup intent',__METHOD__);
            return $this->redirect('/');
        }

        $customerId = $setupIntent['metadata']['customer_id'] ?? null;
        $successUrl = $setupIntent['metadata']['success_url'] ?? '/';
        $paymentMethodId = $setupIntent['payment_method'] ?? null;

        if ($customerId === null || $paymentMethodId === null){
            Craft::error('Unable to find the customerId or paymentMethodId',__METHOD__);
            return $this->redirect('/');
        }

        $result = StripePlugin::$app->customers->attachPaymentMethodToCustomer($paymentMethodId, $customerId);

        if (!$result){
            Craft::error('Something went wrong when attaching the payment method to the customer',__METHOD__);
            return $this->redirect('/');
        }

        $returnUrl = StripePlugin::$app->checkout->getSiteUrl($successUrl);

        return $this->redirect($returnUrl);
    }

    /**
     * @return \yii\web\Response|null
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionUpdateSubscription()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $planId = $request->getRequiredBodyParam('planId');
        $subscriptionId = $request->getRequiredBodyParam('subscriptionId');

        $subscription = StripePlugin::$app->customers->updateSubscription($subscriptionId, $planId);

        if (is_null($subscription)) {
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                ]);
            }

            Craft::$app->getUrlManager()->setRouteParams([
                    'success' => false
                ]
            );

            return null;
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'subscription' => $subscription
            ]);
        }

        return $this->redirectToPostedUrl();
    }
}
