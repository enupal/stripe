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

    /**
     * @var PaymentIntents
     */
    public $paymentIntents;

    /**
     * @var Coupons
     */
    public $coupons;

    /**
     * @var Checkout
     */
    public $checkout;

    /**
     * @var Vendors
     */
    public $vendors;

    /**
     * @var Commissions
     */
    public $commissions;

    /**
     * @var Connects
     */
    public $connects;

    /**
     * @var Taxes
     */
    public $taxes;

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
        $this->coupons = new Coupons();
        $this->paymentIntents = new PaymentIntents();
        $this->checkout = new Checkout();
        $this->vendors = new Vendors();
        $this->connects = new Connects();
        $this->commissions = new Commissions();
        $this->taxes = new Taxes();
    }
}