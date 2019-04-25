<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use craft\db\Query;
use craft\helpers\DateTimeHelper;
use enupal\stripe\models\CouponRedeemed;
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

        foreach ($coupons as $coupon) {
            $coupon['one_time_redeemed'] = $this->getTotalTimesCouponRedeemed($coupon['id']);
        }

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

    /**
     * Apply a coupon to an amount in cents
     *
     * @param $amountInCents
     * @param $couponCode
     * @param $currency
     * @param bool $isRecurring
     * @return CouponRedeemed
     * @throws \Exception
     */
    public function applyCouponToAmountInCents($amountInCents, $couponCode, $currency, $isRecurring = false)
    {
        $coupon = $this->getCoupon($couponCode);
        $couponRedeemed = new CouponRedeemed();
        $couponRedeemed->isValid = true;
        $couponRedeemed->finalAmount = $amountInCents;

        if (!isset($coupon['valid']) && !$coupon['valid']) {
            $couponRedeemed->addErrorMessage(Craft::t('enupal-stripe', 'The coupon is no longer valid'));
            return $couponRedeemed;
        }

        $isValidDate = $this->validateCouponDate($coupon);

        if (!$isValidDate){
            $couponRedeemed->addErrorMessage(Craft::t('enupal-stripe', 'The Coupon date has expired'));
            return $couponRedeemed;
        }

        $canBeRedeemed = $this->validateTimesRedeemed($coupon, $isRecurring);

        if (!$canBeRedeemed){
            $couponRedeemed->addErrorMessage(Craft::t('enupal-stripe', 'Reached maximum number of times this coupon can be redeemed'));
            return $couponRedeemed;
        }

        $couponRedeemed->coupon = $coupon;

        if ($coupon['percent_off']){
            $percentOff = $coupon['percent_off'];
            $discountAmount = $amountInCents * ($percentOff / 100);
            $couponRedeemed->finalAmount = $amountInCents - $discountAmount;
        }else {
            $amountOff = $coupon['amount_off'];
            $couponRedeemed->finalAmount = $amountInCents - $amountOff;

            $couponCurrency = $coupon['currency'];
            if (strtolower($couponCurrency) != strtolower($currency)) {
                $couponRedeemed->addErrorMessage(Craft::t('enupal-stripe', 'The amount currency and coupon currency are different'));
            }
        }

        $minimumCharge = StripePlugin::$app->orders->getMinimumChargeInCents($currency);

        if (!$isRecurring){
            if ($couponRedeemed->finalAmount < $minimumCharge) {
                $couponRedeemed->addErrorMessage(Craft::t('enupal-stripe', 'The final amount is less than allowed by Stripe after apply the coupon'));
            }
        }

        return $couponRedeemed;
    }

    /**
     * Return the total times that a coupon is Redeemed
     *
     * @param $coupon
     * @return int|string
     */
    public function getTotalTimesCouponRedeemed($coupon)
    {
        $total = (new Query())
            ->select('id')
            ->from(['{{%enupalstripe_orders}}'])
            ->where(['<>','paymentType', 2])
            ->andWhere(['[[couponCode]]' => $coupon])
            ->count();

        return $total;
    }

    /**
     * @param Coupon $coupon
     * @return bool
     */
    private function validateCouponDate(Coupon $coupon): bool
    {
        $redeemBy = $coupon['redeem_by'] ?? null;
        $result = false;

        if (!is_null($redeemBy)){
            $result = DateTimeHelper::isInThePast($redeemBy);
        }

        return !$result;
    }

    /**
     * @param Coupon $coupon
     * @param bool $isRecurring
     * @return bool
     */
    private function validateTimesRedeemed(Coupon $coupon, $isRecurring): bool
    {
        if ($isRecurring){
            if (!is_null($coupon['max_redemptions'])){
                if ($coupon['times_redeemed'] >= $coupon['max_redemptions']){
                    return false;
                }
            }
        }else{
            if (!is_null($coupon['max_redemptions'])) {
                $timesRedeemed = $this->getTotalTimesCouponRedeemed($coupon['id']);
                if ((int)$timesRedeemed >= $coupon['max_redemptions']) {
                    return false;
                }
            }
        }

        return true;
    }
}
