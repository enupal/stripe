<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\controllers;

use Craft;
use craft\controllers\ElementIndexesController;
use craft\helpers\ChartHelper;
use craft\helpers\DateTimeHelper;
use craft\i18n\Locale;
use enupal\stripe\elements\db\OrdersQuery;
use enupal\stripe\Stripe;

class ChartsController extends ElementIndexesController
{
    // Public Methods
    // =========================================================================


    /**
     * Returns the data needed to display a Revenue chart.
     *
     * @return \yii\web\Response
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionGetRevenueData()
    {
        $startDateParam = Craft::$app->getRequest()->getRequiredParam('startDate');
        $endDateParam = Craft::$app->getRequest()->getRequiredParam('endDate');
        $currency = Craft::$app->getRequest()->getBodyParam('currency');
        $startDate = DateTimeHelper::toDateTime($startDateParam);
        $endDate = DateTimeHelper::toDateTime($endDateParam);
        $endDate->modify('+1 day');

        $intervalUnit = ChartHelper::getRunChartIntervalUnit($startDate, $endDate);

        /** @var OrdersQuery $query */
        $query = clone $this->getElementQuery()
            ->limit(null);

        $useDefault = true;

        if ($currency && $currency != '*'){
            $query->currency = $currency;
            $useDefault = false;
        }

        // Get the chart data table
        $dataTable = ChartHelper::getRunChartDataFromQuery($query, $startDate, $endDate, 'elements.dateCreated', 'sum', '[[enupalstripe_orders.totalPrice]]', [
            'intervalUnit' => $intervalUnit,
            'valueLabel' => Craft::t('enupal-stripe', 'Revenue'),
            'valueType' => 'currency',
        ]);

        // Get the total revenue
        $total = 0;

        foreach ($dataTable['rows'] as $row) {
            $total += $row[1];
        }

        // Return everything
        $settings = Stripe::$app->settings->getSettings();
        $finalCurrency = $useDefault ? $settings->defaultCurrency : $currency;
        $totalHtml = Craft::$app->getFormatter()->asCurrency($total, strtoupper($finalCurrency));

        return $this->asJson([
            'dataTable' => $dataTable,
            'total' => $total,
            'totalHtml' => $totalHtml,

            'formats' => ChartHelper::formats(),
            'orientation' => Craft::$app->getLocale()->getOrientation(),
            'scale' => $intervalUnit,
            'localeDefinition' => [
                'currency' => $this->_getLocaleDefinitionCurrency(),
            ],
        ]);
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns D3 currency format locale definition.
     *
     * @return array
     */
    private function _getLocaleDefinitionCurrency(): array
    {
        $settings = Stripe::$app->settings->getSettings();
        $currency = $settings->defaultCurrency;

        $currencySymbol = Craft::$app->getLocale()->getCurrencySymbol($currency);
        $currencyFormat = Craft::$app->getLocale()->getNumberPattern(Locale::STYLE_CURRENCY);

        if (strpos($currencyFormat, ';') > 0) {
            $currencyFormatArray = explode(';', $currencyFormat);
            $currencyFormat = $currencyFormatArray[0];
        }

        $pattern = '/[#0,.]/';
        $replacement = '';
        $currencyFormat = preg_replace($pattern, $replacement, $currencyFormat);

        if (strpos($currencyFormat, '¤') === 0) {
            // symbol at beginning
            $currencyD3Format = [str_replace('¤', $currencySymbol, $currencyFormat), ''];
        } else {
            // symbol at the end
            $currencyD3Format = ['', str_replace('¤', $currencySymbol, $currencyFormat)];
        }

        return $currencyD3Format;
    }
}
