<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\jobs;

use craft\db\Query;
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
     * @todo add sync vendor job
     * @inheritdoc
     */
    public function execute($queue)
    {
        /**
        $result = false;
        $settings = StripePlugin::$app->settings->getSettings();

        $userQuery = $forms = (new Query())
            ->select(['*'])
            ->from(['{{%users}}']);

        $runQuery = false;

        if ($settings->vendorUserFieldId) {
            $runQuery = true;
        }

        if ($settings->vendorUserGroupId) {
            $runQuery = true;
            $userQuery->innerJoin('{{%usergroups_users}} usergroups', '[[usergroups.userId]] = [[users.id]]');
            $userQuery->orWhere(['usergroups.id' => $settings->vendorUserGroupId]);
        }

        return $result;
         **/
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