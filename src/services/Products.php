<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use craft\base\Element;
use enupal\stripe\elements\Product as ProductElement;
use enupal\stripe\Stripe as StripePlugin;
use Stripe\Exception\ApiErrorException;
use Stripe\Product;
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
     * @param string|null $status
     *
     * @return null|ProductElement
     */
    public function getProductByStripeId(string $stripeId, $status = Element::STATUS_ENABLED)
    {
        $query = ProductElement::find();
        $query->stripeId($stripeId);
        $query->status($status);

        return $query->one();
    }

    /**
     * @param ProductElement[] $products
     * @return bool
     * @throws \Exception
     */
    public function disableProducts(array $products)
    {
        StripePlugin::$app->settings->initializeStripe();

        foreach ($products as $product) {
            try {
                Product::update($product->stripeId, ['active' => false]);
            } catch (\Exception $e) {
                Craft::error('Something went wrong updating the product: '.$e->getMessage());
                return false;
            }
        }

        return true;
    }

    /**
     * @param ProductElement[] $products
     * @return bool
     * @throws \Exception
     */
    public function enableProducts(array $products)
    {
        StripePlugin::$app->settings->initializeStripe();

        foreach ($products as $product) {
            try {
                Product::update($product->stripeId, ['active' => true]);
            } catch (\Exception $e) {
                Craft::error('Something went wrong updating the product: '.$e->getMessage());
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $stripeObject
     * @return ProductElement|null
     */
    public function createOrUpdateProduct(array $stripeObject)
    {
        $product = $this->getProductByStripeId($stripeObject['id'], null) ?? new ProductElement();
        $product->stripeId = $stripeObject['id'];
        $product->stripeObject = json_encode($stripeObject);
        $product->enabled = (bool)$stripeObject['active'];

        if (!Craft::$app->elements->saveElement($product)) {
            Craft::error('Unable to create new Product: '.json_encode($product->getErrors()), __METHOD__);
            return null;
        }

        Craft::info('Stripe Product Updated: '.$product->stripeId , __METHOD__);

        return $product;
    }
}
