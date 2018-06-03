<?php
/**
 * EnupalStripe plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
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
    public $taxType = DiscountType::RATE;
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
                ['tax'],
                TaxValidator::class, 'on' => 'taxes'
            ],
        ];
    }
}