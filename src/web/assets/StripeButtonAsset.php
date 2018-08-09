<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\web\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class StripeButtonAsset extends AssetBundle
{
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = '@enupal/stripe/resources/';

        // define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        // define the dependencies
        // define the relative path to CSS/JS files that should be registered with the page
        // when this asset bundle is registered
        $this->css = [
            'css/stripeforms.css'
        ];

        $this->js = [
            'js/enupalstripebutton.js'
        ];

        parent::init();
    }
}