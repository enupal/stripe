<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\models;

use craft\base\Model;
use enupal\stripe\enums\DiscountType;
use enupal\stripe\validators\TaxValidator;

class Settings extends Model
{
    // General
    public $testPublishableKey;
    public $testSecretKey;
    public $livePublishableKey;
    public $liveSecretKey;
    public $testMode = 1;
    // Globals
    public $returnUrl;
    public $defaultCurrency = 'USD';
    // Tax
    public $enableTaxes = 0;
    public $taxType = DiscountType::RATE;
    public $displayTaxLabel = 0;
    public $tax;
    // Notification Customer
    public $enableCustomerNotification;
    public $customerNotificationSubject;
    public $customerNotificationSenderName;
    public $customerNotificationSenderEmail;
    public $customerNotificationReplyToEmail;
    public $customerNotificationTemplate;
    public $customerTemplateOverride;
    // Notification Admin
    public $enableAdminNotification;
    public $adminNotificationRecipients;
    public $adminNotificationSenderName;
    public $adminNotificationSubject;
    public $adminNotificationSenderEmail;
    public $adminNotificationReplyToEmail;
    public $adminNotificationTemplate;
    public $adminTemplateOverride;
    // Checkout Email
    public $currentUserEmail = 0;
    // Get plans with nickname
    public $plansWithNickname = 1;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                ['livePublishableKey', 'liveSecretKey'],
                'required', 'on' => 'general', 'when' => function($model) {
                    return !$model->testMode;
                }
            ],
            [
                ['testPublishableKey', 'testSecretKey'],
                'required', 'on' => 'general', 'when' => function($model) {
                return $model->testMode;
            }
            ],
            [
                ['customerNotificationSenderEmail', 'customerNotificationReplyToEmail'],
                'email', 'on' => 'customerNotification'
            ],
            [
                ['adminNotificationSenderEmail', 'adminNotificationReplyToEmail'],
                'email', 'on' => 'adminNotification'
            ],
            [
                // A number and two decimals
                ['tax'],
                'number', 'min'=> '1', 'max'=>'100' , 'on' => 'taxes', 'numberPattern' => '/^\d+(.\d{1,2})?$/',
            ]
        ];
    }
}