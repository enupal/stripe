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

class OrdersQuery extends ElementQuery
{
    // General - Properties
    // =========================================================================
    public $id;
    public $dateCreated;
    public $number;
    public $formId;
    public $email;
    public $stripeTransactionId;
    public $orderStatusId;
    public $totalPrice;
    public $tax;
    public $dateOrdered;

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
    public function email($value)
    {
        $this->email = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @inheritdoc
     */
    public function dateOrdered($value)
    {
        $this->dateOrdered = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDateOrdered()
    {
        return $this->dateOrdered;
    }

    /**
     * @inheritdoc
     */
    public function stripeTransactionId($value)
    {
        $this->stripeTransactionId = $value;
    }

    /**
     * @inheritdoc
     */
    public function getStripeTransactionId()
    {
        return $this->stripeTransactionId;
    }

    /**
     * @inheritdoc
     */
    public function __construct($elementType, array $config = [])
    {
        // Default orderBy
        if (!isset($config['orderBy'])) {
            $config['orderBy'] = 'enupalstripe_orders.dateCreated';
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
        $this->joinElementTable('enupalstripe_orders');

        $this->query->select([
            'enupalstripe_orders.id',
            'enupalstripe_orders.testMode',
            'enupalstripe_orders.number',
            'enupalstripe_orders.currency',
            'enupalstripe_orders.totalPrice',
            'enupalstripe_orders.tax',
            'enupalstripe_orders.discount',
            'enupalstripe_orders.shipping',
            'enupalstripe_orders.formId',
            'enupalstripe_orders.quantity',
            'enupalstripe_orders.stripeTransactionId',
            'enupalstripe_orders.transactionInfo',
            'enupalstripe_orders.email',
            'enupalstripe_orders.firstName',
            'enupalstripe_orders.lastName',
            'enupalstripe_orders.orderStatusId',
            'enupalstripe_orders.addressCity',
            'enupalstripe_orders.addressCountry',
            'enupalstripe_orders.addressState',
            'enupalstripe_orders.addressCountryCode',
            'enupalstripe_orders.addressName',
            'enupalstripe_orders.addressStreet',
            'enupalstripe_orders.addressZip',
            'enupalstripe_orders.variants',
            'enupalstripe_orders.dateOrdered'
        ]);

        if ($this->id) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_orders.id', $this->id)
            );
        }

        if ($this->number) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_orders.number', $this->number)
            );
        }

        if ($this->email) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_orders.email', $this->email)
            );
        }

        if ($this->stripeTransactionId) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_orders.stripeTransactionId', $this->stripeTransactionId)
            );
        }

        if ($this->orderStatusId) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_orders.orderStatusId', $this->orderStatusId)
            );
        }


        if ($this->dateCreated) {
            $this->subQuery->andWhere(Db::parseDateParam('enupalstripe_orders.dateCreated', $this->dateCreated));
        }

        if ($this->dateOrdered) {
            $this->subQuery->andWhere(Db::parseDateParam('enupalstripe_orders.dateOrdered', $this->dateOrdered));
        }

        if ($this->orderBy !== null && empty($this->orderBy) && !$this->structureId && !$this->fixedOrder) {
            $this->orderBy = 'elements.dateCreated desc';
        }

        return parent::beforePrepare();
    }
}
