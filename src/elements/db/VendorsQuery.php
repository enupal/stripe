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

class VendorsQuery extends ElementQuery
{
    // General - Properties
    // =========================================================================
    public $id;
    public $userId;
    public $stripeId;
    public $paymentType;
    public $skipAdminReview;
    public $vendorRate;

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
    public function userId($value)
    {
        $this->userId = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getUserId()
    {
        return $this->userId;
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
    public function skipAdminReview($value)
    {
        $this->skipAdminReview = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSkipAdminReview()
    {
        return $this->skipAdminReview;
    }

    /**
     * @inheritdoc
     */
    public function vendorRate($value)
    {
        $this->vendorRate = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getVendorRate()
    {
        return $this->vendorRate;
    }

    /**
     * @inheritdoc
     */
    public function __construct($elementType, array $config = [])
    {
        // Default orderBy
        if (!isset($config['orderBy'])) {
            $config['orderBy'] = 'enupalstripe_vendors.dateCreated';
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
        $this->joinElementTable('enupalstripe_vendors');

        if (is_null($this->query)){
            return false;
        }

        $this->query->select([
            'enupalstripe_vendors.id',
            'enupalstripe_vendors.userId',
            'enupalstripe_vendors.stripeId',
            'enupalstripe_vendors.paymentType',
            'enupalstripe_vendors.skipAdminReview',
            'enupalstripe_vendors.vendorRate'
        ]);

        if ($this->id) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_vendors.id', $this->id)
            );
        }

        if ($this->userId) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_vendors.userId', $this->userId)
            );
        }

        if ($this->stripeId) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_vendors.stripeId', $this->stripeId)
            );
        }

        if ($this->paymentType) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_vendors.paymentType', $this->paymentType)
            );
        }

        if ($this->skipAdminReview) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_vendors.skipAdminReview', $this->skipAdminReview)
            );
        }

        if ($this->vendorRate !== null) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalstripe_vendors.vendorRate', $this->vendorRate)
            );
        }

        if ($this->dateCreated) {
            $this->subQuery->andWhere(Db::parseDateParam('enupalstripe_vendors.dateCreated', $this->dateCreated));
        }


        if ($this->orderBy !== null && empty($this->orderBy) && !$this->structureId && !$this->fixedOrder) {
            $this->orderBy = 'elements.dateCreated desc';
        }

        return parent::beforePrepare();
    }
}
