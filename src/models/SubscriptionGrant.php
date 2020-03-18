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
use enupal\stripe\records\SubscriptionGrant as SubscriptionGrantRecord;

class SubscriptionGrant extends Model
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
    public $planName;

    /**
     * @var string
     */
    public $handle;

    /**
     * @var string
     */
    public $planId;

    /**
     * @var int
     */
    public $userGroupId;

    /**
     * @var int
     */
    public $sortOrder;

    /**
     * @var bool
     */
    public $enabled = 1;

    /**
     * @var bool
     */
    public $removeWhenCanceled = 1;

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
            [['planId'], 'required'],
            [['handle'], UniqueValidator::class, 'targetClass' => SubscriptionGrantRecord::class],
        ];
    }
}