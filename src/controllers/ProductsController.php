<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\controllers;

use Craft;
use craft\elements\User;
use craft\web\Controller as BaseController;
use enupal\stripe\elements\Vendor;
use enupal\stripe\jobs\SyncVendors;
use enupal\stripe\Stripe;
use yii\web\NotFoundHttpException;

class ProductsController extends BaseController
{
    /**
     * Edit a Product.
     *
     * @param int|null $productId The product's ID, if editing an existing product.
     *
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     * @throws \Exception
     * @throws \Throwable
     */
    public function actionEditProduct(int $productId = null)
    {
        $product = Stripe::$app->products->getProductById($productId);

        if (!$product) {
            throw new NotFoundHttpException(Stripe::t('Product not found'));
        }

        $variables['productId'] = $productId;
        $variables['product'] = $product;
        $variables['stripeObject'] = $product->getStripeObject();
        $variables['prices'] = $product->getPrices();

        $variables['settings'] = Stripe::$app->settings->getSettings();

        return $this->renderTemplate('enupal-stripe/products/_edit', $variables);
    }
}
