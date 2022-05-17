<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\jobs;

use craft\db\Query;
use craft\elements\User;
use enupal\stripe\Stripe as StripePlugin;
use craft\queue\BaseJob;
use Craft;

use yii\queue\RetryableJobInterface;

/**
 * SyncVendors job
 */
class SyncVendors extends BaseJob implements RetryableJobInterface
{
    /**
     * Returns the default description for this job.
     *
     * @return string
     */
    protected function defaultDescription(): ?string
    {
        return StripePlugin::t('Syncing Vendors');
    }

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        $result = false;

        $usersFieldId = $this->getUsersByUserFieldId();
        $usersByGroupId = $this->getUsersByUserGroupId();
        $usersMap = [];

        $users = array_merge($usersFieldId, $usersByGroupId);
        $vendorUserIds = (new Query())
            ->select(['userId'])
            ->from(["{{%enupalstripe_vendors}}"])
            ->all();

        $vendorUserIdsMap = [];

        foreach ($vendorUserIds as $vendorUserId) {
            $vendorUserIdsMap[$vendorUserId['userId']] = 1;
        }

        $totalSteps = count($users);
        $step = 0;
        $skipped = 0;

        foreach ($users as $user) {
            $step++;
            if (isset($usersMap[$user->id])) {
                $skipped++;
                continue;
            }

            $usersMap[$user->id] = 1;
            // Is this user already a vendor?
            if (isset($vendorUserIdsMap[$user->id])) {
                $skipped++;
                continue;
            }

            if (StripePlugin::$app->vendors->registerDefaultVendor($user)) {
                $vendorUserIdsMap[$user->id] = 1;
            }

            $this->setProgress($queue, $step / $totalSteps);
        }

        Craft::info('Sync Vendors process finished, Total: '.$step. ', Skipped: '.$skipped, __METHOD__);

        return $result;
    }

    private function getUsersByUserGroupId()
    {
        $settings = StripePlugin::$app->settings->getSettings();

        if (!$settings->vendorUserGroupId) {
            return [];
        }

        $userQuery = User::find();

        $userQuery->innerJoin('{{%usergroups_users}} usergroups_users', '[[usergroups_users.userId]] = [[users.id]]');
        $userQuery->andWhere(['usergroups_users.groupId' => (int)$settings->vendorUserGroupId]);

        return $userQuery->all();
    }

    private function getUsersByUserFieldId()
    {
        $settings = StripePlugin::$app->settings->getSettings();

        if (!$settings->vendorUserFieldId) {
            return [];
        }

        $field = (new Query())
            ->select(['handle'])
            ->from(['{{%fields}}'])
            ->andWhere(['id' => (int)$settings->vendorUserFieldId])
            ->one();

        $handle = $field['handle'] ?? null;

        $users = User::findAll([$handle => true]);

        return $users;
    }

    /**
     * @inheritdoc
     */
    public function getTtr()
    {
        return 3600;
    }

    /**
     * @inheritdoc
     */
    public function canRetry($attempt, $error)
    {
        return ($attempt < 5) && ($error instanceof \Exception);
    }
}