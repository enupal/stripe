<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\exceptions;

use yii\base\Exception;

abstract class BaseApiException extends Exception
{
    /**
     * @return int
     */
    public function getHttpCode(): int
    {
        return 200;
    }
}
