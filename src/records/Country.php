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
 * Country record.
 *
 * @property int $id
 * @property string $iso
 * @property string $name
 * @property bool $isStateRequired
 */
class Country extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%enupalstripe_countries}}';
    }
}
