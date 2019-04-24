<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use Craft;
use yii\base\Component;
use enupal\stripe\models\OrderStatus as OrderStatusModel;
use enupal\stripe\records\OrderStatus as OrderStatusRecord;
use enupal\stripe\elements\Order;

class OrderStatuses extends Component
{
    /**
     * @param $orderStatusId
     *
     * @return OrderStatusModel
     */
    public function getOrderStatusById($orderStatusId)
    {
        $orderStatus = OrderStatusRecord::find()
            ->where(['id' => $orderStatusId])
            ->one();

        $orderStatusesModel = new OrderStatusModel;

        if ($orderStatus) {
            $orderStatusesModel->setAttributes($orderStatus->getAttributes(), false);
        }

        return $orderStatusesModel;
    }

    /**
     * @param $orderStatus OrderStatusModel
     *
     * @return bool
     * @throws \Exception
     */
    public function saveOrderStatus(OrderStatusModel $orderStatus): bool
    {
        $record = new OrderStatusRecord;

        if ($orderStatus->id) {
            $record = OrderStatusRecord::findOne($orderStatus->id);

            if (!$record) {
                throw new \Exception(Craft::t('enupal-stripe', 'No Order Status exists with the id of “{id}”', [
                    'id' => $orderStatus->id
                ]));
            }
        }

        $record->setAttributes($orderStatus->getAttributes(), false);

        $record->sortOrder = $orderStatus->sortOrder ?: 999;

        $orderStatus->validate();

        if (!$orderStatus->hasErrors()) {
            $transaction = Craft::$app->db->beginTransaction();

            try {
                if ($record->isDefault) {
                    OrderStatusRecord::updateAll(['isDefault' => 0]);
                }

                $record->save(false);

                if (!$orderStatus->id) {
                    $orderStatus->id = $record->id;
                }

                $transaction->commit();
            } catch (\Exception $e) {
                $transaction->rollback();

                throw $e;
            }

            return true;
        }

        return false;
    }

    /**
     * @return mixed|null
     */
    public function getDefaultOrderStatusId()
    {
        $entryStatus = OrderStatusRecord::find()
            ->orderBy(['isDefault' => SORT_DESC])
            ->one();

        return $entryStatus != null ? $entryStatus->id : null;
    }

    /**
     * Reorders Order Statuses
     *
     * @param $orderStatusIds
     *
     * @return bool
     * @throws \Exception
     */
    public function reorderOrderStatuses($orderStatusIds)
    {
        $transaction = Craft::$app->db->beginTransaction();

        try {
            foreach ($orderStatusIds as $orderStatus => $orderStatusId) {
                $orderStatusRecord = $this->getOrderStatusRecordById($orderStatusId);
                $orderStatusRecord->sortOrder = $orderStatus + 1;
                $orderStatusRecord->save();
            }

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        return true;
    }

    /**
     * @return array
     */
    public function getAllOrderStatuses()
    {
        $orderStatuses = OrderStatusRecord::find()
            ->orderBy(['sortOrder' => 'asc'])
            ->all();

        return $orderStatuses;
    }

    /**
     * @param $id
     *
     * @return bool
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function deleteOrderStatusById($id)
    {
        $statuses = $this->getAllOrderStatuses();

        $order = Order::find()->orderStatusId($id)->one();

        if ($order) {
            return false;
        }

        if (count($statuses) >= 2) {
            $orderStatus = OrderStatusRecord::findOne($id);

            if ($orderStatus) {
                $orderStatus->delete();
                return true;
            }
        }

        return false;
    }

    /**
     * Gets an Order Status's record.
     *
     * @param null $orderStatusHandle
     *
     * @return OrderStatusRecord|null
     * @throws \Exception
     */
    public function getOrderStatusRecordByHandle($orderStatusHandle = null)
    {
        $orderStatusRecord = null ;

        if ($orderStatusHandle) {
            $orderStatusRecord = OrderStatusRecord::findOne(['handle' => $orderStatusHandle]);

            if (!$orderStatusRecord) {
                throw new \Exception(Craft::t('enupal-stripe', 'No Order Status exists with the ID “{id}”.',
                    ['id' => $orderStatusHandle]
                )
                );
            }
        }

        return $orderStatusRecord;
    }

    /**
     * Gets an Order Status's record.
     *
     * @param null $orderStatusId
     *
     * @return OrderStatusRecord|null|static
     * @throws \Exception
     */
    private function getOrderStatusRecordById($orderStatusId = null)
    {
        if ($orderStatusId) {
            $orderStatusRecord = OrderStatusRecord::findOne($orderStatusId);

            if (!$orderStatusRecord) {
                throw new \Exception(Craft::t('enupal-stripe', 'No Order Status exists with the ID “{id}”.',
                    ['id' => $orderStatusId]
                )
                );
            }
        } else {
            $orderStatusRecord = new OrderStatusRecord();
        }

        return $orderStatusRecord;
    }
}
