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

class CouponsController extends BaseController
{
    // Disable CSRF validation for the entire controller
    public $enableCsrfValidation = false;

    protected $allowAnonymous = ['actionValidate'];

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
        $currency = $request->getRequiredBodyParam('currency');
        $successMessage = $request->getRequiredBodyParam('successMessage');
        $amount = $request->getRequiredBodyParam('amount');

        $result = [
            'success' => true
        ];

        $coupon = Stripe::$app->coupons->getCoupon($couponCode);
        $finalAmount = $amount;

        if ($coupon){
            if ($coupon['valid']){
                $finalAmount = Stripe::$app->coupons->applyCouponToAmount($amount, $coupon);
                $result['coupon'] = $coupon;
                if ($coupon['amount_off']){
                    $couponCurrency = $coupon['currency'];
                    $minimumCharge = Stripe::$app->orders->getMinimumCharge($currency);
                    $minimumCharge = Stripe::$app->orders->convertToCents($minimumCharge, $currency);

                    if (!$isRecurring) {
                        if ($finalAmount < $minimumCharge) {
                            $result['success'] = false;
                            Craft::error('The final amount is less than allowed by Stripe after apply the coupon', __METHOD__);
                        }
                    }

                    if (strtolower($couponCurrency) != strtolower($currency)){
                        $result['success'] = false;
                        Craft::error('The amount currency and coupon currency are different', __METHOD__);
                    }
                }

                $successMessage = Craft::$app->view->renderString($successMessage, ['coupon' => $coupon]);
            }
        }else {
            $result['success'] = false;
        }

        if ($finalAmount < 0){
            $finalAmount = 0;
        }

        $finalAmount = Stripe::$app->orders->convertFromCents($finalAmount, $currency);
        $result['finalAmount'] = Craft::$app->getFormatter()->asCurrency($finalAmount, $currency);
        $result['successMessage'] = $successMessage;

        return $this->asJson($result);
    }
}
