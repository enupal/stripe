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
use craft\i18n\Locale;
use enupal\stripe\Stripe;
use enupal\stripe\Stripe as StripePlugin;
use Stripe\Invoice;
use Stripe\InvoiceLineItem;
use Stripe\SubscriptionItem;
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
        $dateFormat = strtolower(Craft::$app->sites->getCurrentSite()->getLocale()->getDateFormat(Locale::LENGTH_SHORT, Locale::FORMAT_PHP));
        $this->startDate = isset($subscription['current_period_start']) ? $this->convertDate($subscription['current_period_start'], $dateFormat) : null;
        $this->endDate = isset($subscription['current_period_end']) ? $this->convertDate($subscription['current_period_end'], $dateFormat) : null;
        $this->daysUntilDue = $subscription['days_until_due'] ?? 0;
        $this->planNickName = $this->getNickNames($subscription);
        $this->quantity = $this->getQuantity($subscription);
        $this->interval = $subscription['items']['data'][0]['plan']['interval'] ?? null;
        $this->status = $subscription['status'] ?? null ;
        $this->canceledAt = isset($subscription['canceled_at']) && $subscription['canceled_at'] ? $this->convertDate($subscription['canceled_at'], $dateFormat) : null;
        $this->data = $subscription;
        $this->statusHtml = Stripe::$app->subscriptions->getSubscriptionStatusHtml($this->status);
        $this->cancelAtPeriodEnd = $subscription['cancel_at_period_end'] ?? null;
        $this->customer = $subscription['customer'] ?? null;

        if (isset($subscription['plan']['usage_type']) && $subscription['plan']['usage_type'] === 'metered'){
            $this->meteredId = $subscription['items']['data'][0]['id'];
            if ($this->validateMetered()){
                $invoice = $this->getUpcomingInvoiceItem();
                $this->meteredQuantity = $invoice['lines']['data'][0]['quantity'];
                $this->meteredInfo = $invoice['lines']['data'][0];
                $this->meteredInfoAsJson = json_encode($this->meteredInfo);
            }
        }
    }

    private function convertDate($date, $dateFormat) {
        try {
            return DateTimeHelper::toDateTime($date)->format($dateFormat);
        }catch (\Exception $exception) {
            Craft::error($exception->getMessage(), __METHOD__);
        }

        return $date;
    }

    private function getQuantity($subscription)
    {
        $quantity = 0;

        if (!isset($subscription['items']['data'] )) {
            return $quantity;
        }

        foreach ($subscription['items']['data'] as $item) {
            $quantity += $item['quantity'];
        }

        return $quantity;
    }

    private function getNickNames($subscription)
    {
        $nickName = "";
        $count = 1;

        if (!isset($subscription['items']['data'])) {
            return $nickName;
        }

        foreach ($subscription['items']['data'] as $item) {
            $name = $item['plan']['nickname'] ?? 'Plan '.$count;
            $nickName .= $name.", ";
            $count++;
        }

        return rtrim($nickName, ", ");
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
            SubscriptionItem::createUsageRecord(
                $this->meteredId,
                [
                    'quantity' => $quantity,
                    'timestamp' => $dateTime,
                    'action' => $action,
                ]
            );
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