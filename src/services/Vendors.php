<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use craft\base\Field;
use craft\db\Query;
use craft\elements\User;
use craft\fields\Lightswitch;
use enupal\stripe\elements\Connect;
use enupal\stripe\elements\Vendor as VendorElement;
use yii\base\Component;
use Craft;

class Vendors extends Component
{
    CONST PAYMENT_TYPE_MANUALLY = 'manually';
    CONST PAYMENT_TYPE_ON_CHECKOUT = 'checkout';

    /**
     * Returns a Vendor model if one is found in the database by id
     *
     * @param int $id
     *
     * @return null|VendorElement
     */
    public function getVendorById(int $id)
    {
        $vendor = Craft::$app->getElements()->getElementById($id);

        return $vendor;
    }

    /**
     * @param $userId
     * @return array|\craft\base\ElementInterface|null
     */
    public function getVendorByUserId($userId)
    {
        $query = VendorElement::find();
        $query->userId = $userId;

        return $query->one();
    }

    /**
     * @param $stripeId
     * @return array|\craft\base\ElementInterface|null
     */
    public function getVendorByStripeId($stripeId)
    {
        $query = VendorElement::find();
        $query->stripeId = $stripeId;

        return $query->one();
    }

    /**
     * @return array
     */
    public function getVendorUsersIds()
    {
        $userIds = (new Query())
            ->select('userId')
            ->from(['{{%enupalstripe_vendors}}'])
            ->all();

        $ids = [];

        foreach ($userIds as $userId) {
            $ids[] = $userId['userId'];
        }

        return $ids;
    }

    /**
     * @return array
     */
    public function getPaymentTypesAsOptions()
    {
        $options = [];

        foreach ($this->getPaymentTypes() as $paymentType) {
            $options[] = [
                'label' => ucfirst($paymentType),
                'value' => $paymentType
            ];
        }

        return $options;
    }

    /**
     * @return array
     */
    public function getPaymentTypes()
    {
        return [
            self::PAYMENT_TYPE_MANUALLY,
            self::PAYMENT_TYPE_ON_CHECKOUT
        ];
    }

    /**
     * @param VendorElement $vendor
     *
     * @return VendorElement
     */
    public function populateVendorFromPost(VendorElement $vendor)
    {
        $request = Craft::$app->getRequest();

        $postFields = $request->getBodyParam('fields');

        $postFields['userId'] = is_array($postFields['userId']) ? $postFields['userId'][0] : $postFields['userId'];

        $vendor->setAttributes(/** @scrutinizer ignore-type */
            $postFields, false);

        return $vendor;
    }

    /**
     * @param VendorElement $vendor
     *
     * @return bool
     * @throws \Throwable
     */
    public function deleteVendor(VendorElement $vendor)
    {
        $transaction = Craft::$app->db->beginTransaction();

        try {
            // Delete the connects
            $connects = (new Query())
                ->select(['id'])
                ->from(["{{%enupalstripe_connect}}"])
                ->where(['vendorId' => $vendor->id])
                ->all();

            foreach ($connects as $connect) {
                Craft::$app->elements->deleteElementById($connect['id'], Connect::class, null, true);
            }

            // Delete the Vendor
            $success = Craft::$app->elements->deleteElementById($vendor->id, VendorElement::class,null, true);

            if (!$success) {
                $transaction->rollback();
                Craft::error("Couldn’t delete Vendor", __METHOD__);

                return false;
            }

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollback();

            throw $e;
        }

        return true;
    }

    /**
     * This function will register a vendor with basic info if has a field or user group associated to vendors
     * @param User $user
     */
    public function processUserActivation(User $user)
    {
        $userGroups = Craft::$app->userGroups->getGroupsByUserId($user->id);
    }

    /**
     * @return array
     */
    public function getBooleanUserFields()
    {
        $booleanFields = [];
        $user = new User();
        $fieldLayout = $user->getFieldLayout();
        $fields = $fieldLayout->getFields();

        foreach ($fields as $field) {
            if ($field instanceof Lightswitch) {
                $booleanFields[] = $field;
            }
        }

        return $booleanFields;
    }

    /**
     * @return array
     */
    public function getBooleanUserFieldsAsOptions()
    {
        $booleanFields = $this->getBooleanUserFields();
        $options = [];

        $options[] = [
            'label' => 'Select a field',
            'value' => ''
        ];

        /** @var Field $booleanField */
        foreach ($booleanFields as $booleanField) {
            $options[] = [
                'label' => $booleanField->name. ' ('.$booleanField->handle.')',
                'value' => $booleanField->id
            ];
        }

        return $options;
    }
}
