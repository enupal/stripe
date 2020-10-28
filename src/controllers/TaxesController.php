<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\controllers;

use craft\web\Controller as BaseController;
use Craft;
use enupal\stripe\Stripe;

class TaxesController extends BaseController
{
    /**
     * @return \yii\web\Response
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionDelete()
    {
        $this->requirePostRequest();

        $taxId = Craft::$app->request->getRequiredBodyParam('id');

        if (!Stripe::$app->taxes->archiveById($taxId)) {
            return $this->asJson(null);
        }

        return $this->asJson(['success' => true]);
    }
}
