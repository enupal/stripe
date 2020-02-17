<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\models;

use Craft;
use craft\base\Model;
use craft\helpers\DateTimeHelper;
use enupal\stripe\Stripe;
use enupal\stripe\Stripe as StripePlugin;
use Stripe\Invoice;
use Stripe\InvoiceLineItem;
use Stripe\UsageRecord;
use yii\db\Exception;

class Subscription extends Model
{
    // General
    public $startDate;
    public $endDate;
    public $daysUntilDue;
    public $planNickName;
    public $data;
    public $quantity;
    public $interval;
    public $canceledAt;
    public $status;
    public $statusHtml;
    public $cancelAtPeriodEnd;
    public $meteredId = null;
    public $meteredQuantity;
    public $customer;
    /**
     * @var InvoiceLineItem
     */
    public $meteredInfo;
    public $meteredInfoAsJson;

    public function __construct($subscription = [])
    {
        $this->startDate = isset($subscription['current_period_start']) ? DateTimeHelper::toDateTime($subscription['current_period_start'])->format('m/d/Y') : null;
        $this->endDate = isset($subscription['current_period_end']) ? DateTimeHelper::toDateTime($subscription['current_period_end'])->format('m/d/Y') : null;
        $this->daysUntilDue = $subscription['days_until_due'] ?? 0;
        $this->planNickName = $subscription['plan']['nickname'] ?? null ;
        $this->quantity = $subscription['quantity'] ?? null ;
        $this->interval = $subscription['plan']['interval'] ?? null ;
        $this->status = $subscription['status'] ?? null ;
        $this->canceledAt = isset($subscription['canceled_at']) && $subscription['canceled_at'] ? DateTimeHelper::toDateTime($subscription['canceled_at'])->format('m/d/Y') : null;
        $this->data = $subscription;
        $this->statusHtml = Stripe::$app->subscriptions->getSubscriptionStatusHtml($this->status);
        $this->cancelAtPeriodEnd = $subscription['cancel_at_period_end'];
        $this->customer = $subscription['customer'];

        if ($subscription['plan']['usage_type'] === 'metered'){
            $this->meteredId = $subscription['items']['data'][0]['id'];
            if ($this->validateMetered()){
                $invoice = $this->getUpcomingInvoiceItem();
                $this->meteredQuantity = $invoice['lines']['data'][0]['quantity'];
                $this->meteredInfo = $invoice['lines']['data'][0];
                $this->meteredInfoAsJson = json_encode($this->meteredInfo);
            }
        }
    }

    /**
     * @param $quantity
     * @param null $dateTime
     * @param string $action
     * @return bool
     * @throws Exception
     */
    public function reportUsage($quantity, $dateTime = null, $action = 'increment')
    {
        if (!$this->validateMetered()){
            return false;
        }

        $dateTime = $dateTime ?? DateTimeHelper::currentTimeStamp();
        try{
            UsageRecord::create([
                'quantity' => $quantity,
                'timestamp' => $dateTime,
                'subscription_item' => $this->meteredId,
                'action' => $action,
            ]);
        }catch (\Exception $e){
            throw new Exception($e->getMessage());
        }

        return true;
    }

    /**
     * @return mixed|null
     * @throws \Exception
     */
    public function getUsage()
    {
        if ($this->validateMetered()){
            $invoice = $this->getUpcomingInvoiceItem();
            $this->meteredQuantity = $invoice['lines']['data'][0]['quantity'];
            return $this->meteredQuantity;
        }

        return null;
    }

    /**
     * @return Invoice
     */
    private function getUpcomingInvoiceItem()
    {
        $invoice = Invoice::upcoming([
            "subscription" => $this->data['id']]
        );

        return $invoice;
    }

    /**
     * @return array|null
     * @throws \Stripe\Error\Api
     */
    public function getPaidInvoices()
    {
        $stripeCustomerId = $this->customer;

        if ($stripeCustomerId === null){
            return null;
        }

        $invoices = Invoice::all([
            'limit' => 50,
            'customer' => $stripeCustomerId,
            'status' => 'paid'
        ]);

        $finalInvoices = [];

        while(isset($invoices['data']) && is_array($invoices['data']))
        {
            foreach ($invoices['data'] as $invoice) {
                $finalInvoices[] = $invoice;
            }

            $startingAfter = $invoice['id'];

            if ($invoices['has_more']){
                $invoices = Invoice::all(['limit' => 50, 'customer' => $stripeCustomerId, 'starting_after' => $startingAfter]);
            }else{
                $invoices = null;
            }
        }

        return $finalInvoices;
    }

    /**
     *
     * @return bool
     * @throws \Exception
     */
    private function validateMetered()
    {
        if (!is_null($this->meteredId) && ($this->status == 'active' || $this->status == 'trialing')){
            StripePlugin::$app->settings->initializeStripe();

            return true;
        }

        return false;
    }
}