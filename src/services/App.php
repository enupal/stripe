<?php
/**
 * EnupalStripe plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\stripe\services;

use craft\base\Component;

class App extends Component
{
    /**
     * @var Settings
     */
    public $settings;

    /**
     * @var Buttons
     */
    public $buttons;

    /**
     * @var Orders
     */
    public $orders;

    public function init()
    {
        $this->settings = new Settings();
        $this->buttons = new Buttons();
        $this->orders = new Orders();
    }
}