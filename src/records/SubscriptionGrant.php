<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\records;

use craft\db\ActiveRecord;

/**
 * Class SubscriptionGrant record.
 *
 * @property int    $id
 * @property string $name
 * @property string $planName
 */
class SubscriptionGrant extends ActiveRecord
{
    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%enupalstripe_subscriptiongrants}}';
    }
}