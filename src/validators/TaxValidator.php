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

class TaxValidator extends Validator
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
        if ($object->taxType == DiscountType::RATE && $object->tax) {
            if ($object->tax <= 0 || $object->tax > 100){
                $this->addError($object, $attribute, Stripe::t('Tax need to have a value between >0 and 100'));
            }
        }

        if ($object->taxType == DiscountType::AMOUNT && $object->tax) {
            if ($object->tax < 0){
                $this->addError($object, $attribute, Stripe::t('Tax amount should be > 0'));
            }
        }
    }
}
