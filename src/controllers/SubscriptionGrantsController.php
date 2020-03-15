<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\controllers;

use Craft;
use enupal\stripe\models\SubscriptionGrant;
use enupal\stripe\Stripe;
use craft\web\Controller as BaseController;
use yii\web\NotFoundHttpException;

class SubscriptionGrantsController extends BaseController
{
    /**
     * @param int|null         $subscriptionGrantId
     * @param SubscriptionGrant|null $subscriptionGrant
     *
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionEdit(int $subscriptionGrantId = null, SubscriptionGrant $subscriptionGrant = null)
    {
        if (!$subscriptionGrant) {
            if ($subscriptionGrantId) {
                $subscriptionGrant = Stripe::$app->subscriptions->getSubscriptionGrantById($subscriptionGrantId);

                if (!$subscriptionGrant->id) {
                    throw new NotFoundHttpException(Craft::t('enupal-stripe', 'Subscription Grant not found'));
                }
            } else {
                $subscriptionGrant = new SubscriptionGrant();
            }
        }

        $userGroups = Craft::$app->getUserGroups()->getAllGroups();

        return $this->renderTemplate('enupal-stripe/settings/subscription-grants/_edit', [
            'subscriptionGrant' => $subscriptionGrant,
            'subscriptionGrantId' => $subscriptionGrantId,
            'userGroups' => $userGroups
        ]);
    }

    /**
     * @return \yii\web\Response|null
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionSave()
    {
        $this->requirePostRequest();

        $id = Craft::$app->request->getBodyParam('subscriptionGrantId');
        $subscriptionGrant = Stripe::$app->subscriptions->getSubscriptionGrantById($id);

        $subscriptionGrant->name = Craft::$app->request->getBodyParam('name');
        $subscriptionGrant->handle = Craft::$app->request->getBodyParam('handle');
        $subscriptionGrant->planId = Craft::$app->request->getBodyParam('planId');
        $subscriptionGrant->enabled = Craft::$app->request->getBodyParam('enabled');
        $subscriptionGrant->userGroupId = Craft::$app->request->getBodyParam('userGroupId');
        $subscriptionGrant->removeWhenCanceled = Craft::$app->request->getBodyParam('removeWhenCanceled');

        if (!Stripe::$app->subscriptions->saveSubscriptionGrant($subscriptionGrant)) {
            Craft::$app->session->setError(Craft::t('enupal-stripe', 'Could not save Subscription Grant'));

            Craft::$app->getUrlManager()->setRouteParams([
                'subscriptionGrant' => $subscriptionGrant
            ]);

            return null;
        }

        Craft::$app->session->setNotice(Craft::t('enupal-stripe', 'Subscription Grant saved.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * @return \yii\web\Response|null
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionDelete()
    {
        $this->requirePostRequest();

        $subscriptionGrantId = Craft::$app->request->getRequiredBodyParam('id');

        if (!Stripe::$app->subscriptions->deleteSubscriptionGrantById($subscriptionGrantId)) {
            return $this->asJson(null);
        }

        return $this->asJson(['success' => true]);
    }
}
