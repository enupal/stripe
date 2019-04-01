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
        $this->requireAcceptsJson();
        $request = Craft::$app->getRequest();
        $couponCode = $request->getRequiredBodyParam('couponCode');
        $amount = $request->getRequiredBodyParam('amount');
        $result = [
            'success' => false
        ];

        $coupon = Stripe::$app->coupons->getCoupon($couponCode);

        if ($coupon){
            if ($coupon['valid']){
                $newTotal = 0;
                if ($coupon['percent_off']){

                }else{
                    $currency = $coupon['currency'];
                    $amountOff = Stripe::$app->orders->convertFromCents($coupon['amount_off'], $currency) ;
                    
                }
            }
        }

        return $this->asJson($result);
    }
}
