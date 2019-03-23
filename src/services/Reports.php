<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @dedicado Al amor de vida, mi compañera de vida y motivacion de cualquier deseo ardiente de exito, a mi Sara **).
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use Craft;
use craft\db\Query as CraftQuery;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use enupal\stripe\Stripe;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Ods;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use yii\base\Component;
use yii\base\Exception;

class Reports extends Component
{

    /**
     * Get a order summary CSV or XLS file for date range and an optional status.
     *
     * @param $format
     * @param $startDate
     * @param $endDate
     * @param null $orderStatusId
     * @return string
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function getOrdersExportFile($format, $startDate, $endDate, $orderStatusId = null)
    {
        $columns = $this->getSelectColumns();
        $reportColumns = $this->getReportColumns();

        // Dont use `date(dateOrdered)` in sql to force comparison to whole day, instead just remove timestamp and shift end date.
        $startDate = new \DateTime($startDate);
        $startDate->setTime(0, 0);
        $endDate = new \DateTime($endDate);
        $endDate->modify('+1 day'); //so that we capture whole day of endDate

        $orderQuery = (new CraftQuery())
            ->select($columns)
            ->from('{{%enupalstripe_orders}} enupalstripe_orders')
            ->andWhere(['>=', 'dateOrdered', Db::prepareDateForDb($startDate)])
            ->andWhere(['<=', 'dateOrdered', Db::prepareDateForDb($endDate)]);

        $orderQuery->leftJoin('{{%enupalstripe_addresses}} shipping_address', 'shipping_address.id = enupalstripe_orders.shippingAddressId');
        $orderQuery->leftJoin('{{%enupalstripe_addresses}} billing_address', 'billing_address.id = enupalstripe_orders.billingAddressId');
        $orderQuery->leftJoin('{{%enupalstripe_countries}} shippingCountries', 'shippingCountries.id = shipping_address.countryId');
        $orderQuery->leftJoin('{{%enupalstripe_countries}} billingCountries', 'billingCountries.id = billing_address.countryId');

        $status = null;

        if ($orderStatusId){
            $status = Stripe::$app->orderStatuses->getOrderStatusById($orderStatusId);
        }

        if ($status) {
            $orderQuery->andWhere('orderStatusId = :id', [':id' => $status->id]);
        }

        $orders = $orderQuery->all();

        // Populate the spreadsheet
        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $spreadsheet->getActiveSheet()->fromArray($reportColumns, null, 'A1');
        $spreadsheet->getActiveSheet()->fromArray($orders, null, 'A2');

        // Could use the writer factory with a $format <-> phpspreadsheet string map, but this is more simple for now.
        switch ($format) {
            case 'csv':
                $writer = new Csv($spreadsheet);
                break;
            case 'xls':
                $writer = new Xls($spreadsheet);
                break;
            case 'xlsx':
                $writer = new Xlsx($spreadsheet);
                break;
            case 'ods':
                $writer = new Ods($spreadsheet);
                break;
        }

        // Prepare and write temp file to disk
        $folder = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . 'enupal-stripe-order-exports';
        FileHelper::createDirectory($folder);
        $filename = uniqid('orderexport', true) . '.' . $format;
        $tempFile = $folder . DIRECTORY_SEPARATOR . $filename;
        if (($handle = fopen($tempFile, 'wb')) === false) {
            throw new Exception('Could not create temp file: ' . $tempFile);
        }
        fclose($handle);

        $writer->save($tempFile);

        return $tempFile;
    }

    /**
     * @return array
     */
    private function getSelectColumns()
    {
        $columns = [
            'enupalstripe_orders.id orderId',
            'formId',
            'userId',
            'testMode',
            'paymentType',
            'number',
            'currency',
            'totalPrice',
            'tax',
            'quantity',
            'dateOrdered',
            'stripeTransactionId',
            'email',
            'isCompleted',
            'variants formFields',
            'isSubscription',
            'orderStatusId',
            'dateRefunded',
            'refunded',
            'billingAddressId',
            'shippingAddressId',
            'enupalstripe_orders.dateCreated dateCreated',
            'enupalstripe_orders.dateUpdated dateUpdated',
            'billing_address.firstName billingAddressFirstName',
            'billing_address.lastName billingAddressLastName',
            'billing_address.address1 billingAddressAddress1',
            'billing_address.address2 billingAddressAddress2',
            'billing_address.zipCode billingAddressZipCode',
            'billing_address.stateName billingState',
            'billingCountries.name billingCountry',
            'shipping_address.firstName shippingAddressFirstName',
            'shipping_address.lastName shippingAddressLastName',
            'shipping_address.address1 shippingAddressAddress1',
            'shipping_address.address2 shippingAddressAddress2',
            'shipping_address.zipCode shippingAddressZipCode',
            'shipping_address.stateName shippingState',
            'shippingCountries.name shippingCountry',
        ];

        return $columns;
    }

    /**
     * @return array
     */
    private function getReportColumns()
    {
        $columns = [
            'orderId',
            'formId',
            'userId',
            'testMode',
            'paymentType',
            'number',
            'currency',
            'totalPrice',
            'tax',
            'quantity',
            'dateOrdered',
            'stripeTransactionId',
            'email',
            'isCompleted',
            'variants formFields',
            'isSubscription',
            'orderStatusId',
            'dateRefunded',
            'refunded',
            'billingAddressId',
            'shippingAddressId',
            'dateCreated',
            'dateUpdated',
            'billingAddressFirstName',
            'billingAddressLastName',
            'billingAddressAddress1',
            'billingAddressAddress2',
            'billingAddressZipCode',
            'billingAddressState',
            'billingCountry',
            'shippingAddressFirstName',
            'shippingAddressLastName',
            'shippingAddressAddress1',
            'shippingAddressAddress2',
            'shippingAddressZipCode',
            'shippingAddressState',
            'shippingCountry',
        ];

        return $columns;
    }
}
