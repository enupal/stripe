<?php
/**
 * EnupalStripe plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\stripe\controllers;

use craft\web\Controller as BaseController;

class WebhookController extends BaseController
{
    public function actionListener()
    {
        // @todo add support for stripe webhooks
        return $this->asJson(['success'=> false]);
    }
}
