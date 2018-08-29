<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\records;

use craft\db\ActiveRecord;
use craft\helpers\UrlHelper;

/**
 * Class EntryStatus record.
 *
 * @property int    $id    ID
 * @property string $name  Name
 */
class OrderStatus extends ActiveRecord
{
    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%enupalstripe_orderstatuses}}';
    }

    /**
     * @return string
     */
    public function getCpEditUrl()
    {
        return UrlHelper::cpUrl('enupal-stripe/settings/orders-statuses/'.$this->id);
    }

    /**
     * @return string
     */
    public function htmlLabel()
    {
        return '<span class="enupalStripeStatusLabel"><span class="status '.$this->color.'"></span> '.$this->name.'</span>';
    }

}