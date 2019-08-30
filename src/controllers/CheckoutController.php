<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\controllers;

use Craft;
use enupal\stripe\Stripe;

class CheckoutController extends FrontEndController
{
    /**
     * @return \yii\web\Response
     * @throws \Throwable
     */
    public function actionCreateSession()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        $data = $request->getRequiredBodyParam('enupalStripe');
        $enupalStripeData = json_decode($request->getRequiredBodyParam('enupalStripeData'), true);
        $data['enupalCouponCode'] = $request->getBodyParam('enupalCouponCode');
        $data['enupalStripeData'] = $enupalStripeData;
        $formId = $data['formId'] ?? null;
        if ($formId === null){
            throw new \Exception("Unable to find the formId: ".$formId, __METHOD__);
        }

        $data['enupalStripeData']['stripe']['amount'] = $data['amount'];

        $form = Stripe::$app->paymentForms->getPaymentFormById($formId);

        $session = Stripe::$app->checkout->createCheckoutSession($form, $data);

        return $this->asJson(['success' => true, 'sessionId' => $session['id']]);
    }
}
