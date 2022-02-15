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
use enupal\stripe\contracts\HttpStatus;

class Carts extends Component implements HttpStatus
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
     * @param int $id
     * @return array|Cart|null
     */
    public function getCartById(int $id)
    {
        $query = Cart::find();
        $query->id = $id;

        return $query->one();
    }

    /**
     * @param Cart $cart
     * @return void
     * @throws \Throwable
     * @throws \craft\errors\MissingComponentException
     */
    public function clearCart(Cart $cart)
    {
        $this->processCart($cart, []);
    }

    /**
     * @param Cart $cart
     * @return \Stripe\Checkout\Session
     * @throws CartItemException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \yii\base\Exception
     */
    public function checkoutCart(Cart $cart)
    {
        if (empty($cart->getItems()) || empty($cart->id)) {
            throw new CartItemException(
                Craft::t("site", "Cart is empty"),
                self::BAD_REQUEST
            );
        }

        return StripePlugin::$app->checkout->createCartCheckoutSession($cart);
    }

    /**
     * @param Cart $cart
     * @return void
     * @throws \Throwable
     */
    public function setCartCompleted(Cart $cart)
    {
        $cart->cartStatus = Cart::STATUS_COMPLETED;

        $this->saveCart($cart);

        $this->removeSessionCart();
    }

    /**
     * @param Cart $cart
     * @param array $postData
     * @param bool $isUpdate If disabled will override the current items
     * @return void
     * @throws CartItemException
     * @throws \Throwable
     * @throws \craft\errors\MissingComponentException
     */
    public function addOrUpdateCart(Cart $cart, array $postData, bool $isUpdate = false): void
    {
        $cart->cartMetadata = $postData['metadata'] ?? null;
        $items = $cart->getItems();
        $postItems = $isUpdate ? $postData['updates'] ?? [] : $postData['items'] ?? [];

        foreach ($postItems as $key => $postItem) {
            $priceId = $isUpdate ? $key : $postItem['price'] ?? "";
            $quantity = $postItem['quantity'] ?? 0;
            $quantity = intval($quantity);
            $description = $postItem['description'] ?? null;

            $price = StripePlugin::$app->prices->getPriceByStripeId($priceId);

            if (is_null($price)) {
                throw new CartItemException(
                    Craft::t("site", "Cannot find price: ".$priceId),
                    self::BAD_REQUEST
                );
            }
            // if item is already in the cart, add the quantity
            $items = $this->addOrUpdateItem($items, $priceId, $quantity, $isUpdate, $description);
        }

        $this->processCart($cart, $items);
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
     * @param Cart $cart
     * @param array $items
     * @return void
     * @throws CartItemException
     * @throws \Throwable
     * @throws \craft\errors\MissingComponentException
     */
    private function processCart(Cart $cart, array $items)
    {
        $this->populateCart($cart, $items);
        if (!$this->saveCart($cart)) {
            throw new CartItemException(
                Craft::t("site", json_encode($cart->getErrors())),
                self::INTERNAL_SERVER_ERROR
            );
        }
        $this->setSessionCart($cart->number);
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
     * @param Cart $cart
     * @param array $items
     * @param string $status
     * @return void
     * @throws \Exception
     */
    private function populateCart(Cart $cart, array $items, string $status = Cart::STATUS_PENDING)
    {
        $cart->items = $items;
        $cart->currency = $this->getCartCurrency($cart);
        $cart->itemCount = $this->getCartItemCount($cart);
        $cart->totalPrice = StripePlugin::$app->orders->convertFromCents($this->getCartTotalPrice($cart), $cart->currency);
        $cart->number = $cart->number ?? StripePlugin::$app->orders->getRandomStr();
        $cart->cartStatus = $status;

        $user = Craft::$app->getUser()->getIdentity();
        if (!is_null($user)) {
            $cart->userId = $user->id;
        }
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

    /**
     * @return void
     * @throws \craft\errors\MissingComponentException
     */
    public function removeSessionCart()
    {
        $session = Craft::$app->getSession();
        $session->remove(self::SESSION_CART_NAME);
    }

    /**
     * @param Cart $cart
     * @return string
     */
    private function getCartCurrency(Cart $cart)
    {
        $items = $cart->getItems();

        foreach ($items as $item) {
            $priceId = $item['price'] ?? null;
            $price = StripePlugin::$app->prices->getPriceByStripeId($priceId);

            if (isset($price->getStripeObject()->currency)) {
                return $price->getStripeObject()->currency;
            }
        }

        return 'usd';
    }

    /**
     * @param Cart $cart
     * @return float|int
     */
    private function getCartTotalPrice(Cart $cart)
    {
        $indexesToDelete = [];
        $items = $cart->getItems();
        $totalPrice = 0;

        foreach ($items as $index => $item) {
            $priceId = $item['price'] ?? null;
            $quantity = $item['quantity'] ?? 0;
            $price = StripePlugin::$app->prices->getPriceByStripeId($priceId);

            if (is_null($price)) {
                $indexesToDelete[] = $index;
                continue;
            }

            $priceObject = $price->getStripeObject();
            $totalPrice += ($priceObject->unit_amount * $quantity);
        }

        foreach ($indexesToDelete as $index) {
            Craft::warning("Removing {$items[$index]['price']} as is not synced with database", __METHOD__);
            unset($items[$index]);
        }

        $cart->items = $items;

        return $totalPrice;
    }

    /**
     * @param Cart $cart
     * @return int|mixed
     */
    private function getCartItemCount(Cart $cart)
    {
        $items = $cart->getItems();
        $quantity = 0;
        foreach ($items as $item) {
            $itemQuantity = $item['quantity'] ?? 0;
            $quantity += $itemQuantity;
        }

        return $quantity;
    }

    private function addOrUpdateItem($items, $priceId, int $quantity, $isUpdate, $description = null)
    {
        $priceIndex = null;
        $removeItem = $quantity <= 0;

        foreach ($items as $index => $item) {
            if ($priceId === $item['price']) {
                if (!$isUpdate) {
                    $quantity += $item['quantity'];
                }
                $priceIndex = $index;
                break;
            }
        }

        $priceToAdd = [
            'price' => $priceId,
            'quantity' => $quantity
        ];

        if (!is_null($description)) {
            $priceToAdd['description'] = $description;
        }


        if (is_null($priceIndex) && !$removeItem) {
            $items[] = $priceToAdd;
        }else {
            $items[$priceIndex] = $priceToAdd;

            if ($removeItem) {
                unset($items[$priceIndex]);
            }
        }

        return $items;
    }
}
