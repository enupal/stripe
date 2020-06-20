<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\controllers;

use Craft;
use craft\elements\Asset;
use craft\elements\User;
use craft\helpers\UrlHelper;
use craft\web\Controller as BaseController;
use enupal\stripe\elements\Vendor;
use enupal\stripe\Stripe;
use Psy\Util\Str;
use yii\web\NotFoundHttpException;

class VendorsController extends BaseController
{
    /**
     * Save a Vendor
     *
     * @return null|\yii\web\Response
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionSaveVendor()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $vendor = new Vendor;

        $vendorId = $request->getBodyParam('vendorId');

        if ($vendorId) {
            $vendor = Stripe::$app->vendors->getVendorById($vendorId);
        }

        $vendor = Stripe::$app->vendors->populateVendorFromPost($vendor);

        // Save it
        if (!Craft::$app->elements->saveElement($vendor)) {
            Craft::$app->getSession()->setError(Stripe::t('Couldn’t save vendor'));

            Craft::$app->getUrlManager()->setRouteParams([
                    'vendor' => $vendor
                ]
            );

            return null;
        }

        Craft::$app->getSession()->setNotice(Stripe::t('Vendor saved.'));

        return $this->redirectToPostedUrl($vendor);
    }

    /**
     * Edit a Vendor.
     *
     * @param int|null    $vendorId The vendor's ID, if editing an existing vendor.
     * @param Vendor|null $vendor   The vendor send back by setRouteParams if any errors on saveVendor
     *
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     * @throws \Exception
     * @throws \Throwable
     */
    public function actionEditVendor(int $vendorId = null, Vendor $vendor = null)
    {
        if ($vendorId === null && $vendor === null) {
            $vendor = new Vendor();
        } else {
            if ($vendorId !== null) {
                if ($vendor === null) {
                    // Get the vendor
                    $vendor = Stripe::$app->vendors->getVendorById($vendorId);

                    if (!$vendor) {
                        throw new NotFoundHttpException(Stripe::t('Vendor not found'));
                    }
                }
            }
        }


        $variables['userElement'] = $vendor->getUser();
        $variables['elementType'] = User::class;

        $variables['vendorId'] = $vendorId;
        $variables['vendor'] = $vendor;
        $variables['paymentTypeOptions'] = Stripe::$app->vendors->getPaymentTypesAsOptions();

        // Set the "Continue Editing" URL
        $variables['continueEditingUrl'] = 'enupal-stripe/vendors/edit/{id}';

        $variables['settings'] = Stripe::$app->settings->getSettings();

        $vendorUsersIds = Stripe::$app->vendors->getVendorUsersIds();

        $variables['criteria'] = [
            'where' => ['not in', 'elements.id', $vendorUsersIds]
        ];

        return $this->renderTemplate('enupal-stripe/vendors/_edit', $variables);
    }

    /**
     * Delete a Stripe Vendor.
     *
     * @return \yii\web\Response
     * @throws \Throwable
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionDeleteForm()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $vendorId = $request->getRequiredBodyParam('vendorId');
        $vendor = Stripe::$app->paymentForms->getPaymentFormById($vendorId);

        // @TODO - handle errors
        Stripe::$app->paymentForms->deletePaymentForm($vendor);

        Craft::$app->getSession()->setNotice(Stripe::t('Payment form deleted.'));

        return $this->redirectToPostedUrl($vendor);
    }

    /**
     * Retrieve all stripe plans as options for dropdown select field
     *
     * @return \yii\web\Response
     */
    public function actionRefreshPlans()
    {
        try {
            $this->requirePostRequest();
            $this->requireAcceptsJson();

            $plans = Stripe::$app->plans->getStripePlans();
        } catch (\Throwable $e) {
            return $this->asErrorJson($e->getMessage());
        }

        return $this->asJson(['success'=> true, 'plans' => $plans]);
    }
}
