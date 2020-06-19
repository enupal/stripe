<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use enupal\stripe\elements\Vendor;
use yii\base\Component;
use Craft;

class Vendors extends Component
{
    /**
     * Returns a Vendor model if one is found in the database by id
     *
     * @param int $id
     *
     * @return null|Vendor
     */
    public function getVendorById(int $id)
    {
        $vendor = Craft::$app->getElements()->getElementById($id);

        return $vendor;
    }
}
