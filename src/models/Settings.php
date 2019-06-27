<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\models;

use craft\base\Model;
use enupal\stripe\Stripe;


class Settings extends Model
{
    // General
    public $testPublishableKey;
    public $testSecretKey;
    public $livePublishableKey;
    public $liveSecretKey;
    public $testMode = 1;
    public $capture = 1;
    public $useSca = 1;
    // Globals
    public $returnUrl;
    public $defaultCurrency = 'USD';
    // Tax
    public $enableTaxes = 0;
    public $taxType = 0;
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
    public $loadJquery = 1;
    public $loadCss = 1;
    // Snyc
    public $syncType = 1;
    public $syncLimit = 500;
    public $syncIfUserExists = false;
    public $syncDefaultFormId;
    public $syncDefaultStatusId;
    public $syncEnabledDateRange = false;
    public $syncStartDate;
    public $syncEndDate;

    public $cancelAtPeriodEnd = false;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                ['livePublishableKey', 'liveSecretKey'],
                'required', 'on' => 'general', 'when' => function($model) {
                    $configSettings = Stripe::$app->settings->getConfigSettings();
                    $isRequired = isset($configSettings['livePublishableKey']) ? false : true;
                    return !$model->testMode &&  $isRequired;
                }
            ],
            [
                ['liveSecretKey'],
                'required', 'on' => 'general', 'when' => function($model) {
                $configSettings = Stripe::$app->settings->getConfigSettings();
                $isRequired = isset($configSettings['liveSecretKey']) ? false : true;
                return !$model->testMode &&  $isRequired;
            }
            ],
            [
                ['testPublishableKey'],
                'required', 'on' => 'general', 'when' => function($model) {
                    $configSettings = Stripe::$app->settings->getConfigSettings();
                    $isRequired = isset($configSettings['testPublishableKey']) ? false : true;
                    return $model->testMode && $isRequired;
                }
            ],
            [
                ['syncType', 'syncLimit', 'syncDefaultFormId', 'syncDefaultStatusId'],
                'required', 'on' => 'sync'
            ],
            [
                ['syncStartDate', 'syncEndDate'],
                'required', 'on' => 'sync', 'when' => function($model) {
                    return $model->syncEnabledDateRange;
                }
            ],
            ['syncStartDate', 'validateDates', 'on' => 'sync', 'when' => function($model) {
                    return $model->syncEnabledDateRange;
                }
            ],
            [
                ['syncLimit'],
                'number', 'on' => 'sync', 'min'=> '1', 'max'=>'1000'
            ],
            [
                ['testSecretKey'],
                'required', 'on' => 'general', 'when' => function($model) {
                $configSettings = Stripe::$app->settings->getConfigSettings();
                $isRequired = isset($configSettings['testSecretKey']) ? false : true;
                return $model->testMode && $isRequired;
            }
            ],
            [
                ['adminNotificationRecipients', 'adminNotificationSenderName', 'adminNotificationSubject', 'adminNotificationSenderEmail', 'adminNotificationReplyToEmail'],
                'required', 'when' => function($model) {
                return $model->enableAdminNotification;
            }
            ],
            [
                ['customerNotificationSubject', 'customerNotificationSenderName', 'customerNotificationSenderEmail', 'customerNotificationReplyToEmail', 'customerNotificationReplyToEmail'],
                'required', 'when' => function($model) {
                return $model->enableCustomerNotification;
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

    public function validateDates(){
        if ($this->syncEndDate && $this->syncStartDate){
            if(strtotime($this->syncEndDate->format('Y-m-d')) <= strtotime($this->syncStartDate->format('Y-m-d'))){
                $this->addError('syncStartDate','Please give correct Start and End dates');
                $this->addError('syncEndDate','Please give correct Start and End dates');
            }
        }else{
            if (!$this->syncEndDate){
                $this->addError('syncEndDate','End Date is required');
            }
            if (!$this->syncStartDate){
                $this->addError('syncStartDate','Start Date is required');
            }
        }
    }
}