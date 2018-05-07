<?php
/**
 * EnupalStripe plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
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
        $matrixMultiplePlansField = Craft::$app->fields->getFieldByHandle(StripePlugin::$app->buttons::MULTIPLE_PLANS_HANDLE);

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
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     */
    public function getStripePlans()
    {
        $options = [];
        StripePlugin::$app->settings->initializeStripe();

        $plans = Plan::all();
        $option['label'] = 'Select Plan...';
        $option['value'] = '';
        $option['default'] = '';
        array_push($options, $option);
        if (isset($plans['data'])) {
            foreach ($plans['data'] as $plan) {
                if ($plan['nickname']) {
                    $planId = $plan['id'];
                    $planName = $this->getDefaultPlanName($plan);
                    $option['label'] = $planName;
                    $option['value'] = $planId;
                    $option['default'] = '';
                    array_push($options, $option);
                }
            }
        }

        return $options;
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
        $intervalCount = $plan['interval_count'];
        $interval = $intervalCount > 1 ? $intervalCount.' '.$plan['interval'].'s' : $plan['interval'];
        $amount = $plan['amount'] ?? $plan['tiers'][0]['amount'] ?? 0;
        $amount = Craft::$app->getFormatter()->asCurrency($amount / 100, strtoupper($plan['currency']));
        $planName = $plan['nickname'].' '.$amount.'/'.$interval;

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
