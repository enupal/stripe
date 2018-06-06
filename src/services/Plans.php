<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use Craft;
use yii\base\Component;
use enupal\stripe\Stripe as StripePlugin;
use Stripe\Plan;


class Plans extends Component
{
    /**
     * Updates plans under Select Plan dropdown within matrix field
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     */
    public function getUpdatePlans()
    {
        $options = $this->getStripePlans();

        if (empty($options)){
            return false;
        }

        $currentFieldContext = Craft::$app->getContent()->fieldContext;
        Craft::$app->getContent()->fieldContext = 'enupalStripe:';
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
     */
    public function getMultiplePlansFromButton()
    {
        $options = $this->getStripePlans();
        $finalPlans = [];

        if (empty($options)){
            return false;
        }

        $currentFieldContext = Craft::$app->getContent()->fieldContext;
        Craft::$app->getContent()->fieldContext = 'enupalStripe:';
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
    public function getStripePlans()
    {
        $options = [];
        StripePlugin::$app->settings->initializeStripe();
        $settings =  StripePlugin::$app->settings->getSettings();

        $plans = Plan::all();
        $option['label'] = 'Select Plan...';
        $option['value'] = '';
        $option['default'] = '';
        array_push($options, $option);
        if (isset($plans['data'])) {
            foreach ($plans['data'] as $plan) {
                if ($settings->plansWithNickname){
                    if ($plan['nickname']) {
                        $this->populatePlan($plan, $options);
                    }
                }else{
                    $this->populatePlan($plan, $options);
                }
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
        $amount = $plan['amount'] ?? $plan['tiers'][0]['amount'] ?? 0;
        $amount = Craft::$app->getFormatter()->asCurrency($amount / 100, strtoupper($plan['currency']));
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
}
