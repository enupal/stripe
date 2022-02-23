<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use enupal\stripe\Stripe as StripePlugin;
use Stripe\ShippingRate;
use yii\base\Component;
use Craft;

class Shipping extends Component
{
    /**
     * @return array
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getAllShippingRates()
    {
        StripePlugin::$app->settings->initializeStripe();
        $startingAfter = null;
        $shippingRates = ShippingRate::all(['limit' => 50, 'starting_after' => $startingAfter]);

        $result = [];

        while(isset($shippingRates['data']) && is_array($shippingRates['data']))
        {
            $lastShippingRate = null;
            foreach ($shippingRates['data'] as $shippingRate) {
                $result[] = $shippingRate;
                $lastShippingRate = $shippingRate;
            }

            if ($shippingRates['has_more'] && !is_null($lastShippingRate)){
                $startingAfter = $lastShippingRate['id'];
                $shippingRates = ShippingRate::all(['limit' => 50, 'starting_after' => $startingAfter]);
            }else{
                $shippingRates = null;
            }
        }

        return $result;
    }

    /**
     * @param $shippingId
     * @return bool
     * @throws \Exception
     */
    public function archiveById($shippingId)
    {
        StripePlugin::$app->settings->initializeStripe();

        try {
            ShippingRate::update($shippingId, ['active' => false]);
        } catch (\Exception $e) {
            Craft::error($e->getMessage(), __METHOD__);
            return false;
        }

        return true;
    }

    /**
     * @param $shippingRateId
     * @return \Stripe\StripeObject|null
     * @throws \Exception
     */
    public function getShippingRate($shippingRateId)
    {
        StripePlugin::$app->settings->initializeStripe();
        $shippingRate = null;

        try {
            $shippingRate = ShippingRate::retrieve($shippingRateId);
        } catch(\Exception $e){
            Craft::error('Unable to find the Shipping ID: '.$shippingRateId);
        }

        return $shippingRate;
    }

    /**
     * @return array
     */
    public function getShippingRatesAsOptions()
    {
        $shippingRatesAsOptions = [];
        try {
            $shippingRates = $this->getAllShippingRates();
            foreach ($shippingRates as $shippingRate) {
                if (!$shippingRate['active']) {
                    continue;
                }
                $option = [
                    'label' => $shippingRate['display_name'],
                    'value' => $shippingRate['id']
                ];

                $shippingRatesAsOptions[] = $option;
            }
        }catch (\Exception $e) {
            Craft::error($e->getMessage(), __METHOD__);
        }

        return $shippingRatesAsOptions;
    }
}
