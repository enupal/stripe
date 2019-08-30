<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\controllers;

use Craft;
use enupal\stripe\Stripe;
use enupal\stripe\models\OrderStatus;
use craft\web\Controller as BaseController;
use yii\web\NotFoundHttpException;

class OrderStatusesController extends BaseController
{
    /**
     * @param int|null         $orderStatusId
     * @param OrderStatus|null $orderStatus
     *
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionEdit(int $orderStatusId = null, OrderStatus $orderStatus = null)
    {
        if (!$orderStatus) {
            if ($orderStatusId) {
                $orderStatus = Stripe::$app->orderStatuses->getOrderStatusById($orderStatusId);

                if (!$orderStatus->id) {
                    throw new NotFoundHttpException(Craft::t('enupal-stripe', 'Order Status not found'));
                }
            } else {
                $orderStatus = new OrderStatus();
            }
        }

        return $this->renderTemplate('enupal-stripe/settings/order-statuses/_edit', [
            'orderStatus' => $orderStatus,
            'orderStatusId' => $orderStatusId
        ]);
    }

	/**
	 * @return null|\yii\web\Response
	 * @throws \yii\web\BadRequestHttpException
	 */
    public function actionSave()
    {
        $this->requirePostRequest();

        $id = Craft::$app->request->getBodyParam('orderStatusId');
        $orderStatus = Stripe::$app->orderStatuses->getOrderStatusById($id);

        $orderStatus->name = Craft::$app->request->getBodyParam('name');
        $orderStatus->handle = Craft::$app->request->getBodyParam('handle');
        $orderStatus->color = Craft::$app->request->getBodyParam('color');
        $orderStatus->isDefault = Craft::$app->request->getBodyParam('isDefault');

        if (!Stripe::$app->orderStatuses->saveOrderStatus($orderStatus)) {
            Craft::$app->session->setError(Craft::t('enupal-stripe', 'Could not save Order Status.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'orderStatus' => $orderStatus
            ]);

            return null;
        }

        Craft::$app->session->setNotice(Craft::t('enupal-stripe', 'Order Status saved.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * @return \yii\web\Response
     * @throws \Exception
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionReorder()
    {
        $this->requirePostRequest();

        $ids = json_decode(Craft::$app->request->getRequiredBodyParam('ids'), true);

        if ($success = Stripe::$app->orderStatuses->reorderOrderStatuses($ids)) {
            return $this->asJson(['success' => $success]);
        }

        return $this->asJson(['error' => Craft::t('enupal-stripe', "Couldn't reorder Order Statuses.")]);
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

        $orderStatusId = Craft::$app->request->getRequiredBodyParam('id');

        if (!Stripe::$app->orderStatuses->deleteOrderStatusById($orderStatusId)) {
            return $this->asJson(null);
        }

        return $this->asJson(['success' => true]);
    }
}
