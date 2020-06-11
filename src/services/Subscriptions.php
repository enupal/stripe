<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use Craft;
use craft\db\Query;
use enupal\stripe\elements\Order;
use enupal\stripe\events\WebhookEvent;
use enupal\stripe\models\SubscriptionGrant;
use enupal\stripe\records\SubscriptionGrant as SubscriptionGrantRecord;
use Stripe\Subscription;
use enupal\stripe\models\Subscription as SubscriptionModel;
use yii\base\Component;
use enupal\stripe\Stripe as StripePlugin;

class Subscriptions extends Component
{
    /**
     * @param $id
     * @return null|\Stripe\StripeObject
     */
    public function getStripeSubscription($id)
    {
        $subscription = null;

        try {
            StripePlugin::$app->settings->initializeStripe();

            $subscription = Subscription::retrieve($id);

        } catch (\Exception $e) {
            Craft::error('Unable to get subscription: '.$e->getMessage(), __METHOD__);
        }

        return $subscription;
    }

    /**
     * @param $id
     * @return SubscriptionModel
     */
    public function getSubscriptionModel($id)
    {
        $subscription = $this->getStripeSubscription($id);

        $subscriptionModel = new SubscriptionModel($subscription);

        return $subscriptionModel;
    }

	/**
	 * @param $id
	 * @param bool $cancelAtPeriodEnd
	 *
	 * @return bool
	 * @throws \Exception
	 */
    public function cancelStripeSubscription($id, bool $cancelAtPeriodEnd)
    {
        $response = false;

        try {
            StripePlugin::$app->settings->initializeStripe();

            $subscription = $this->getStripeSubscription($id);

            if ($subscription === null){
                Craft::error('Subscription not found: '.$id, __METHOD__);
                return $response;
            }
            if ($cancelAtPeriodEnd){
                Subscription::update($id, [
                    'cancel_at_period_end' => true
                ]);
            }else{
                $subscription->cancel();
            }
            $response = true;
        } catch (\Exception $e) {
            Craft::error('Unable to cancel subscription: '.$e->getMessage(), __METHOD__);
            throw $e;
        }

        return $response;
    }

	/**
	 * @param $id
	 * @return bool
	 * @throws \Exception
	 */
    public function reactivateStripeSubscription($id)
    {
        $response = false;

        try {
            StripePlugin::$app->settings->initializeStripe();

            $subscription = $this->getStripeSubscription($id);

            if ($subscription === null){
                Craft::error('Subscription not found: '.$id, __METHOD__);
                return $response;
            }

            Subscription::update($id, [
                'cancel_at_period_end' => false
            ]);

            $response = true;
        } catch (\Exception $e) {
            Craft::error('Unable to reactivate subscription: '.$e->getMessage(), __METHOD__);
            throw $e;
        }

        return $response;
    }

    /**
     * @param $stripeTransactionId
     * @return bool
     */
    public function getIsSubscription($stripeTransactionId)
    {
        $transactionId = substr($stripeTransactionId, 0, 3);

        if ($transactionId != 'sub'){
            return false;
        }

        return true;
    }

    /**
     * @param $status
     * @return string
     */
    public function getSubscriptionStatusHtml($status)
    {
        $statuses = [
            'trialing' => 'grey',
            'active' => 'green',
            'past_due' => 'orange',
            'canceled' => 'red',
            'unpaid' => 'yellow',
        ];

        $color = $statuses[$status] ?? '';
        $html = "<span class='status ".$color."'> </span>".$status;

        return $html;
    }

    /**
     * @param $userId
     * @return array|\craft\base\ElementInterface|null
     */
    public function getSubscriptionsByUser($userId)
    {
        $query = Order::find();
        $query->userId = $userId;
        $query->isSubscription = true;

        return $query->all();
    }

    /**
     * @param $email
     * @return array|\craft\base\ElementInterface|null
     */
    public function getSubscriptionsByEmail($email)
    {
        $query = Order::find();
        $query->email = $email;
        $query->isSubscription = true;

        return $query->all();
    }

    /**
     * @return array
     */
    public function getAllSubscriptionGrants()
    {
        $subscriptionGrants = (new Query())
            ->select(['*'])
            ->from(["{{%enupalstripe_subscriptiongrants}}"])
            ->all();

        return $subscriptionGrants;
    }

    /**
     * @param WebhookEvent $event
     * @return bool
     */
    public function processSubscriptionGrantEvent(WebhookEvent $event)
    {
        $data  = $event->stripeData;
        $order = $event->order;
        $eventType = $data['type'] ?? null;
        $planId = $data['data']['object']['items']['data'][0]['plan']['id'] ?? null;

        if ($order === null) {
            return false;
        }

        $user = Craft::$app->getUsers()->getUserByUsernameOrEmail($order->email);

        if ($user === null) {
            return false;
        }

        switch ($eventType) {
            case 'customer.subscription.created':
                $this->handleSubscriptionGrants($planId, $user);
                break;
            // If SCA is enabled the subscription will be available here.
            case 'checkout.session.completed':
                /** @var \enupal\stripe\models\Subscription $subscription */
                $subscription = $order->getSubscription();
                $user = Craft::$app->getUsers()->getUserByUsernameOrEmail($order->email);

                if ($subscription && $user) {
                    $id = $subscription->data->plan->id ?? null;

                    if ($id){
                        $this->handleSubscriptionGrants($id, $user);
                    }
                }

                break;

            case 'customer.subscription.deleted':
                $userGroups = $user->getGroups();
                $currentUserGroupIds = [];

                if ($userGroups){
                    foreach ($userGroups as $userGroup) {
                        $currentUserGroupIds[] = $userGroup->id;
                    }
                }

                $grantUserGroupIds = $this->getUserGroupsByPlanId($planId, true);
                $removedUserGroupIds = array_diff($currentUserGroupIds, $grantUserGroupIds);
                Craft::$app->getUsers()->assignUserToGroups($user->id, $removedUserGroupIds);
                Craft::info("Groups (".json_encode($grantUserGroupIds).") removed to user: ".$user->username, __METHOD__);

                break;
        }

        return true;
    }

    /**
     * @param $planId
     * @param $user
     */
    private function handleSubscriptionGrants($planId, $user)
    {
        $grantUserGroupIds = $this->getUserGroupsByPlanId($planId, $user);
        if ($grantUserGroupIds){
            Craft::$app->getUsers()->assignUserToGroups($user->id, $grantUserGroupIds);
            Craft::info("Groups (".json_encode($grantUserGroupIds).") added to user: ".$user->username, __METHOD__);
        }
    }

    /**
     * @param $planId
     * @param bool $checkRemoveWhenCanceled
     * @return array
     */
    public function getUserGroupsByPlanId($planId, $checkRemoveWhenCanceled = false)
    {
        $subscriptionGrants = $this->getSubscriptionGrantsByPlanId($planId);
        $newGroups = [];

        foreach ($subscriptionGrants as $subscriptionGrant) {
            if (!$subscriptionGrant['enabled']){
                continue;
            }

            if ($checkRemoveWhenCanceled){
                if ($subscriptionGrant['removeWhenCanceled']){
                    $newGroups[] = $subscriptionGrant['userGroupId'];
                }
            }else{
                $newGroups[] = $subscriptionGrant['userGroupId'];
            }
        }

        return $newGroups;
    }

    /**
     * @param $planId
     * @return array
     */
    public function getSubscriptionGrantsByPlanId($planId)
    {
        $subscriptionGrants =  (new Query())
            ->select(['*'])
            ->from(["{{%enupalstripe_subscriptiongrants}}"])
            ->where(["planId" => $planId])
            ->all();

        return $subscriptionGrants;
    }

    /**
     * @param $id
     * @return bool
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function deleteSubscriptionGrantById($id)
    {
        $subscriptionGrant = SubscriptionGrantRecord::findOne(['id' => $id]);

        if ($subscriptionGrant) {
            $subscriptionGrant->delete();
            return true;
        }

        return false;
    }

    /**
     * @param $subscriptionGrantId
     *
     * @return SubscriptionGrant
     */
    public function getSubscriptionGrantById($subscriptionGrantId)
    {
        $subscriptionGrant = SubscriptionGrantRecord::find()
            ->where(['id' => $subscriptionGrantId])
            ->one();

        $subscriptionGrantModel = new SubscriptionGrant();

        if ($subscriptionGrant) {
            $subscriptionGrantModel->setAttributes($subscriptionGrant->getAttributes(), false);
        }

        return $subscriptionGrantModel;
    }

    /**
     * @param SubscriptionGrant $subscriptionGrant
     * @return bool
     * @throws \Exception
     */
    public function saveSubscriptionGrant(SubscriptionGrant $subscriptionGrant)
    {
        $record = new SubscriptionGrantRecord();

        if ($subscriptionGrant->id) {
            $record = SubscriptionGrantRecord::findOne($subscriptionGrant->id);

            if (!$record) {
                throw new \Exception(Craft::t('enupal-stripe', 'No Subscription Grant exists with the id of “{id}”', [
                    'id' => $subscriptionGrant->id
                ]));
            }
        }

        $record->setAttributes($subscriptionGrant->getAttributes(), false);

        $record->sortOrder = $subscriptionGrant->sortOrder ?: 999;

        $subscriptionGrant->validate();

        if (!$subscriptionGrant->hasErrors()) {
            if ($subscriptionGrant->planId){
                $plan = StripePlugin::$app->plans->getStripePlan($subscriptionGrant->planId);
                if ($plan !== null){
                    $planName = StripePlugin::$app->plans->getDefaultPlanName($plan);
                    $record->planName = $planName;
                }
            }

            $transaction = Craft::$app->db->beginTransaction();

            try {
                $record->save(false);

                if (!$subscriptionGrant->id) {
                    $subscriptionGrant->id = $record->id;
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
}
