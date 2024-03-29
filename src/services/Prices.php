<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use craft\base\Element;
use enupal\stripe\elements\PaymentForm;
use enupal\stripe\elements\Product;
use Stripe\Price;
use enupal\stripe\elements\Price as PriceElement;
use yii\base\Component;
use enupal\stripe\Stripe as StripePlugin;
use Craft;

class Prices extends Component
{
    const PRICE_TYPE_RECURRING = 'recurring';

    /**
     * @param array $lineItem
     * @param boolean $active
     * @return Price
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function createPrice(array $lineItem)
    {
        StripePlugin::$app->settings->initializeStripe();
        return Price::create([
            'unit_amount' => $lineItem['amount'],
            'currency' => $lineItem['currency'],
            'nickname' => $lineItem['description'] ?? "",
            'product_data' => [
                'name' => $lineItem['name']
            ]
        ]);
    }

    /**
     * Returns a Product model if one is found in the database by id
     *
     * @param int $id
     *
     * @return null|PriceElement
     */
    public function getPriceById(int $id)
    {
        $price = Craft::$app->getElements()->getElementById($id);

        return $price;
    }

    /**
     * Returns a Price model if one is found in the database by stripe transaction id
     *
     * @param string $stripeId
     * @param string|null $status
     *
     * @return null|PriceElement
     */
    public function getPriceByStripeId(string $stripeId, $status = Element::STATUS_ENABLED)
    {
        $query = PriceElement::find();
        $query->stripeId($stripeId);
        $query->status($status);

        return $query->one();
    }

    /**
     * @param int $productId Returns a Prices related to a product
     * @param string|null $status
     * @return array|\craft\base\ElementInterface[]|null
     */
    public function getPricesByProductId(int $productId, $status = Element::STATUS_ENABLED)
    {
        $query = PriceElement::find();
        $query->productId($productId);
        $query->status($status);

        return $query->all();
    }

    /**
     * @param array $stripeObject
     * @param Product|null $product
     * @return PriceElement|null
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     */
    public function createOrUpdatePrice(array $stripeObject, ?Product $product = null)
    {
        $product = is_null($product) ? StripePlugin::$app->products->getProductByStripeId($stripeObject['product'], null) : $product;
        if (is_null($product)) {
            Craft::error('Product not found related to price: '.$stripeObject['product'], __METHOD__);
            return null;
        }

        $price = $this->getPriceByStripeId($stripeObject['id'], null) ?? new PriceElement();
        $price->stripeId = $stripeObject['id'];
        $price->stripeObject = json_encode($stripeObject);
        $price->productId = $product->id;
        $price->enabled = (bool)$stripeObject['active'];

        if (!Craft::$app->elements->saveElement($price)) {
            Craft::error('Unable to create new Price: '.json_encode($price->getErrors()), __METHOD__);
            return null;
        }

        Craft::info('Stripe Price Updated: '.$price->stripeId , __METHOD__);

        return $price;
    }

    /**
     * @param Product $product
     * @return void
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     */
    public function syncPricesFromProduct(Product $product)
    {
        StripePlugin::$app->settings->initializeStripe();
        $stripePrices = Price::all(['product' => $product->stripeId]);

        foreach ($stripePrices->autoPagingIterator() as $stripePrice) {
            $price = $this->getPriceByStripeId($stripePrice['id']);
            if (is_null($price)) {
                $toJson = json_encode($stripePrice);
                $toArray = json_decode($toJson, true);
                $this->createOrUpdatePrice($toArray, $product);
            }
        }
    }
}
