<?php
/**
 * EnupalStripe plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\stripe\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class StripeButtonsQuery extends ElementQuery
{

    // General - Properties
    // =========================================================================
    public $id;
    public $dateCreated;
    public $name;
    public $handle;

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
    public function __construct($elementType, array $config = [])
    {
        // Default orderBy
        if (!isset($config['orderBy'])) {
            $config['orderBy'] = 'enupalstripe_buttons.dateCreated';
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
        $this->joinElementTable('enupalstripe_buttons');

        $this->query->select([
            'enupalstripe_buttons.id',
            'enupalstripe_buttons.name',
            'enupalstripe_buttons.companyName',
            'enupalstripe_buttons.currency',
            'enupalstripe_buttons.language',
            'enupalstripe_buttons.amountType',
            'enupalstripe_buttons.amount',
            'enupalstripe_buttons.customAmountLabel',
            'enupalstripe_buttons.minimumAmount',
            'enupalstripe_buttons.logoImage',
            'enupalstripe_buttons.enableRememberMe',

            'enupalstripe_buttons.handle',
            'enupalstripe_buttons.quantity',
            'enupalstripe_buttons.hasUnlimitedStock',
            'enupalstripe_buttons.customerQuantity',
            'enupalstripe_buttons.soldOutMessage',
            'enupalstripe_buttons.discountType',
            'enupalstripe_buttons.discount',

            'enupalstripe_buttons.verifyZip',
            'enupalstripe_buttons.enableBillingAddress',
            'enupalstripe_buttons.enableShippingAddress',
            'enupalstripe_buttons.shippingAmount',

            'enupalstripe_buttons.itemWeight',
            'enupalstripe_buttons.itemWeightUnit',
            'enupalstripe_buttons.showItemName',
            'enupalstripe_buttons.showItemPrice',
            'enupalstripe_buttons.showItemCurrency',
            'enupalstripe_buttons.returnUrl',
            'enupalstripe_buttons.buttonText',
            'enupalstripe_buttons.paymentButtonProcessingText',
        ]);

        if ($this->name) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_buttons.name', $this->name)
            );
        }

        if ($this->handle) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_buttons.handle', $this->handle)
            );
        }

        if ($this->orderBy !== null && empty($this->orderBy) && !$this->structureId && !$this->fixedOrder) {
            $this->orderBy = 'dateCreated desc';
        }

        return parent::beforePrepare();
    }
}
