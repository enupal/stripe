<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use enupal\stripe\elements\PaymentForm;
use Stripe\Price;
use yii\base\Component;
use enupal\stripe\Stripe as StripePlugin;

class Prices extends Component
{
    /**
     * @param int $amountInCents
     * @param string $currency
     * @param PaymentForm $paymentForm
     * @return Price
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function cretePrice(int $amountInCents, string $currency, $paymentForm)
    {
        StripePlugin::$app->settings->initializeStripe();

        $price = Price::create([
            'unit_amount' => $amountInCents,
            'currency' => $currency,
            'product_data' => [
                'name' => $paymentForm->companyName
            ]
        ]);

        return $price;
    }
}
