<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use craft\base\ElementInterface;
use yii\base\Component;
use Craft;

class Commissions extends Component
{
    /**
     * Returns a Order model if one is found in the database by id
     *
     * @param int $id
     *
     * @return null|ElementInterface
     */
    public function getOrderById(int $id)
    {
        $order = Craft::$app->getElements()->getElementById($id);

        return $order;
    }
}
