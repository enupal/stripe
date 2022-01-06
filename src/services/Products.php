<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use enupal\stripe\elements\Product as ProductElement;
use yii\base\Component;
use Craft;

class Products extends Component
{
    /**
     * Returns a Price model if one is found in the database by id
     *
     * @param int $id
     *
     * @return null|ProductElement
     */
    public function getProductById(int $id)
    {
        $product = Craft::$app->getElements()->getElementById($id);

        return $product;
    }

    /**
     * @param array $stripeObject
     * @return ProductElement|null
     */
    public function createProduct(array $stripeObject)
    {
        $product = new ProductElement();
        $product->stripeId = $stripeObject['id'];
        $product->stripeObject = json_encode($stripeObject);

        if (!Craft::$app->elements->saveElement($product)) {
            Craft::error('Unable to create new Product: '.json_encode($product->getErrors()), __METHOD__);
            return null;
        }

        Craft::info('Stripe Product Created: '.$product->stripeId , __METHOD__);

        return $product;
    }
}
