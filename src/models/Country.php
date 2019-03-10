<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\models;

use craft\base\Model;
use craft\helpers\UrlHelper;

class Country extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var int ID
     */
    public $id;

    /**
     * @var string Name
     */
    public $name;

    /**
     * @var string ISO code
     */
    public $iso;

    /**
     * @var bool State Required
     */
    public $isStateRequired;

    // Public Methods
    // =========================================================================

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->name;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['iso', 'name'], 'required'],
            [['iso'], 'string', 'length' => [2]],
        ];
    }

    /**
     * @return string
     */
    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('enupal-stripe/settings/countries/' . $this->id);
    }
}
