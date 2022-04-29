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

class ProductQuery extends ElementQuery
{
    // General - Properties
    // =========================================================================
    public $id;
    public $stripeId;
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
    public function stripeId($value)
    {
        $this->stripeId = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function __construct($elementType, array $config = [])
    {
        // Default orderBy
        if (!isset($config['orderBy'])) {
            $config['orderBy'] = 'enupalstripe_products.dateCreated';
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
        $this->joinElementTable('enupalstripe_products');

        if (is_null($this->query)){
            return false;
        }

        $this->query->select([
            'enupalstripe_products.id',
            'enupalstripe_products.stripeId',
            'enupalstripe_products.stripeObject'
        ]);

        if ($this->id) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_products.id', $this->id)
            );
        }

        if ($this->stripeId) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_products.stripeId', $this->stripeId)
            );
        }

        if ($this->orderBy !== null && empty($this->orderBy) && !$this->structureId && !$this->fixedOrder) {
            $this->orderBy = 'elements.dateCreated desc';
        }

        return parent::beforePrepare();
    }
}
