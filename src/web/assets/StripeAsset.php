<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\web\assets;

use craft\web\AssetBundle;
use enupal\stripe\Stripe;

class StripeAsset extends AssetBundle
{
    public function init()
    {
        $settings = Stripe::$app->settings->getSettings();
        // define the path that your publishable resources live
        $this->sourcePath = '@enupal/stripe/resources/';

        // define the relative path to CSS/JS files that should be registered with the page
        // when this asset bundle is registered
        if ($settings->loadCss) {
            $this->css = [
                'stripe/dist/css/enupal-button.min.css'
            ];
        }

        if ($settings->loadJquery){
            $this->js[] = 'vendor/js/jquery.min.js';
        }

        $this->js[] = 'stripe/dist/js/enupal-stripe.min.js';

        parent::init();
    }
}