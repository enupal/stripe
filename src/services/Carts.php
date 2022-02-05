<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use Craft;
use enupal\stripe\elements\Cart;
use enupal\stripe\records\Cart as CartRecord;
use enupal\stripe\exceptions\CartItemException;
use enupal\stripe\Stripe as StripePlugin;
use yii\base\Component;

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
            // @todo validate if there is already a cart
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
     * @param string $cartStatus
     * @return array|Cart|null
     */
    public function getCartByUserId(int $userId, string $cartStatus = Cart::STATUS_PENDING)
    {
        $query = Cart::find();
        $query->userId = $userId;
        $query->cartStatus = $cartStatus;

        return $query->one();
    }

    /**
     * @param string $number
     * @param string $cartStatus
     * @return array|Cart|null
     */
    public function getCartByNumber(string $number, string $cartStatus = Cart::STATUS_PENDING)
    {
        $query = Cart::find();
        $query->number = $number;
        $query->cartStatus = $cartStatus;

        return $query->one();
    }

    /**
     * @param Cart $cart
     * @param array $postData
     * @return void
     * @throws CartItemException
     * @throws \Throwable
     * @throws \craft\errors\MissingComponentException
     */
    public function addCart(Cart $cart, array $postData): void
    {
        $cart->cartMetadata = $postData['metadata'] ?? null;
        $items = [];
        $postItems = $postData['items'] ?? [];
        $totalPrice = 0;
        $currency = null;

        foreach ($postItems as $postItem) {
            $priceId = $postItem['price'] ?? null;
            $quantity = $postItem['quantity'] ?? 0;
            $quantity = intval($quantity);
            $description = $postItem['description'] ?? null;

            if ($quantity <= 0 || empty($priceId)) {
                continue;
            }

            $price = StripePlugin::$app->prices->getPriceByStripeId($priceId);

            if (is_null($price)) {
                continue;
            }
            $priceObject = $price->getStripeObject();
            $currency = $priceObject->currency;
            $totalPrice += ($priceObject->unit_amount * $quantity);

            // if item is already in the cart, add the quantity
            $quantity = in_array($priceId, $items) ? ($items[$priceId]['quantity'] + $quantity) : $quantity;

            $items[$priceId] = [
                'price' => $priceId,
                'quantity' => $quantity
            ];

            if (!is_null($description)) {
                $items[$priceId]['description'] = $description;
            }
        }

        if (empty($items)) {
            throw new CartItemException("Can't find items in the cart");
        }

        $cart->items = $items;
        $cart->currency = $currency;
        $cart->totalPrice = StripePlugin::$app->orders->convertFromCents($totalPrice, $currency);
        $cart->itemCount = count($items);
        $cart->number = $cart->number ?? StripePlugin::$app->orders->getRandomStr();
        $cart->cartStatus = Cart::STATUS_PENDING;

        $user = Craft::$app->getUser()->getIdentity();
        if (!is_null($user)) {
            $cart->userId = $user->id;
        }

        $this->saveCart($cart);

        $this->setSessionCart($cart->number);
    }

    /**
     * @param $cart Cart
     *
     * @throws \Exception
     * @return bool
     * @throws \Throwable
     */
    public function saveCart(Cart $cart): bool
    {
        if ($cart->id) {
            $cartRecord = CartRecord::findOne($cart->id);

            if (!$cartRecord) {
                throw new \Exception(StripePlugin::t('No Cart exists with the ID “{id}”', ['id' => $cart->id]));
            }
        }

        if (!$cart->validate()) {
            return false;
        }

        $transaction = Craft::$app->db->beginTransaction();

        try {
            $result = Craft::$app->elements->saveElement($cart);

            if ($result) {
                $transaction->commit();
            }
        } catch (\Exception $e) {
            $transaction->rollback();

            throw $e;
        }

        return true;
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
