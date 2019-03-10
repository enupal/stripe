<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
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
     * @var PaymentForms
     */
    public $paymentForms;

    /**
     * @var Orders
     */
    public $orders;

    /**
     * @var Subscriptions
     */
    public $subscriptions;

    /**
     * @var Plans
     */
    public $plans;

    /**
     * @var Messages
     */
    public $messages;

    /**
     * @var Customers
     */
    public $customers;

    /**
     * @var OrderStatuses
     */
    public $orderStatuses;

    /**
     * @var Emails
     */
    public $emails;

    /**
     * @var Addresses
     */
    public $addresses;

    /**
     * @var Countries
     */
    public $countries;

    /**
     * @var Reports
     */
    public $reports;

    public function init()
    {
        $this->settings = new Settings();
        $this->paymentForms = new PaymentForms();
        $this->orders = new Orders();
        $this->plans = new Plans();
        $this->subscriptions = new Subscriptions();
        $this->messages = new Messages();
        $this->customers = new Customers();
        $this->orderStatuses = new OrderStatuses();
        $this->emails = new Emails();
        $this->addresses = new Addresses();
        $this->countries = new Countries();
        $this->reports = new Reports();
    }
}