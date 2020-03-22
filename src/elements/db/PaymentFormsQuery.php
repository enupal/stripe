<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class PaymentFormsQuery extends ElementQuery
{

    // General - Properties
    // =========================================================================
    public $id;
    public $dateCreated;
    public $name;
    public $handle;
    public $enableCheckout;
    public $paymentType;

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        parent::__set($name, $value);
    }

    /**
     * @inheritdoc
     */
    public function handle($value)
    {
        $this->handle = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSku()
    {
        return $this->handle;
    }

    /**
     * @inheritdoc
     */
    public function name($value)
    {
        $this->name = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function enableCheckout($value)
    {
        $this->enableChackout = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getEnableCheckout()
    {
        return $this->enableCheckout;
    }

    /**
     * @inheritdoc
     */
    public function paymentType($value)
    {
        $this->paymentType = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getPaymentType()
    {
        return $this->paymentType;
    }

    /**
     * @inheritdoc
     */
    public function __construct($elementType, array $config = [])
    {
        // Default orderBy
        if (!isset($config['orderBy'])) {
            $config['orderBy'] = 'enupalstripe_forms.dateCreated';
        }

        parent::__construct($elementType, $config);
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        $this->joinElementTable('enupalstripe_forms');

        if (is_null($this->query)){
            return false;
        }

        $this->query->select([
            'enupalstripe_forms.id',
            'enupalstripe_forms.name',
            'enupalstripe_forms.handle',
            'enupalstripe_forms.enableCheckout',
            'enupalstripe_forms.checkoutSuccessUrl',
            'enupalstripe_forms.checkoutCancelUrl',
            'enupalstripe_forms.checkoutSubmitType',
            'enupalstripe_forms.paymentType',
            'enupalstripe_forms.checkoutPaymentType',
            'enupalstripe_forms.companyName',
            'enupalstripe_forms.currency',
            'enupalstripe_forms.language',
            'enupalstripe_forms.amountType',
            'enupalstripe_forms.amount',
            'enupalstripe_forms.customAmountLabel',
            'enupalstripe_forms.minimumAmount',
            'enupalstripe_forms.logoImage',
            'enupalstripe_forms.enableRememberMe',
            'enupalstripe_forms.enableRecurringPayment',
            'enupalstripe_forms.recurringPaymentType',
            'enupalstripe_forms.selectPlanLabel',

            'enupalstripe_forms.quantity',
            'enupalstripe_forms.hasUnlimitedStock',
            'enupalstripe_forms.customerQuantity',
            'enupalstripe_forms.soldOutMessage',

            'enupalstripe_forms.enableSubscriptions',
            'enupalstripe_forms.subscriptionType',
            'enupalstripe_forms.singlePlanSetupFee',
            'enupalstripe_forms.singlePlanInfo',
            'enupalstripe_forms.enableCustomPlanAmount',
            'enupalstripe_forms.customPlanMinimumAmount',
            'enupalstripe_forms.customPlanDefaultAmount',
            'enupalstripe_forms.customPlanInterval',
            'enupalstripe_forms.customPlanFrequency',
            'enupalstripe_forms.subscriptionStyle',
            'enupalstripe_forms.singlePlanTrialPeriod',

            'enupalstripe_forms.verifyZip',
            'enupalstripe_forms.enableBillingAddress',
            'enupalstripe_forms.enableShippingAddress',
            'enupalstripe_forms.shippingAmount',

            'enupalstripe_forms.itemWeight',
            'enupalstripe_forms.itemWeightUnit',
            'enupalstripe_forms.showItemName',
            'enupalstripe_forms.showItemPrice',
            'enupalstripe_forms.showItemCurrency',
            'enupalstripe_forms.returnUrl',
            'enupalstripe_forms.buttonText',
            'enupalstripe_forms.paymentButtonProcessingText',
            'enupalstripe_forms.checkoutButtonText',
            'enupalstripe_forms.buttonClass',
            'enupalstripe_forms.enableTemplateOverrides',
            'enupalstripe_forms.templateOverridesFolder'
        ]);

        if ($this->name) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_forms.name', $this->name)
            );
        }

        if ($this->handle) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_forms.handle', $this->handle)
            );
        }

        if ($this->orderBy !== null && empty($this->orderBy) && !$this->structureId && !$this->fixedOrder) {
            $this->orderBy = 'dateCreated desc';
        }

        return parent::beforePrepare();
    }
}
