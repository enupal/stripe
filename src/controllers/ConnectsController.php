<?php
/**
 * Stripe plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2019 Enupal LLC
 */


namespace enupal\stripe\controllers;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller as BaseController;
use enupal\stripe\elements\Connect as ConnectElement;
use enupal\stripe\elements\Vendor;
use enupal\stripe\Stripe;
use yii\web\NotFoundHttpException;

class ConnectsController extends BaseController
{
    /**
     * Save a Connect
     *
     * @return null|\yii\web\Response
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionSaveConnect()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $connect = new ConnectElement;

        $connectId = $request->getBodyParam('connectId');

        if ($connectId) {
            $connect = Stripe::$app->connects->getConnectById($connectId);
        }

        $connect = Stripe::$app->connects->populateConnectFromPost($connect);

        // Save it
        if (!Craft::$app->elements->saveElement($connect)) {
            Craft::$app->getSession()->setError(Craft::t('enupal-stripe','Couldn’t save connect'));

            Craft::$app->getUrlManager()->setRouteParams([
                    'connect' => $connect
                ]
            );

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('enupal-stripe','Connect saved.'));

        return $this->redirectToPostedUrl($connect);
    }

    /**
     * Edit a Connect
     *
     * @param int|null $connectId The connect ID, if editing an existing connect.
     * @param ConnectElement|null $connect   The connect send back by setRouteParams if any errors on saveConnect
     *
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     * @throws \Exception
     * @throws \Throwable
     */
    public function actionEditConnect(int $connectId = null, ConnectElement $connect = null)
    {
        // Immediately create a new Connect
        if ($connectId === null) {
            $request = Craft::$app->getRequest();
            $productType = $request->getRequiredBodyParam("productType");
            $connect = Stripe::$app->connects->createNewConnect($productType);

            if ($connect->id) {
                $url = UrlHelper::cpUrl('enupal-stripe/connects/edit/'.$connect->id);
                return $this->redirect($url);
            } else {
                $errors = $connect->getErrors();
                throw new \Exception(Craft::t('enupal-stripe','Error creating the Connect '.json_encode($errors)));
            }
        } else {
            if ($connectId !== null) {
                if ($connect === null) {
                    // Get the connect
                    $connect = Stripe::$app->connects->getConnectById($connectId);

                    if (!$connect) {
                        throw new NotFoundHttpException(Craft::t('enupal-stripe','Connect not found'));
                    }
                }
            }
        }

        if (is_string($connect->products)){
            $connect->products = json_decode($connect->products, true);
        }

        $products = [];
        if ($connect->products) {
            foreach ($connect->products as $product) {
                $product = Craft::$app->getElements()->getElementById($product, $connect->productType);
                if ($product !== null){
                    $products[] = $product;
                }
            }
        }

        $variables['connectId'] = $connectId;
        $variables['connect'] = $connect;

        // Set the "Continue Editing" URL
        $variables['continueEditingUrl'] = 'enupal-stripe/connects/edit/{id}';
        $settings = Stripe::$app->settings->getSettings();
        $variables['settings'] = $settings;
        $variables['productTypeName'] = $connect->productType::displayName();
        $variables['products'] = $products;
        $variables['vendorElementType'] = Vendor::class;

        return $this->renderTemplate('enupal-stripe/connects/_edit', $variables);
    }

    /**
     * Delete a Connect.
     *
     * @return \yii\web\Response
     * @throws \Throwable
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionDeleteConnect()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $connectId = $request->getRequiredBodyParam('connectId');
        $connect = Stripe::$app->connects->getConnectById($connectId);

        // @TODO - handle errors
        Stripe::$app->connects->deleteConnect($connect);

        Craft::$app->getSession()->setNotice(Craft::t('enupal-stripe','Connect deleted.'));

        return $this->redirectToPostedUrl($connect);
    }
}
