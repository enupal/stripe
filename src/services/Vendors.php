<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use craft\db\Query;
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
}
