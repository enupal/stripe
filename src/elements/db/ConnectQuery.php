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

class ConnectQuery extends ElementQuery
{
    // General - Properties
    // =========================================================================
    public $id;
    public $dateCreated;
    public $vendorId;
    public $products;
    public $productType;
    public $allProducts;
    public $rate;

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
    public function vendorId($value)
    {
        $this->vendorId = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getVendorId()
    {
        return $this->vendorId;
    }

    /**
     * @inheritdoc
     */
    public function products($value)
    {
        $this->products = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * @inheritdoc
     */
    public function productType($value)
    {
        $this->productType = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getProductType()
    {
        return $this->productType;
    }

    /**
     * @inheritdoc
     */
    public function allProducts($value)
    {
        $this->allProducts = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAllProducts()
    {
        return $this->allProducts;
    }

    /**
     * @inheritdoc
     */
    public function rate($value)
    {
        $this->rate = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * @inheritdoc
     */
    public function __construct($elementType, array $config = [])
    {
        // Default orderBy
        if (!isset($config['orderBy'])) {
            $config['orderBy'] = 'enupalstripe_connect.dateCreated';
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
        $this->joinElementTable('enupalstripe_connect');

        if (is_null($this->query)){
            return false;
        }

        $this->query->select([
            'enupalstripe_connect.id',
            'enupalstripe_connect.vendorId',
            'enupalstripe_connect.products',
            'enupalstripe_connect.productType',
            'enupalstripe_connect.allProducts',
            'enupalstripe_connect.rate'
        ]);

        if ($this->id) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_connect.id', $this->id)
            );
        }

        if ($this->vendorId) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_connect.vendorId', $this->vendorId)
            );
        }

        if ($this->products) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_connect.products', $this->products)
            );
        }

        if ($this->productType) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_connect.productType', $this->productType)
            );
        }

        if ($this->allProducts) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_connect.allProducts', $this->allProducts)
            );
        }

        if ($this->rate !== null) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_connect.rate', $this->rate)
            );
        }

        if ($this->dateCreated) {
            $this->subQuery->andWhere(Db::parseDateParam('enupalstripe_connect.dateCreated', $this->dateCreated));
        }

        if ($this->orderBy !== null && empty($this->orderBy) && !$this->structureId && !$this->fixedOrder) {
            $this->orderBy = 'elements.dateCreated desc';
        }

        return parent::beforePrepare();
    }
}
