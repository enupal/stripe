<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\controllers;

use Craft;
use enupal\stripe\elements\PaymentForm;
use enupal\stripe\enums\AmountType;
use enupal\stripe\Stripe;
use craft\web\Controller as BaseController;
use enupal\stripe\Stripe as StripePlugin;

class CheckoutController extends BaseController
{
    protected $allowAnonymous = true;

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

        if (!$this->validateForm($form, $data)) {
            return $this->asJson(['success' => false, 'errors' => ["invalid_amount" => "invalid minimum amount"]]);
        }

        $session = Stripe::$app->checkout->createCheckoutSession($form, $data);

        return $this->asJson(['success' => true, 'sessionId' => $session['id']]);
    }

    /**
     * @param PaymentForm $form
     * @param array $data
     * @return bool
     */
    private function validateForm(PaymentForm $form, array $data) {
        if ($form->amountType == AmountType::ONE_TIME_CUSTOM_AMOUNT) {
            if ((float)$form->minimumAmount > (float)StripePlugin::$app->orders->convertFromCents($data["amount"], $data['enupalStripeData']['stripe']["currency"])) {
                return false;
            }
        }

        return true;
    }
}
