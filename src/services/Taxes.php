<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use enupal\stripe\Stripe as StripePlugin;
use Stripe\TaxRate;
use yii\base\Component;
use Craft;

class Taxes extends Component
{
    /**
     * @return array
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getAllTaxes()
    {
        StripePlugin::$app->settings->initializeStripe();
        $startingAfter = null;
        $taxes = TaxRate::all(['limit' => 50, 'starting_after' => $startingAfter]);

        $result = [];

        while(isset($taxes['data']) && is_array($taxes['data']))
        {
            foreach ($taxes['data'] as $tax) {
                $result[] = $tax;
            }

            $startingAfter = $tax['id'];
            if ($taxes['has_more']){
                $taxes = TaxRate::all(['limit' => 50, 'starting_after' => $startingAfter]);
            }else{
                $taxes = null;
            }
        }

        return $result;
    }

    /**
     * @param $taxId
     * @return bool
     * @throws \Exception
     */
    public function archiveById($taxId)
    {
        StripePlugin::$app->settings->initializeStripe();

        try {
            TaxRate::update($taxId, ['active' => false]);
        } catch (\Exception $e) {
            Craft::error($e->getMessage(), __METHOD__);
            return false;
        }

        return true;
    }

    /**
     * @param $taxId
     * @return \Stripe\StripeObject|null
     * @throws \Exception
     */
    public function getTax($taxId)
    {
        StripePlugin::$app->settings->initializeStripe();
        $tax = null;

        try {
            $tax = TaxRate::retrieve($taxId);
        } catch(\Exception $e){
            Craft::error('Unable to find the Tax ID: '.$taxId);
        }

        return $tax;
    }
}
