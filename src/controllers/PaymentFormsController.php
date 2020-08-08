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
use craft\helpers\UrlHelper;
use craft\web\Controller as BaseController;
use enupal\stripe\Stripe;
use yii\web\NotFoundHttpException;


use enupal\stripe\elements\PaymentForm as PaymentFormElement;

class PaymentFormsController extends BaseController
{
    /**
     * Allows anonymous execution
     *
     * @var string[]
     */
    protected $allowAnonymous = [
        'save-form'
    ];

    /**
     * Save a Payment Form
     *
     * @return null|\yii\web\Response
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionSaveForm()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $paymentForm = new PaymentFormElement;

        $formId = $request->getBodyParam('formId');

        if ($formId) {
            $paymentForm = Stripe::$app->paymentForms->getPaymentFormById($formId);
        } else{
            $paymentForm = Stripe::$app->paymentForms->createNewPaymentForm();
        }

        $paymentForm = Stripe::$app->paymentForms->populatePaymentFormFromPost($paymentForm);

        // Save it
        if (!Stripe::$app->paymentForms->savePaymentForm($paymentForm)) {
            Craft::$app->getSession()->setError(Craft::t('site','Couldn’t save payment form.'));

            Craft::$app->getUrlManager()->setRouteParams([
                    'paymentForm' => $paymentForm
                ]
            );

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('site','Payment form saved.'));

        return $this->redirectToPostedUrl($paymentForm);
    }

    /**
     * Edit a Payment Form.
     *
     * @param int|null           $formId The button's ID, if editing an existing button.
     * @param PaymentFormElement|null $paymentForm   The button send back by setRouteParams if any errors on savePaymentForm
     *
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     * @throws \Exception
     * @throws \Throwable
     */
    public function actionEditForm(int $formId = null, PaymentFormElement $paymentForm = null)
    {
        // Immediately create a new payment form
        if ($formId === null) {
            $paymentForm = Stripe::$app->paymentForms->createNewPaymentForm();

            if ($paymentForm->id) {
                $url = UrlHelper::cpUrl('enupal-stripe/forms/edit/'.$paymentForm->id);
                return $this->redirect($url);
            } else {
                throw new \Exception(Stripe::t('Error creating Payment Form'));
            }
        } else {
            if ($formId !== null) {
                if ($paymentForm === null) {
                    // Get the payment form
                    $paymentForm = Stripe::$app->paymentForms->getPaymentFormById($formId);

                    if (!$paymentForm) {
                        throw new NotFoundHttpException(Stripe::t('Payment Form not found'));
                    }
                }
            }
        }

        $variables['logoElement'] = $paymentForm->getLogoAssets();
        $variables['elementType'] = Asset::class;

        $variables['formId'] = $formId;
        $variables['stripeForm'] = $paymentForm;

        // Set the "Continue Editing" URL
        $variables['continueEditingUrl'] = 'enupal-stripe/forms/edit/{id}';

        $variables['settings'] = Stripe::$app->settings->getSettings();

        $variables['availablePaymentTypes'] =  Stripe::$app->paymentForms->getPaymentTypes();
        $variables['availableCheckoutPaymentTypes'] =  Stripe::$app->paymentForms->getCheckoutPaymentTypes();

        $variables['paymentTypeIdes'] = is_array($paymentForm->paymentType) ? $paymentForm->paymentType : json_decode($paymentForm->paymentType, true);
        $variables['checkoutPaymentTypeIdes'] = is_array($paymentForm->checkoutPaymentType) ? $paymentForm->checkoutPaymentType : json_decode($paymentForm->checkoutPaymentType, true);

        return $this->renderTemplate('enupal-stripe/forms/_edit', $variables);
    }

	/**
	 * Delete a Stripe Payment Form.
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

        $formId = $request->getRequiredBodyParam('formId');
        $paymentForm = Stripe::$app->paymentForms->getPaymentFormById($formId);

        // @TODO - handle errors
        Stripe::$app->paymentForms->deletePaymentForm($paymentForm);

        Craft::$app->getSession()->setNotice(Stripe::t('Payment form deleted.'));

        return $this->redirectToPostedUrl($paymentForm);
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
