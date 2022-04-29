<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\exceptions;

class CartItemException extends BaseApiException
{
    /**
     * @inerhitDoc
     */
    public function getHttpCode(): int
    {
        return 400;
    }
}
