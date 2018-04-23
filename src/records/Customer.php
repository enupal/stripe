<?php
/**
 * EnupalStripe plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\stripe\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;
use craft\records\Element;

/**
 * Class Customer record.
 */
class Customer extends ActiveRecord
{
    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%enupalstripe_customers}}';
    }
}