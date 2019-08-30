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

class CouponsController extends FrontEndController
{
    /**
     * @return \yii\web\Response
     * @throws \Throwable
     */
    public function actionValidate()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $request = Craft::$app->getRequest();
        $couponCode = $request->getRequiredBodyParam('couponCode');
        $isRecurring = $request->getRequiredBodyParam('isRecurring');
        $isRecurring = filter_var ($isRecurring, FILTER_VALIDATE_BOOLEAN);

        $currency = $request->getRequiredBodyParam('currency');
        $successMessage = $request->getRequiredBodyParam('successMessage');
        $amount = $request->getRequiredBodyParam('amount');

        $couponRedeemed = Stripe::$app->coupons->applyCouponToAmountInCents($amount, $couponCode, $currency, $isRecurring);

        if ($couponRedeemed->isValid){
            $successMessage = Craft::$app->view->renderObjectTemplate($successMessage, $couponRedeemed->coupon);
        }

        $result = [
            'success' => $couponRedeemed->isValid,
            'coupon' => $couponRedeemed->coupon
        ];

        $finalAmount = Stripe::$app->orders->convertFromCents($couponRedeemed->finalAmount, $currency);
        $result['finalAmount'] = $finalAmount;
        $result['finalAmountInCents'] = $couponRedeemed->finalAmount;
        $result['finalAmountAsCurrency'] = Craft::$app->getFormatter()->asCurrency($finalAmount, $currency);
        $result['successMessage'] = $successMessage;

        return $this->asJson($result);
    }
}
