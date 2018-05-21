<?php
/**
 * EnupalStripe plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\stripe\controllers;

use Craft;
use craft\elements\Asset;
use craft\helpers\UrlHelper;
use craft\web\Controller as BaseController;
use enupal\stripe\Stripe;
use yii\web\NotFoundHttpException;


use enupal\stripe\elements\StripeButton as StripeElement;

class ButtonsController extends BaseController
{
    /**
     * Save a Button
     *
     * @return null|\yii\web\Response
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionSaveButton()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $button = new StripeElement;

        $buttonId = $request->getBodyParam('buttonId');

        if ($buttonId) {
            $button = Stripe::$app->buttons->getButtonById($buttonId);
        }

        $button = Stripe::$app->buttons->populateButtonFromPost($button);

        // Save it
        if (!Stripe::$app->buttons->saveButton($button)) {
            Craft::$app->getSession()->setError(Stripe::t('Couldn’t save button.'));

            Craft::$app->getUrlManager()->setRouteParams([
                    'button' => $button
                ]
            );

            return null;
        }

        Craft::$app->getSession()->setNotice(Stripe::t('Button saved.'));

        return $this->redirectToPostedUrl($button);
    }

    /**
     * Edit a Button.
     *
     * @param int|null           $buttonId The button's ID, if editing an existing button.
     * @param StripeElement|null $button   The button send back by setRouteParams if any errors on saveButton
     *
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     * @throws \Exception
     * @throws \Throwable
     */
    public function actionEditButton(int $buttonId = null, StripeElement $button = null)
    {
       # $button = Stripe::$app->buttons->getButtonById($buttonId);
       # foreach ($button->{Stripe::$app->buttons::MULTIPLE_PLANS_HANDLE} as $item) {
       #     Craft::dd($item->selectPlan);

        #}
        #Craft::dd($button->{Stripe::$app->buttons::MULTIPLE_PLANS_HANDLE});

        // Immediately create a new Slider
        if ($buttonId === null) {
            $button = Stripe::$app->buttons->createNewButton();

            if ($button->id) {
                $url = UrlHelper::cpUrl('enupal-stripe/buttons/edit/'.$button->id);
                return $this->redirect($url);
            } else {
                throw new \Exception(Stripe::t('Error creating Button'));
            }
        } else {
            if ($buttonId !== null) {
                if ($button === null) {
                    // Get the button
                    $button = Stripe::$app->buttons->getButtonById($buttonId);

                    if (!$button) {
                        throw new NotFoundHttpException(Stripe::t('Button not found'));
                    }
                }
            }
        }

        $variables['logoElement'] = [$button->getLogoAsset()];
        $variables['elementType'] = Asset::class;

        $variables['buttonId'] = $buttonId;
        $variables['stripeButton'] = $button;

        // Set the "Continue Editing" URL
        $variables['continueEditingUrl'] = 'enupal-stripe/buttons/edit/{id}';

        $variables['settings'] = Stripe::$app->settings->getSettings();

        return $this->renderTemplate('enupal-stripe/buttons/_edit', $variables);
    }

    /**
     * Delete a Stripe Button.
     *
     * @return \yii\web\Response
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\db\Exception
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionDeleteButton()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $buttonId = $request->getRequiredBodyParam('buttonId');
        $button = Stripe::$app->buttons->getButtonById($buttonId);

        // @TODO - handle errors
        Stripe::$app->buttons->deleteButton($button);

        return $this->redirectToPostedUrl($button);
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
