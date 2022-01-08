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
     * Returns a Product model if one is found in the database by id
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
     * Returns a Product model if one is found in the database by stripe transaction id
     *
     * @param string $stripeId
     *
     * @return null|ProductElement
     */
    public function getProductByStripeId(string $stripeId)
    {
        $query = ProductElement::find();
        $query->stripeId($stripeId);

        return $query->one();
    }

    /**
     * @param array $stripeObject
     * @return ProductElement|null
     */
    public function createOrUpdateProduct(array $stripeObject)
    {
        $product = $this->getProductByStripeId($stripeObject['id']) ?? new ProductElement();
        $product->stripeId = $stripeObject['id'];
        $product->stripeObject = json_encode($stripeObject);

        if (!Craft::$app->elements->saveElement($product)) {
            Craft::error('Unable to create new Product: '.json_encode($product->getErrors()), __METHOD__);
            return null;
        }

        Craft::info('Stripe Product Updated: '.$product->stripeId , __METHOD__);

        return $product;
    }
}
