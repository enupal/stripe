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

class PriceQuery extends ElementQuery
{
    // General - Properties
    // =========================================================================
    public $id;
    public $stripeId;
    public $productId;
    public $stripeObject;

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
    public function __construct($elementType, array $config = [])
    {
        // Default orderBy
        if (!isset($config['orderBy'])) {
            $config['orderBy'] = 'enupalstripe_prices.created';
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
        $this->joinElementTable('enupalstripe_prices');

        if (is_null($this->query)){
            return false;
        }

        $this->query->select([
            'enupalstripe_prices.id',
            'enupalstripe_prices.productId',
            'enupalstripe_prices.stripeId',
            'enupalstripe_prices.stripeObject'
        ]);

        if ($this->id) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_prices.id', $this->id)
            );
        }

        if ($this->stripeId) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_prices.stripeId', $this->stripeId)
            );
        }

        if ($this->productId) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_prices.productId', $this->productId)
            );
        }

        if ($this->orderBy !== null && empty($this->orderBy) && !$this->structureId && !$this->fixedOrder) {
            $this->orderBy = 'elements.created desc';
        }

        return parent::beforePrepare();
    }
}
