<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use enupal\stripe\Stripe as StripePlugin;
use Stripe\Token;
use yii\base\Component;

class Tokens extends Component
{
    /**
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getStripeToken($token)
    {
        StripePlugin::$app->settings->initializeStripe();

        return Token::retrieve($token);
    }
}
