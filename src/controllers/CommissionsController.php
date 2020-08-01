<?php
/**
 * Stripe plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2019 Enupal LLC
 */


namespace enupal\stripe\controllers;

use Craft;
use craft\web\Controller as BaseController;
use enupal\stripe\elements\Commission as CommissionElement;
use enupal\stripe\Stripe;
use yii\web\NotFoundHttpException;

class CommissionsController extends BaseController
{
    /**
     * Save a Commission
     *
     * @return null|\yii\web\Response
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionSaveCommission()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $commission = new CommissionElement;

        $commissionId = $request->getBodyParam('commissionId');

        if ($commissionId) {
            $commission = Stripe::$app->commissions->getCommissionById($commissionId);
        }

        $commission = Stripe::$app->commissions->populateCommissionFromPost($commission);

        // Save it
        if (!Craft::$app->elements->saveElement($commission)) {
            Craft::$app->getSession()->setError(Craft::t('enupal-stripe','Couldn’t save commission'));

            Craft::$app->getUrlManager()->setRouteParams([
                    'commission' => $commission
                ]
            );

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('enupal-stripe','Commission saved.'));

        return $this->redirectToPostedUrl($commission);
    }

    /**
     * Edit a Commission
     *
     * @param int|null $commissionId The commission ID, if editing an existing commission.
     * @param CommissionElement|null $commission   The commission send back by setRouteParams if any errors on saveCommission
     *
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     * @throws \Exception
     * @throws \Throwable
     */
    public function actionEditCommission(int $commissionId = null, CommissionElement $commission = null)
    {
        // Immediately create a new Commission
        if ($commissionId !== null) {
            if ($commission === null) {
                // Get the commission
                $commission = Stripe::$app->commissions->getCommissionById($commissionId);

                if (!$commission) {
                    throw new NotFoundHttpException(Craft::t('enupal-stripe','Commission not found'));
                }
            }
        }else {
            throw new NotFoundHttpException(Craft::t('enupal-stripe','Commission not found'));
        }


        $variables['commissionId'] = $commissionId;
        $variables['commission'] = $commission;

        // Set the "Continue Editing" URL
        $variables['continueEditingUrl'] = 'enupal-stripe/commissions/edit/{id}';
        $settings = Stripe::$app->settings->getSettings();
        $variables['settings'] = $settings;

        return $this->renderTemplate('enupal-stripe/commissions/_edit', $variables);
    }

    /**
     * Delete a Commission.
     *
     * @return \yii\web\Response
     * @throws \Throwable
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionDeleteCommission()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $commissionId = $request->getRequiredBodyParam('commissionId');
        $commission = Stripe::$app->commissions->getCommissionById($commissionId);

        // @TODO - handle errors
        Stripe::$app->commissions->deleteCommission($commission);

        Craft::$app->getSession()->setNotice(Craft::t('enupal-stripe','Commission deleted.'));

        return $this->redirectToPostedUrl($commission);
    }

    /**
     * Transfer payment via ajax
     *
     * @return \yii\web\Response
     * @throws \Throwable
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionTransferPayment()
    {
        $this->requireAcceptsJson();
        $request = Craft::$app->getRequest();
        $commissionId = $request->getRequiredBodyParam('commissionId');
        $commission = Stripe::$app->commissions->getCommissionById($commissionId);

        if (is_null($commission)){
            return $this->asErrorJson("Commission not found: ".$commissionId);
        }

        $result = Stripe::$app->commissions->processTransfer($commission);

        if (!$result){
            return $this->asErrorJson("Unable to process transfer");
        }

        return $this->asJson(['success'=> true]);
    }
}
