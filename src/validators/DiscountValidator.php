<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\validators;

use enupal\stripe\enums\DiscountType;
use yii\validators\Validator;
use enupal\stripe\Stripe;

class DiscountValidator extends Validator
{
    public $skipOnEmpty = false;

    /**
     * Ftp validation
     *
     * @param $object
     * @param $attribute
     */
    public function validateAttribute($object, $attribute)
    {
        if ($object->discountType == DiscountType::RATE && $object->discount) {
            if ($object->discount <= 0 || $object->discount > 100){
                $this->addError($object, $attribute, Stripe::t('Discount need to have a value between >0 and 100'));
            }
        }

        if ($object->discountType == DiscountType::AMOUNT && $object->discount) {
            if ($object->discount < 0){
                $this->addError($object, $attribute, Stripe::t('Discount amount should be > 0'));
            }
        }
    }
}
