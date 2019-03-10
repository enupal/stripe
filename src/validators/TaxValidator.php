<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\validators;

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
        if (is_numeric($object->tax)){
            if ($object->enableTaxes && $object->tax) {
                if ($object->tax <= 0 || $object->tax > 100){
                    $this->addError($object, $attribute, Stripe::t('Tax need to have a value between >0 and 100'));
                }
            }
        }else{
            $this->addError($object, $attribute, Stripe::t('Tax need to have a valid number'));
        }
    }
}
