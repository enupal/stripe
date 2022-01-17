<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use Craft;
use craft\db\Query;
use craft\helpers\Db;
use enupal\stripe\elements\Cart;
use enupal\stripe\exceptions\CartItemException;
use enupal\stripe\Stripe as StripePlugin;
use yii\base\Component;
use yii\db\Exception;

class Carts extends Component
{
    const SESSION_CART_NAME = 'enupal_stripe_cart';

    /**
     * @return array|Cart|null
     * @throws \craft\errors\MissingComponentException
     */
    public function getCart()
    {
        $cart = null;
        $user = Craft::$app->getUser()->getIdentity();

        // First try from the Database
        if ($user) {
            $cart = $this->getCartByUserId($user->getId());
            // update the cart session with the database
            if (!is_null($cart)) {
                $this->setSessionCart($cart->number);
            }
        }

        // Secondly try from the session
        if (is_null($cart)) {
            $cart = $this->getSessionCart();
        }

        return $cart;
    }

    /**
     * @param int $userId
     * @param string $status
     * @return array|Cart|null
     */
    public function getCartByUserId(int $userId, string $status = Cart::STATUS_PENDING)
    {
        $query = Cart::find();
        $query->userId = $userId;
        $query->status = $status;

        return $query->one();
    }

    /**
     * @param string $number
     * @param string $status
     * @return array|Cart|null
     */
    public function getCartByNumber(string $number, string $status = Cart::STATUS_PENDING)
    {
        $query = Cart::find();
        $query->number = $number;
        $query->status = $status;

        return $query->one();
    }

    public function populateCart(Cart $cart, array $postData)
    {
        $cart->cartMetadata = $postData['metadata'] ?? null;
        $items = [];
        $postItems = $postData['items'] ?? [];

        foreach ($postItems as $postItem) {
            $priceId = $postItem['price'] ?? null;
            $quantity = $postItem['quantity'] ?? null;
            $description = $postItem['description'] ?? null;
            $totalPrice = 0;

            if (is_int($quantity) && $quantity > 0 && !empty($priceId)) {
                continue;
            }

            $price = StripePlugin::$app->prices->getPriceByStripeId($priceId);

            if (is_null($price)) {
                continue;
            }

            //@todo get the price and calculate totalPrice

            // if item is already in the cart, add the quantity
            if (in_array($priceId, $items)) {
                $items[$priceId]['quantity'] += $quantity;
            } else {
                $items[$priceId] = [
                    'price' => $priceId,
                    'quantity' => $quantity,
                    'description' => $description
                ];
            }
        }

        if (empty($items)) {
            throw new CartItemException("Can't find items in the cart");
        }

        $cart->items = $items;
        $cart->itemCount = count($items);
    }

    /**
     * @return array|Cart|null
     * @throws \craft\errors\MissingComponentException
     */
    private function getSessionCart()
    {
        $session = Craft::$app->getSession();
        $cartNumber = $session->get(self::SESSION_CART_NAME);
        $cart = null;

        if ($cartNumber) {
            $cart = $this->getCartByNumber($cartNumber);
        }

        return $cart;
    }

    /**
     * @param string $number
     * @return void
     * @throws \craft\errors\MissingComponentException
     */
    private function setSessionCart(string $number)
    {
        $session = Craft::$app->getSession();
        $session->set(self::SESSION_CART_NAME, $number);
    }
}
