<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @dedicado Al amor de vida, mi compañera de vida y motivacion de cualquier deseo ardiente de exito, a mi Sara **).
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\controllers;

use Craft;
use enupal\stripe\Stripe as StripePlugin;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use craft\web\Controller as BaseController;

class DownloadsController extends BaseController
{
    /**
     * Returns the export file in the requested format.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \yii\base\Exception
     */
    public function actionExportOrder(): Response
    {
        $format = Craft::$app->getRequest()->getRequiredParam('format');
        $startDate = Craft::$app->getRequest()->getRequiredParam('startDate');
        $endDate = Craft::$app->getRequest()->getRequiredParam('endDate');
        $source = Craft::$app->getRequest()->getRequiredParam('source');

        // Limited to only the formats we allow.
        $allowedFormats = ['xls', 'csv', 'xlsx', 'ods',];
        if (!in_array($format, $allowedFormats, false)) {
            throw new BadRequestHttpException();
        }

        $orderStatusId = null;

        if (strpos($source, ':') !== false) {
            $orderStatusId = explode(':', $source)[1];
        }

        // Get the generated file saved into a temporary location
        $tempFile = StripePlugin::$app->reports->getOrdersExportFile($format, $startDate, $endDate, $orderStatusId);

        return Craft::$app->getResponse()->sendFile($tempFile, 'orders.' . $format);
    }
}
