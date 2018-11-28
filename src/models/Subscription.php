<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\models;

use craft\base\Model;
use craft\helpers\DateTimeHelper;
use enupal\stripe\Stripe;
use Craft;

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
    }
}