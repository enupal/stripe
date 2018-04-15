<?php
/**
 * EnupalStripe plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\stripe\controllers;

use Craft;
use craft\web\Controller as BaseController;

use enupal\stripe\contracts\PaypalIPN;
use enupal\stripe\elements\Order;
use enupal\stripe\enums\OrderStatus;
use enupal\stripe\Stripe;

class StripeController extends BaseController
{
    // Disable CSRF validation for the entire controller
    public $enableCsrfValidation = false;

    protected $allowAnonymous = ['actionIpn'];

    /**
     * @return \yii\web\Response
     * @throws \Exception
     * @throws \Throwable
     */
    public function actionIpn()
    {
        if (isset($_POST)) {
            $settings = Stripe::$app->settings->getSettings();
            $ipn = new PaypalIPN();
            $ipn->usePHPCerts();

            if ($settings->testMode){
                $ipn->useSandbox();
            }

            if ($ipn->verifyIPN()) {
                $order = Stripe::$app->orders->populateOrder();
                $button = Stripe::$app->buttons->getButtonBySku($this->getValue('item_number'));

                if ($button){
                    $order->buttonId = $button->id;
                }

                // Stock
                $saveButton = false;
                if (!$button->hasUnlimitedStock && (int)$button->quantity > 0){
                    $button->quantity -= $order->quantity;
                    $saveButton = true;
                }

                $receiverEmail = $this->getValue('receiver_email');
                $receiverId = $this->getValue('receiver_id');

                $result = ($settings->liveAccount ==  $receiverEmail || $settings->liveAccount != $receiverId);

                if ($order->testMode){
                    $result = ($settings->sandboxAccount ==  $receiverEmail || $settings->sandboxAccount == $receiverId);
                }

                if (!$result){
                    Craft::error('PayPal receiverEmail does not match', __METHOD__);
                    return $this->asJson(['success' => 'false']);
                }

                if (!Stripe::$app->orders->saveOrder($order)){
                    Craft::error('Something went wrong saving the order: '.json_encode($order->getErrors()), __METHOD__);
                    return $this->asJson(['success' => 'false']);
                }
                // Let's update the stock
                if ($saveButton){
                    if (!Stripe::$app->buttons->saveButton($button)){
                        Craft::error('Something went wrong updating the button stock: '.json_encode($button->getErrors()), __METHOD__);
                        return $this->asJson(['success' => 'false']);
                    }
                }
            }else{
                Craft::error('PayPal fail to verifyIPN', __METHOD__);
                return $this->asJson(['success' => 'false']);
            }
        }

        return $this->asJson(['success' => 'true']);
    }

    /**
     * @param $key
     *
     * @return string|null
     */
    private function getValue($key)
    {
        if (!isset($_POST[$key])){
            return null;
        }

        return $_POST[$key];
    }

}
