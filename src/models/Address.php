<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\models;

use Craft;
use craft\base\Model;
use enupal\stripe\Stripe as Plugin;
use craft\helpers\UrlHelper;

/**
 * Address Model
 *
 * @property Country $country
 * @property string $countryText
 * @property string $cpEditUrl
 * @property string $fullName
 * @property string $stateText
 * @property string $abbreviationText
 */
class Address extends Model
{
    /**
     * @var int Address ID
     */
    public $id;

    /**
     * @var string Attention
     */
    public $attention;

    /**
     * @var string Title
     */
    public $title;

    /**
     * @var string First Name
     */
    public $firstName;

    /**
     * @var string Last Name
     */
    public $lastName;

    /**
     * @var string Address Line 1
     */
    public $address1;

    /**
     * @var string Address Line 2
     */
    public $address2;

    /**
     * @var string City
     */
    public $city;

    /**
     * @var string Zip
     */
    public $zipCode;

    /**
     * @var string Phone
     */
    public $phone;

    /**
     * @var string Alternative Phone
     */
    public $alternativePhone;

    /**
     * @var string Business Name
     */
    public $businessName;

    /**
     * @var string Business Tax ID
     */
    public $businessTaxId;

    /**
     * @var string Business ID
     */
    public $businessId;

    /**
     * @var string State Name
     */
    public $stateName;

    /**
     * @var int Country ID
     */
    public $countryId;

    // Public Methods
    // =========================================================================

    /**
     * @return string
     */
    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('enupal-stripe/addresses/' . $this->id);
    }

    /**
     * @inheritdoc
     */
    public function attributes(): array
    {
        $names = parent::attributes();
        $names[] = 'fullName';
        $names[] = 'countryText';
        $names[] = 'stateText';
        $names[] = 'stateValue';
        $names[] = 'abbreviationText';
        return $names;
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'country',
            'state',
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        $labels = parent::attributeLabels();
        $labels['firstName'] = Craft::t('enupal-stripe', 'First Name');
        $labels['lastName'] = Craft::t('enupal-stripe', 'Last Name');
        $labels['attention'] = Craft::t('enupal-stripe', 'Attention');
        $labels['title'] = Craft::t('enupal-stripe', 'Title');
        $labels['address1'] = Craft::t('enupal-stripe', 'Address 1');
        $labels['address2'] = Craft::t('enupal-stripe', 'Address 2');
        $labels['city'] = Craft::t('enupal-stripe', 'City');
        $labels['zipCode'] = Craft::t('enupal-stripe', 'Zip Code');
        $labels['phone'] = Craft::t('enupal-stripe', 'Phone');
        $labels['alternativePhone'] = Craft::t('enupal-stripe', 'Alternative Phone');
        $labels['businessName'] = Craft::t('enupal-stripe', 'Business Name');
        $labels['businessId'] = Craft::t('enupal-stripe', 'Business ID');
        $labels['businessTaxId'] = Craft::t('enupal-stripe', 'Business Tax ID');
        $labels['countryId'] = Craft::t('enupal-stripe', 'State');
        $labels['stateName'] = Craft::t('enupal-stripe', 'State');
        return $labels;
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['firstName'], 'required'];
        $rules[] = [['countryId'], 'required'];

        return $rules;
    }

    /**
     * @return string
     */
    public function getFullName(): string
    {
        $firstName = trim($this->firstName);
        $lastName = trim($this->lastName);

        return $firstName . ($firstName && $lastName ? ' ' : '') . $lastName;
    }

    /**
     * @return string
     */
    public function getCountryText(): string
    {
        return $this->countryId ? $this->getCountry()->name : '';
    }

    /**
     * @return Country|null
     */
    public function getCountry()
    {
        return $this->countryId ? Plugin::$app->countries->getCountryById($this->countryId) : null;
    }

    /**
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\SyntaxError
     */
    public function getAddressAsHtml(): string
    {
        $address = $this;
            $addressHtml = "<address>{{ shipping.getFullName() }}<br>{{ shipping.address1 }}<br>{{ shipping.city }}, {{ shipping.stateName }} {{ shipping.zipCode }}<br>{{ shipping.getCountryText() }}</address>";
        $addressHtml = Craft::$app->getView()->renderString($addressHtml, ['shipping' => $address]);

        return $addressHtml;
    }
}
