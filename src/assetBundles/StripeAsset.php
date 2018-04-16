<?php
/**
 * EnupalStripe plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\stripe\assetbundles;

use craft\web\AssetBundle;

class StripeAsset extends AssetBundle
{
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = '@enupal/stripe/resources/';

        // define the relative path to CSS/JS files that should be registered with the page
        // when this asset bundle is registered
        $this->css = [
            'stripe/css/enupal-button.css'
        ];

        $this->js = [
            'vendor/js/jquery.min.js',
            'stripe/js/enupal-stripe.js'
        ];

        parent::init();
    }
}