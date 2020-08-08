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

class CommissionsQuery extends ElementQuery
{
    // General - Properties
    // =========================================================================
    public $id;
    public $orderId;
    public $productId;
    public $connectId;
    public $stripeId;
    public $number;
    public $orderType;
    public $commissionStatus;
    public $totalPrice;
    public $testMode;
    public $currency;
    public $datePaid;

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
    public function orderId($value)
    {
        $this->orderId = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getOrderId()
    {
        return $this->connectId;
    }

    /**
     * @inheritdoc
     */
    public function productId($value)
    {
        $this->productId = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * @inheritdoc
     */
    public function number($value)
    {
        $this->number = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @inheritdoc
     */
    public function stripeId($value)
    {
        $this->stripeId = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getStripeId()
    {
        return $this->stripeId;
    }

    /**
     * @inheritdoc
     */
    public function testMode($value)
    {
        $this->testMode = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTestMode()
    {
        return $this->testMode;
    }

    /**
     * @inheritdoc
     */
    public function connectId($value)
    {
        $this->connectId = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getConnectId()
    {
        return $this->connectId;
    }

    /**
     * @inheritdoc
     */
    public function orderType($value)
    {
        $this->orderType = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getOrderType()
    {
        return $this->orderType;
    }

    /**
     * @inheritdoc
     */
    public function commissionStatus($value)
    {
        $this->commissionStatus = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCommissionStatus()
    {
        return $this->commissionStatus;
    }

    /**
     * @inheritdoc
     */
    public function totalPrice($value)
    {
        $this->totalPrice = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTotalPrice()
    {
        return $this->totalPrice;
    }

    /**
     * @inheritdoc
     */
    public function currency($value)
    {
        $this->currency = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @inheritdoc
     */
    public function datePaid($value)
    {
        $this->datePaid = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDatePaid()
    {
        return $this->datePaid;
    }

    /**
     * @inheritdoc
     */
    public function __construct($elementType, array $config = [])
    {
        // Default orderBy
        if (!isset($config['orderBy'])) {
            $config['orderBy'] = 'enupalstripe_commissions.dateCreated';
        }

        parent::__construct($elementType, $config);
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @throws \Exception
     */
    protected function beforePrepare(): bool
    {
        $this->joinElementTable('enupalstripe_commissions');

        if (is_null($this->query)){
            return false;
        }

        $this->query->select([
            'enupalstripe_commissions.id',
            'enupalstripe_commissions.connectId',
            'enupalstripe_commissions.stripeId',
            'enupalstripe_commissions.number',
            'enupalstripe_commissions.orderId',
            'enupalstripe_commissions.productId',
            'enupalstripe_commissions.orderType',
            'enupalstripe_commissions.commissionStatus',
            'enupalstripe_commissions.totalPrice',
            'enupalstripe_commissions.testMode',
            'enupalstripe_commissions.currency',
            'enupalstripe_commissions.datePaid'
        ]);

        if ($this->id) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_commissions.id', $this->id)
            );
        }

        if ($this->connectId) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_commissions.connectId', $this->connectId)
            );
        }

        if ($this->orderId) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_commissions.orderId', $this->orderId)
            );
        }

        if ($this->number) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_commissions.number', $this->number)
            );
        }

        if ($this->productId) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_commissions.productId', $this->productId)
            );
        }

        if ($this->orderType) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_commissions.orderType', $this->orderType)
            );
        }

        if ($this->commissionStatus) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_commissions.commissionStatus', $this->commissionStatus)
            );
        }

        if ($this->totalPrice !== null) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_commissions.totalPrice', $this->totalPrice)
            );
        }

        if ($this->currency !== null) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_commissions.currency', $this->currency)
            );
        }

        if ($this->stripeId !== null) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_commissions.stripeId', $this->stripeId)
            );
        }

        if ($this->datePaid !== null) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_commissions.datePaid', $this->datePaid)
            );
        }

        if ($this->dateCreated) {
            $this->subQuery->andWhere(Db::parseDateParam('enupalstripe_commissions.dateCreated', $this->dateCreated));
        }


        if ($this->orderBy !== null && empty($this->orderBy) && !$this->structureId && !$this->fixedOrder) {
            $this->orderBy = 'elements.dateCreated desc';
        }

        return parent::beforePrepare();
    }
}
