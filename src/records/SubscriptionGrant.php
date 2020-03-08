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
 * @property int    $id    ID
 * @property string $name  Name
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