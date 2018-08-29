<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\models;

use craft\base\Model;
use Craft;
use craft\validators\UniqueValidator;
use enupal\stripe\records\OrderStatus as OrderStatusRecord;

class OrderStatus extends Model
{
    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $handle;

    /**
     * @var string
     */
    public $color = 'green';

    /**
     * @var int
     */
    public $sortOrder;

    /**
     * @var int
     */
    public $isDefault;

    /**
     * @var string
     */
    public $dateCreated;

    /**
     * @var string
     */
    public $dateUpdated;

    /**
     * @var string
     */
    public $uid;

    /**
     * Use the translated section name as the string representation.
     *
     * @inheritdoc
     */
    public function __toString()
    {
        $name = Craft::t('enupal-stripe', $this->name);

        return (string)$name;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'handle'], 'required'],
            [['name', 'handle'], 'string', 'max' => 255],
            [['handle'], UniqueValidator::class, 'targetClass' => OrderStatusRecord::class],
        ];
    }
}