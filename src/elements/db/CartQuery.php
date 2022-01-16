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

class CartQuery extends ElementQuery
{
    // General - Properties
    // =========================================================================
    public $number;
    public $stripeId;
    public $totalPrice;
    public $itemCount;
    public $currency;
    public $items;
    public $userEmail;
    public $userId;
    public $status;

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
    public function stripeId($value)
    {
        $this->stripeId = $value;

        return $this;
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
    public function __construct($elementType, array $config = [])
    {
        // Default orderBy
        if (!isset($config['orderBy'])) {
            $config['orderBy'] = 'enupalstripe_carts.dateCreated';
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
        $this->joinElementTable('enupalstripe_carts');

        if (is_null($this->query)){
            return false;
        }

        $this->query->select([
            'enupalstripe_carts.id',
            'enupalstripe_carts.number',
            'enupalstripe_carts.stripeId',
            'enupalstripe_carts.items',
            'enupalstripe_carts.itemCount',
            'enupalstripe_carts.totalPrice',
            'enupalstripe_carts.currency',
            'enupalstripe_carts.userId',
            'enupalstripe_carts.userEmail',
            'enupalstripe_carts.status'
        ]);

        if ($this->id) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_carts.id', $this->id)
            );
        }

        if ($this->stripeId) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_carts.stripeId', $this->stripeId)
            );
        }

        if ($this->number) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_carts.number', $this->number)
            );
        }

        if ($this->orderBy !== null && empty($this->orderBy) && !$this->structureId && !$this->fixedOrder) {
            $this->orderBy = 'elements.dateCreated desc';
        }

        return parent::beforePrepare();
    }
}
