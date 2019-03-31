<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use enupal\stripe\Stripe as StripePlugin;
use Stripe\Coupon;
use yii\base\Component;
use Craft;

class Coupons extends Component
{
    /**
     * @return \Stripe\Collection
     * @throws \Stripe\Error\Api
     */
    public function getAllCoupons()
    {
        StripePlugin::$app->settings->initializeStripe();

        $coupons = Coupon::all();

        return $coupons;
    }

    /**
     * @param $couponId
     * @return \Stripe\StripeObject|null
     * @throws \Exception
     */
    public function getCoupon($couponId)
    {
        StripePlugin::$app->settings->initializeStripe();
        $coupon = null;

        try {
            $coupon = Coupon::retrieve($couponId);
        } catch(\Exception $e){
            Craft::error('Unable to find the Coupon ID: '.$couponId);
        }

        return $coupon;
    }

    /**
     * @param $couponId
     * @return bool
     * @throws \Exception
     */
    public function deleteCoupon($couponId)
    {
        StripePlugin::$app->settings->initializeStripe();
        $result = false;

        try {
            $coupon = $this->getCoupon($couponId);
            $coupon->delete();
            $result = true;
        } catch(\Exception $e){
            Craft::error('Unable to find the Coupon ID: '.$couponId);
        }

        return $result;
    }
}
