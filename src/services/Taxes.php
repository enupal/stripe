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
    const TAX_RATES = 'tax_rates';
    const DYNAMIC_TAX_RATES = 'dynamic_tax_rates';
    const DEFAULT_TAX_RATES = 'default_tax_rates';

    /**
     * @return array
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getAllTaxes()
    {
        StripePlugin::$app->settings->initializeStripe();
        $startingAfter = null;
        $taxes = TaxRate::all(['limit' => 50, 'active' => true, 'starting_after' => $startingAfter]);

        $result = [];

        while(isset($taxes['data']) && is_array($taxes['data']))
        {
            $lastTax = null;
            foreach ($taxes['data'] as $tax) {
                $result[] = $tax;
                $lastTax = $tax;
            }

            if ($taxes['has_more'] && !is_null($lastTax)){
                $startingAfter = $lastTax['id'];
                $taxes = TaxRate::all(['limit' => 50, 'active' => true, 'starting_after' => $startingAfter]);
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

    /**
     * @return array
     */
    public function getTaxAsOptions()
    {
        $taxAsOptions = [];
        try {
            $taxes = $this->getAllTaxes();
            foreach ($taxes as $tax) {
                $inclusive = $tax['inclusive'] ? 'Inclusive' : "Exclusive";
                if (!empty($tax['display_name'])) {
                    $label = $tax['display_name']. ' - ';
                }
                if (!empty($tax['jurisdiction']) && $tax['jurisdiction']) {
                    $label .= $tax['jurisdiction']. ' - ';
                }
                if (!empty($tax['percentage'])) {
                    $label .= $tax['percentage']. '% '.$inclusive;
                }

                if (!empty($tax['description'])) {
                    $label .= ' ('.$tax['description'].')';
                }

                $option = [
                    'label' => $label,
                    'value' => $tax['id']
                ];

                $taxAsOptions[] = $option;
            }
        }catch (\Exception $e) {
            Craft::error($e->getMessage(), __METHOD__);
        }

        return $taxAsOptions;
    }
}
