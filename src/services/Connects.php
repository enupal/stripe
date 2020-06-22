<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use craft\db\Query;
use craft\services\Plugins;
use enupal\stripe\elements\Commission;
use enupal\stripe\elements\Connect;
use enupal\stripe\elements\PaymentForm;
use enupal\stripe\Stripe;
use yii\base\Component;
use Craft;

class Connects extends Component
{
    /**
     * Returns a Connect model if one is found in the database by id
     *
     * @param int $id
     *
     * @return null|Connect
     */
    public function getConnectById(int $id)
    {
        $connect = Craft::$app->getElements()->getElementById($id);

        return $connect;
    }

    /**
     * @return array
     */
    public function getConnectProductTypes()
    {
        $productTypes = [
            PaymentForm::class
        ];

        return $productTypes;
    }

    /**
     * @return array
     */
    public function getConnectProductTypesAsOptions()
    {
        $productTypes = $this->getConnectProductTypes();
        $options = [];

        foreach ($productTypes as $productType) {
            $name = $productType::displayName();
            $options[] = [
                'label' => $name,
                'value' => $productType
            ];
        }

        return $options;
    }

    /**
     * @param string $productType
     *
     * @return Connect
     * @throws \Exception
     * @throws \Throwable
     */
    public function createNewConnect(string $productType): Connect
    {
        $settings = Stripe::$app->settings->getSettings();
        $connect = new Connect();

        $connect->productType = $productType;
        $connect->enabled = 0;
        $connect->rate = $settings->globalRate;

        Craft::$app->elements->saveElement($connect, false);

        return $connect;
    }

    /**
     * @return bool
     */
    public function isCommerceInstalled()
    {
        $pluginHandle = 'commerce';
        $projectConfig = Craft::$app->getProjectConfig();
        $commerceSettings = $projectConfig->get(Plugins::CONFIG_PLUGINS_KEY.'.'.$pluginHandle);
        $isInstalled = $commerceSettings['enabled'] ?? false;

        return $isInstalled;
    }

    /**
     * @param Connect $connect
     *
     * @return Connect
     */
    public function populateConnectFromPost(Connect $connect)
    {
        $request = Craft::$app->getRequest();

        $postFields = $request->getBodyParam('fields');

        $postFields['vendorId'] = is_array($postFields['vendorId']) ? $postFields['vendorId'][0] : $postFields['vendorId'];

        $connect->setAttributes(/** @scrutinizer ignore-type */
            $postFields, false);

        return $connect;
    }

    /**
     * @param Connect $connect
     *
     * @return bool
     * @throws \Throwable
     */
    public function deleteConnect(Connect $connect)
    {
        $transaction = Craft::$app->db->beginTransaction();

        try {
            // Delete the commissions
            $commissions = (new Query())
                ->select(['id'])
                ->from(["{{%enupalstripe_commissions}}"])
                ->where(['connectId' => $connect->id])
                ->all();

            foreach ($commissions as $commission) {
                Craft::$app->elements->deleteElementById($commission['id'], Commission::class, null, true);
            }

            // Delete the Connect
            $success = Craft::$app->elements->deleteElementById($connect->id, Connect::class,null, true);

            if (!$success) {
                $transaction->rollback();
                Craft::error("Couldn’t delete Connect", __METHOD__);

                return false;
            }

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollback();

            throw $e;
        }

        return true;
    }
}
