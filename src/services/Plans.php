<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use Craft;
use enupal\stripe\models\CustomPlan;
use yii\base\Component;
use enupal\stripe\Stripe as StripePlugin;
use Stripe\Plan;


class Plans extends Component
{
    /**
     * Updates plans under Select Plan dropdown within matrix field
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @return bool
     */
    public function getUpdatePlans()
    {
        $options = $this->getStripePlans(true);

        if (empty($options)){
            return false;
        }

        $currentFieldContext = Craft::$app->getContent()->fieldContext;
        Craft::$app->getContent()->fieldContext = StripePlugin::$app->settings->getFieldContext();
        $matrixMultiplePlansField = Craft::$app->fields->getFieldByHandle(StripePlugin::$app->paymentForms::MULTIPLE_PLANS_HANDLE);

        $matrixFields = $matrixMultiplePlansField->getBlockTypeFields();
        foreach ($matrixFields as $matrixField) {
            if ($matrixField->handle == 'selectPlan'){
                $matrixField->options = $options;
                // Update the select plan field with the plans from stripe
                Craft::$app->fields->saveField($matrixField);
                break;
            }
        }

        Craft::$app->getContent()->fieldContext = $currentFieldContext;

        return true;
    }

    /**
     * Get all plans
     *
     * @return array|bool
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     */
    public function getMultiplePlansFromButton()
    {
        $options = $this->getStripePlans();
        $finalPlans = [];

        if (empty($options)){
            return false;
        }

        $currentFieldContext = Craft::$app->getContent()->fieldContext;
        Craft::$app->getContent()->fieldContext = StripePlugin::$app->settings->getFieldContext();
        $matrixMultiplePlansField = Craft::$app->fields->getFieldByHandle(StripePlugin::$app->paymentForms::MULTIPLE_PLANS_HANDLE);

        $matrixFields = $matrixMultiplePlansField->getBlockTypeFields();
        foreach ($matrixFields as $matrixField) {
            if ($matrixField->handle == 'selectPlan'){
                foreach ($matrixField->options as $option) {
                    if ($option['value']){
                        $plan = $this->getStripePlan($options['value']);
                        if ($plan){
                            if (isset($plans['data'])) {
                                $finalPlans[$options['value']] = $plan['data'];
                            }
                        }
                    }
                }
                break;
            }
        }

        Craft::$app->getContent()->fieldContext = $currentFieldContext;

        return $finalPlans;
    }

    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     */
    public function getStripePlans($checkDuplicateLabels = false)
    {
        $options = [];
        StripePlugin::$app->settings->initializeStripe();
        $settings =  StripePlugin::$app->settings->getSettings();
        $startingAfter = null;
        $plans = Plan::all(['limit' => 50, 'starting_after' => $startingAfter]);
        $option['label'] = 'Select Plan...';
        $option['value'] = '';
        $option['default'] = '';
        array_push($options, $option);

        while(isset($plans['data']) && is_array($plans['data']))
        {
            foreach ($plans['data'] as $plan) {
                $isValid = true;
                if ($checkDuplicateLabels){
                    foreach ($options as $option) {
                        if ($option['label'] === $this->getDefaultPlanName($plan)){
                            $isValid = false;
                            break;
                        }
                    }
                }
                if (!$isValid){
                    continue;
                }
                if ($settings->plansWithNickname){
                    if ($plan['nickname']) {
                        $this->populatePlan($plan, $options);
                    }
                }else{
                    $this->populatePlan($plan, $options);
                }
            }
            $startingAfter = $plan['id'];
            if ($plans['has_more']){
                $plans = Plan::all(['limit' => 50, 'starting_after' => $startingAfter]);
            }else{
                $plans = null;
            }
        }

        return $options;
    }

    /**
     * @param $plan
     * @param $options
     * @throws \yii\base\InvalidConfigException
     */
    private function populatePlan($plan, &$options)
    {
        $planId = $plan['id'];
        $planName = $this->getDefaultPlanName($plan);
        $option['label'] = $planName;
        $option['value'] = $planId;
        $option['default'] = '';
        array_push($options, $option);
    }

    /**
     * Create a human readable plan name given a plan array.
     *
     * @param $plan
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public function getDefaultPlanName($plan)
    {
        $nickname = $plan['nickname'] != '' ? $plan['nickname'] : $plan['id'] ;
        $intervalCount = $plan['interval_count'];
        $interval = $intervalCount > 1 ? $intervalCount.' '.$plan['interval'].'s' : $plan['interval'];
        $amount = $plan['amount'] ?? $plan['tiers'][0]['unit_amount'] ?? 0;
        $currency= strtoupper($plan['currency']);
        $amount = StripePlugin::$app->orders->convertFromCents($amount, $currency);
        $amount = Craft::$app->getFormatter()->asCurrency($amount, $currency);
        $planName = $nickname.' '.$amount.'/'.$interval;

        return $planName;
    }

    /**
     * @param $id
     * @return null|\Stripe\StripeObject
     * @throws \Exception
     */
    public function getStripePlan($id)
    {
        $plan = null;
        StripePlugin::$app->settings->initializeStripe();

        $plan = Plan::retrieve($id);

        return $plan;
    }

    /**
     * @param $plan \Stripe\StripeObject
     * @param $quantity
     * @return float|int
     */
    public function getPlanAmount($plan, $quantity)
    {
        $amount = $plan['amount'];

        if (isset($plan['billing_scheme']) && $plan['billing_scheme'] == 'tiered'){
            foreach ($plan['tiers'] as $pos => $tier) {
                $flat = $tier['flat_amount'] ?? 0;
                // last tier
                if ($tier['up_to'] === null){
                    if ($quantity > $plan['tiers'][$pos-1]['up_to']){
                        $amount = $tier['unit_amount'] + $flat;
                    }
                }else{
                    if ($quantity <= $tier['up_to']){
                        if ($pos > 0){
                            if ($quantity > $plan['tiers'][$pos-1]['up_to']){
                                $amount = $tier['unit_amount'] + $flat;
                            }
                        }else{
                            $amount = $tier['unit_amount'] + $flat;
                        }
                    }
                }
            }
        }else if (isset($plan['billing_scheme']) && $plan['billing_scheme'] == 'per_unit'){
            $amount = $amount * $quantity;
        }

        $amount = StripePlugin::$app->orders->convertFromCents($amount, strtoupper($plan['currency']));

        return $amount;
    }

    /**
     * @param CustomPlan $customPlan
     * @return Plan
     */
    public function createCustomPlan(CustomPlan $customPlan)
    {
        $currentTime = time();
        $planName = strval($currentTime);
        //Create new plan for this customer:
        $params = [
            "amount" => $customPlan->amountInCents,
            "interval" => $customPlan->interval,
            "product" => [
                "name" => "Custom Plan - " . $planName,
            ],
            "currency" => $customPlan->currency,
            "id" => $planName
        ];

        if ($customPlan->intervalCount){
            $params['interval_count'] = $customPlan->intervalCount;
        }

        if ($customPlan->trialPeriodDays){
            $params['trial_period_days'] = $customPlan->trialPeriodDays;
        }

        $plan = Plan::create($params);

        return $plan;
    }
}
