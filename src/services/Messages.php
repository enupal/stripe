<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use yii\base\Component;
use enupal\stripe\records\Message as MessageRecord;

class Messages extends Component
{
    /**
     * @param $orderId
     * @param $message
     * @param null|string|array $details
     * @return bool
     */
    public function addMessage($orderId, $message, $details = null)
    {
        if (is_array($details) || is_object($details)){
            $details = json_encode($details);
        }
        $messageRecord = new MessageRecord();
        $messageRecord->orderId = $orderId;
        $messageRecord->message = $message;
        $messageRecord->details = $details;

        return $messageRecord->save();
    }

    /**
     * @param $orderId
     * @return MessageRecord[]|null
     */
    public function getAllMessages($orderId)
    {
        $messages = MessageRecord::find()->where(['orderId' => $orderId])->orderBy([
            'dateCreated' => SORT_ASC
        ])->all();

        return $messages;
    }
}
