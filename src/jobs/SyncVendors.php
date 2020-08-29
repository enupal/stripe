<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\jobs;

use Aws\Iam\IamClient;
use craft\db\Query;
use craft\elements\User;
use enupal\stripe\Stripe as StripePlugin;
use craft\queue\BaseJob;

use yii\queue\RetryableJobInterface;

/**
 * SyncVendors job
 */
class SyncVendors extends BaseJob implements RetryableJobInterface
{
    public $defaultPaymentType;

    public $defaultSkipAdminReview;

    public $defaultVendorRate;

    public $defaultEnabled;

    /**
     * Returns the default description for this job.
     *
     * @return string
     */
    protected function defaultDescription(): string
    {
        return StripePlugin::t('Syncing Vendors');
    }

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        $result = false;


        if ($settings->vendorUserGroupId) {
            $runQuery = true;
            $userQuery->innerJoin('{{%usergroups_users}} usergroups', '[[usergroups.userId]] = [[users.id]]');
            $userQuery->orWhere(['usergroups.id' => $settings->vendorUserGroupId]);
        }

        return $result;
    }

    private function getUsersByUserFieldId()
    {
        $settings = StripePlugin::$app->settings->getSettings();

        if (!$settings->vendorUserFieldId) {
            return null;
        }

        $field = (new Query())
            ->select(['handle'])
            ->from(['{{%fields}}'])
            ->andWhere(['id' => $settings->vendorUserGroupId])
            ->one();

        $handle = $field['handle'];

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