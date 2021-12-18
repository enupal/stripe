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
    public $active;
    public $description;
    public $metadata;
    public $name;
    public $created;
    public $images;
    public $packageDimensions;
    public $shippable;
    public $statementDescriptor;
    public $taxCode;
    public $unitLabel;
    public $url;

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
            $config['orderBy'] = 'enupalstripe_product.created';
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
        $this->joinElementTable('enupalstripe_product');

        if (is_null($this->query)){
            return false;
        }

        $this->query->select([
            'enupalstripe_product.id',
            'enupalstripe_product.stripeId',
            'enupalstripe_product.name',
            'enupalstripe_product.active',
            'enupalstripe_product.description',
            'enupalstripe_product.metadata',
            'enupalstripe_product.images',
            'enupalstripe_product.packageDimensions',
            'enupalstripe_product.shippable',
            'enupalstripe_product.statementDescriptor',
            'enupalstripe_product.taxCode',
            'enupalstripe_product.unitLabel',
            'enupalstripe_product.url'
        ]);

        if ($this->id) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_product.id', $this->id)
            );
        }

        if ($this->name) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_product.name', $this->name)
            );
        }

        if ($this->active) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_product.active', $this->active)
            );
        }

        if ($this->created) {
            $this->subQuery->andWhere(Db::parseDateParam('enupalstripe_product.created', $this->created));
        }

        if ($this->orderBy !== null && empty($this->orderBy) && !$this->structureId && !$this->fixedOrder) {
            $this->orderBy = 'elements.created desc';
        }

        return parent::beforePrepare();
    }
}
