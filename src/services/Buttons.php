<?php
/**
 * EnupalStripe plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\stripe\services;

use Craft;
use craft\base\Field;
use craft\fields\Dropdown;
use craft\fields\Lightswitch;
use craft\fields\Matrix;
use craft\fields\Number;
use craft\fields\PlainText;
use craft\fields\Table;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use enupal\stripe\enums\SubscriptionType;
use enupal\stripe\web\assets\StripeAsset;
use enupal\stripe\elements\StripeButton;
use enupal\stripe\enums\AmountType;
use enupal\stripe\enums\DiscountType;
use yii\base\Component;
use enupal\stripe\Stripe as StripePlugin;
use enupal\stripe\elements\StripeButton as StripeElement;
use enupal\stripe\records\StripeButton as StripeButtonRecord;
use craft\helpers\Template as TemplateHelper;

use yii\base\Exception;

class Buttons extends Component
{
    protected $buttonRecord;

    const BASIC_FORM_FIELDS_HANDLE = 'enupalStripeBasicFields';
    const MULTIPLE_PLANS_HANDLE = 'enupalMultiplePlans';

    /**
     * Returns a StripeButton model if one is found in the database by id
     *
     * @param int $id
     * @param int $siteId
     *
     * @return null|StripeButton|\craft\base\ElementInterface
     */
    public function getButtonById(int $id, int $siteId = null)
    {
        $button = Craft::$app->getElements()->getElementById($id, StripeElement::class, $siteId);

        return $button;
    }

    /**
     * Returns a StripeButton model if one is found in the database by handle
     *
     * @param string $handle
     * @param int    $siteId
     *
     * @return null|\craft\base\ElementInterface|array
     */
    public function getButtonBySku($handle, int $siteId = null)
    {
        $query = StripeElement::find();
        $query->handle($handle);
        $query->siteId($siteId);

        return $query->one();
    }

    /**
     * Returns all Buttons
     *
     * @return null|StripeElement[]
     */
    public function getAllButtons()
    {
        $query = StripeElement::find();

        return $query->all();
    }

    /**
     * @param $button StripeElement
     *
     * @throws \Exception
     * @return bool
     * @throws \Throwable
     */
    public function saveButton(StripeElement $button)
    {
        $isNewForm = true;
        if ($button->id) {
            $buttonRecord = StripeButtonRecord::findOne($button->id);
            $isNewForm = false;

            if (!$buttonRecord) {
                throw new Exception(StripePlugin::t('No StripeButton exists with the ID “{id}”', ['id' => $button->id]));
            }
        }

        if ($button->enableSubscriptions){
            if ($button->subscriptionType == SubscriptionType::SINGLE_PLAN && $button->singlePlanInfo){
                $plan = StripePlugin::$app->plans->getStripePlan($button->singlePlanInfo);
                $button->singlePlanInfo = Json::encode($plan);
            }
        }

        if (!$button->validate()) {
            return false;
        }

        $transaction = Craft::$app->db->beginTransaction();

        try {
            // Set the field context
            Craft::$app->content->fieldContext = $button->getFieldContext();
            if ($isNewForm) {
                $fieldLayout = $button->getFieldLayout();

                // Save the field layout
                Craft::$app->getFields()->saveLayout($fieldLayout);

                // Assign our new layout id info to our form model and records
                $button->fieldLayoutId = $fieldLayout->id;
                $button->setFieldLayout($fieldLayout);
                $button->fieldLayoutId = $fieldLayout->id;
            }

            if (Craft::$app->elements->saveElement($button)) {
                $transaction->commit();
            }
        } catch (\Exception $e) {
            $transaction->rollback();

            throw $e;
        }

        return true;
    }

    /**
     * @return bool|string
     */
    public function getEnupalStripePath()
    {
        $defaultTemplate = Craft::getAlias('@enupal/stripe/templates/_frontend/');

        return $defaultTemplate;
    }

    /**
     * @param StripeElement $button
     *
     * @return StripeElement
     */
    public function populateButtonFromPost(StripeElement $button)
    {
        $request = Craft::$app->getRequest();

        $postFields = $request->getBodyParam('fields');

        $button->setAttributes(/** @scrutinizer ignore-type */
            $postFields, false);

        return $button;
    }

    /**
     * @return array
     */
    public function getCurrencies()
    {
        $currencies = [
            'AED' => 'United Arab Emirates Dirham',
            'AFN' => 'Afghan Afghani', // NON AMEX
            'ALL' => 'Albanian Lek',
            'AMD' => 'Armenian Dram',
            'ANG' => 'Netherlands Antillean Gulden',
            'AOA' => 'Angolan Kwanza', // NON AMEX
            'ARS' => 'Argentine Peso', // non amex
            'AUD' => 'Australian Dollar',
            'AWG' => 'Aruban Florin',
            'AZN' => 'Azerbaijani Manat',
            'BAM' => 'Bosnia & Herzegovina Convertible Mark',
            'BBD' => 'Barbadian Dollar',
            'BDT' => 'Bangladeshi Taka',
            'BIF' => 'Burundian Franc',
            'BGN' => 'Bulgarian Lev',
            'BMD' => 'Bermudian Dollar',
            'BND' => 'Brunei Dollar',
            'BOB' => 'Bolivian Boliviano', // NON AMEX
            'BRL' => 'Brazilian Real', // NON AMEX
            'BSD' => 'Bahamian Dollar',
            'BWP' => 'Botswana Pula',
            'BZD' => 'Belize Dollar',
            'CAD' => 'Canadian Dollar',
            'CDF' => 'Congolese Franc',
            'CHF' => 'Swiss Franc',
            'CLP' => 'Chilean Peso', // NON AMEX
            'CNY' => 'Chinese Renminbi Yuan',
            'COP' => 'Colombian Peso', // NON AMEX
            'CRC' => 'Costa Rican Colón', // NON AMEX
            'CVE' => 'Cape Verdean Escudo', // NON AMEX
            'CZK' => 'Czech Koruna', // NON AMEX
            'DJF' => 'Djiboutian Franc', // NON AMEX
            'DKK' => 'Danish Krone',
            'DOP' => 'Dominican Peso',
            'DZD' => 'Algerian Dinar',
            'EGP' => 'Egyptian Pound',
            'ETB' => 'Ethiopian Birr',
            'EUR' => 'Euro',
            'FJD' => 'Fijian Dollar',
            'FKP' => 'Falkland Islands Pound', // NON AMEX
            'GBP' => 'British Pound',
            'GEL' => 'Georgian Lari',
            'GIP' => 'Gibraltar Pound',
            'GMD' => 'Gambian Dalasi',
            'GNF' => 'Guinean Franc', // NON AMEX
            'GTQ' => 'Guatemalan Quetzal', // NON AMEX
            'GYD' => 'Guyanese Dollar',
            'HKD' => 'Hong Kong Dollar',
            'HNL' => 'Honduran Lempira', // NON AMEX
            'HRK' => 'Croatian Kuna',
            'HTG' => 'Haitian Gourde',
            'HUF' => 'Hungarian Forint', // NON AMEX
            'IDR' => 'Indonesian Rupiah',
            'ILS' => 'Israeli New Sheqel',
            'INR' => 'Indian Rupee', // NON AMEX
            'ISK' => 'Icelandic Króna',
            'JMD' => 'Jamaican Dollar',
            'JPY' => 'Japanese Yen',
            'KES' => 'Kenyan Shilling',
            'KGS' => 'Kyrgyzstani Som',
            'KHR' => 'Cambodian Riel',
            'KMF' => 'Comorian Franc',
            'KRW' => 'South Korean Won',
            'KYD' => 'Cayman Islands Dollar',
            'KZT' => 'Kazakhstani Tenge',
            'LAK' => 'Lao Kip', // NON AMEX
            'LBP' => 'Lebanese Pound',
            'LKR' => 'Sri Lankan Rupee',
            'LRD' => 'Liberian Dollar',
            'LSL' => 'Lesotho Loti',
            'MAD' => 'Moroccan Dirham',
            'MDL' => 'Moldovan Leu',
            'MGA' => 'Malagasy Ariary',
            'MKD' => 'Macedonian Denar',
            'MNT' => 'Mongolian Tögrög',
            'MOP' => 'Macanese Pataca',
            'MRO' => 'Mauritanian Ouguiya',
            'MUR' => 'Mauritian Rupee', // NON AMEX
            'MVR' => 'Maldivian Rufiyaa',
            'MWK' => 'Malawian Kwacha',
            'MXN' => 'Mexican Peso', // NON AMEX
            'MYR' => 'Malaysian Ringgit',
            'MZN' => 'Mozambican Metical',
            'NAD' => 'Namibian Dollar',
            'NGN' => 'Nigerian Naira',
            'NIO' => 'Nicaraguan Córdoba', // NON AMEX
            'NOK' => 'Norwegian Krone',
            'NPR' => 'Nepalese Rupee',
            'NZD' => 'New Zealand Dollar',
            'PAB' => 'Panamanian Balboa', // NON AMEX
            'PEN' => 'Peruvian Nuevo Sol', // NON AMEX
            'PGK' => 'Papua New Guinean Kina',
            'PHP' => 'Philippine Peso',
            'PKR' => 'Pakistani Rupee',
            'PLN' => 'Polish Złoty',
            'PYG' => 'Paraguayan Guaraní', // NON AMEX
            'QAR' => 'Qatari Riyal',
            'RON' => 'Romanian Leu',
            'RSD' => 'Serbian Dinar',
            'RUB' => 'Russian Ruble',
            'RWF' => 'Rwandan Franc',
            'SAR' => 'Saudi Riyal',
            'SBD' => 'Solomon Islands Dollar',
            'SCR' => 'Seychellois Rupee',
            'SEK' => 'Swedish Krona',
            'SGD' => 'Singapore Dollar',
            'SHP' => 'Saint Helenian Pound', // NON AMEX
            'SLL' => 'Sierra Leonean Leone',
            'SOS' => 'Somali Shilling',
            'SRD' => 'Surinamese Dollar', // NON AMEX
            'STD' => 'São Tomé and Príncipe Dobra',
            'SVC' => 'Salvadoran Colón', // NON AMEX
            'SZL' => 'Swazi Lilangeni',
            'THB' => 'Thai Baht',
            'TJS' => 'Tajikistani Somoni',
            'TOP' => 'Tongan Paʻanga',
            'TRY' => 'Turkish Lira',
            'TTD' => 'Trinidad and Tobago Dollar',
            'TWD' => 'New Taiwan Dollar',
            'TZS' => 'Tanzanian Shilling',
            'UAH' => 'Ukrainian Hryvnia',
            'UGX' => 'Ugandan Shilling',
            'USD' => 'United States Dollar',
            'UYU' => 'Uruguayan Peso', // NON AMEX
            'UZS' => 'Uzbekistani Som',
            'VND' => 'Vietnamese Đồng',
            'VUV' => 'Vanuatu Vatu',
            'WST' => 'Samoan Tala',
            'XAF' => 'Central African Cfa Franc',
            'XCD' => 'East Caribbean Dollar',
            'XOF' => 'West African Cfa Franc', // NON AMEX
            'XPF' => 'Cfp Franc', // NON AMEX
            'YER' => 'Yemeni Rial',
            'ZAR' => 'South African Rand',
            'ZMW' => 'Zambian Kwacha'
        ];

        return $currencies;
    }

    /**
     * @return array
     */
    public function getIsoCurrencies()
    {
        $currencies = $this->getCurrencies();
        $isoCurrencies = [];

        foreach ($currencies as $key => $currency) {
            $isoCurrencies[$key] = $key;
        }

        return $isoCurrencies;
    }

    /**
     * @return array
     */
    public function getLanguageOptions()
    {
        $languages = [
            'en' => 'English (en)',
            'auto' => 'Auto-detect locale',
            'zh' => 'Simplified Chinese',
            'da' => 'Danish',
            'nl' => 'Dutch',
            'fi' => 'Finnish',
            'fr' => 'French',
            'de' => 'German',
            'it' => 'Italian',
            'ja' => 'Japanese',
            'no' => 'Norwegian',
            'es' => 'Spanish',
            'sv' => 'Swedish',
        ];

        return $languages;
    }

    /**
     * @return array
     */
    public function getSubscriptionsTypes()
    {
        $options = [
            SubscriptionType::SINGLE_PLAN => Craft::t('enupal-stripe', 'Set single plan'),
            SubscriptionType::MULTIPLE_PLANS =>  Craft::t('enupal-stripe', 'Customer chooses plan')
        ];

        return $options;
    }

    /**
     * @param string $name
     * @param string $handle
     *
     * @return StripeElement
     * @throws \Exception
     * @throws \Throwable
     */
    public function createNewButton($name = null, $handle = null): StripeElement
    {
        $button = new StripeElement();
        $name = empty($name) ? 'Button' : $name;
        $handle = empty($handle) ? 'button' : $handle;

        $settings = StripePlugin::$app->settings->getSettings();

        $button->name = $this->getFieldAsNew('name', $name);
        $button->handle = $this->getFieldAsNew('handle', $handle);
        $button->hasUnlimitedStock = 1;
        $button->enableBillingAddress = 0;
        $button->enableShippingAddress = 0;
        $button->customerQuantity = 0;
        $button->buttonClass = 'enupal-stripe-button';
        $button->amountType = AmountType::ONE_TIME_SET_AMOUNT;
        $button->currency = $settings->defaultCurrency ? $settings->defaultCurrency : 'USD';
        $button->enabled = 1;
        $button->language = 'en';

        // Set default variant
        $button = $this->addDefaultVariant($button);

        $this->saveButton($button);

        return $button;
    }

    /**
     * This service allows add the variant to a PayPal button
     *
     * @param StripeElement $button
     *
     * @return StripeElement|null
     */
    public function addDefaultVariant(StripeElement $button)
    {
        if (is_null($button)) {
            return null;
        }

        $currentFieldContext = Craft::$app->getContent()->fieldContext;
        Craft::$app->getContent()->fieldContext = 'enupalStripe:';

        $matrixBasicField = Craft::$app->fields->getFieldByHandle(self::BASIC_FORM_FIELDS_HANDLE);
        $matrixMultiplePlans = Craft::$app->fields->getFieldByHandle(self::MULTIPLE_PLANS_HANDLE);
        // Give back the current field context
        Craft::$app->getContent()->fieldContext = $currentFieldContext;

        if (is_null($matrixBasicField) || is_null($matrixMultiplePlans)) {
            // Can't add variants to this button (Someone delete the fields)
            // Let's not throw an exception and just return the Button element with not variants
            Craft::error("Can't add variants to Stripe Button", __METHOD__);
            return $button;
        }

        // Create a tab
        $tabName = "Tab1";
        $requiredFields = [];
        $postedFieldLayout = [];

        // Add our variant fields
        if ($matrixBasicField->id != null) {
            $postedFieldLayout[$tabName][] = $matrixBasicField->id;
        }

        if ($matrixMultiplePlans->id != null) {
            $postedFieldLayout[$tabName][] = $matrixMultiplePlans->id;
        }

        // Set the field layout
        $fieldLayout = Craft::$app->fields->assembleLayout($postedFieldLayout, $requiredFields);

        $fieldLayout->type = StripeButton::class;
        // Set the tab to the form
        $button->setFieldLayout($fieldLayout);

        return $button;
    }

    /**
     * Add the default two Matrix fields for variants
     *
     * @throws \Throwable
     */
    public function createDefaultVariantFields()
    {
        $matrixBasicField = $this->createFormFieldsMatrixField();
        $multiplePlansMatrixField = $this->createMultiplePlansMatrixField();
        // Save our fields
        $currentFieldContext = Craft::$app->getContent()->fieldContext;
        Craft::$app->getContent()->fieldContext = 'enupalStripe:';
        Craft::$app->fields->saveField($matrixBasicField);
        Craft::$app->fields->saveField($multiplePlansMatrixField);
        // Give back the current field context
        Craft::$app->getContent()->fieldContext = $currentFieldContext;
    }

    /**
     * Delete all fields created when installing
     */
    public function deleteVariantFields()
    {
        $currentFieldContext = Craft::$app->getContent()->fieldContext;
        Craft::$app->getContent()->fieldContext = 'enupalStripe:';

        $matrixBasicField = Craft::$app->fields->getFieldByHandle(self::BASIC_FORM_FIELDS_HANDLE);
        $matrixMultiplePlans = Craft::$app->fields->getFieldByHandle(self::MULTIPLE_PLANS_HANDLE);

        if ($matrixBasicField) {
            Craft::$app->fields->deleteFieldById($matrixBasicField->id);
        }

        if ($matrixMultiplePlans) {
            Craft::$app->fields->deleteFieldById($matrixMultiplePlans->id);
        }
        // Give back the current field context
        Craft::$app->getContent()->fieldContext = $currentFieldContext;
    }

    /**
     * Create a secuencial string for the "name" and "handle" fields if they are already taken
     *
     * @param string
     * @param string
     *
     * @return null|string
     */
    public function getFieldAsNew($field, $value)
    {
        $i = 1;
        $band = true;
        do {
            $newField = $field == "handle" ? $value.$i : $value." ".$i;
            $button = $this->getFieldValue($field, $newField);
            if (is_null($button)) {
                $band = false;
            }

            $i++;
        } while ($band);

        return $newField;
    }

    /**
     * Returns the value of a given field
     *
     * @param string $field
     * @param string $value
     *
     * @return StripeButtonRecord
     */
    public function getFieldValue($field, $value)
    {
        $result = StripeButtonRecord::findOne([$field => $value]);

        return $result;
    }

    /**
     * @return array
     */
    public function getDiscountOptions()
    {
        $types = [];
        $types[DiscountType::RATE] = StripePlugin::t('Rate (%)');
        $types[DiscountType::AMOUNT] = StripePlugin::t('Amount');

        return $types;
    }

    /**
     * @return array
     */
    public function getAmountTypeOptions()
    {
        $types = [];
        $types[AmountType::ONE_TIME_SET_AMOUNT] = StripePlugin::t('One-Time set amount');
        $types[AmountType::ONE_TIME_CUSTOM_AMOUNT] = StripePlugin::t('One-Time custom amount');

        return $types;
    }

    /**
     * Returns a complete Stripe Button for display in template
     *
     * @param string     $handle
     * @param array|null $options
     *
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function getButtonHtml($handle, array $options = null)
    {
        $button = StripePlugin::$app->buttons->getButtonBySku($handle);
        $templatePath = StripePlugin::$app->buttons->getEnupalStripePath();
        $buttonHtml = null;
        $settings = StripePlugin::$app->settings->getSettings();

        if (!$settings->testPublishableKey || !$settings->livePublishableKey) {
            return StripePlugin::t("Please add a valid Stripe account in the plugin settings");
        }

        if ($button) {
            if (!$button->hasUnlimitedStock && (int)$button->quantity < 0) {
                $buttonHtml = '<span class="error">Out of Stock</span>';

                return TemplateHelper::raw($buttonHtml);
            }

            $view = Craft::$app->getView();

            $view->setTemplatesPath($templatePath);

            $view->registerJsFile("https://checkout.stripe.com/checkout.js");
            $view->registerAssetBundle(StripeAsset::class);

            $buttonHtml = $view->renderTemplate(
                'button', [
                    'button' => $button,
                    'settings' => $settings,
                    'options' => $options
                ]
            );

            $view->setTemplatesPath(Craft::$app->path->getSiteTemplatesPath());
        } else {
            $buttonHtml = StripePlugin::t("Stripe Button not found or disabled");
        }

        return TemplateHelper::raw($buttonHtml);
    }

    /**
     * @param StripeElement $button
     *
     * @return bool
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\db\Exception
     */
    public function deleteButton(StripeElement $button)
    {
        $transaction = Craft::$app->db->beginTransaction();

        try {
            // Delete the Button Element
            $success = Craft::$app->elements->deleteElementById($button->id);

            if (!$success) {
                $transaction->rollback();
                Craft::error("Couldn’t delete Stripe Button", __METHOD__);

                return false;
            }

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollback();

            throw $e;
        }

        return true;
    }

    /**
     * @param $label
     *
     * @return string
     */
    public function labelToHandle($label)
    {
        $handle = FileHelper::sanitizeFilename(
            $label,
            [
                'asciiOnly' => true,
                'separator' => '-'
            ]
        );

        return $handle;
    }

    /**
     * @return \craft\base\FieldInterface
     */
    private function createFormFieldsMatrixField()
    {
        $fieldsService = Craft::$app->getFields();

        $matrixSettings = [
            'minBlocks' => "",
            'maxBlocks' => "",
            'blockTypes' => [
                'new1' => [
                    'name' => 'Single Line',
                    'handle' => 'singleLine',
                    'fields' => [
                        'new1' => [
                            'type' => PlainText::class,
                            'name' => 'Label',
                            'handle' => 'label',
                            'instructions' => '',
                            'required' => 1,
                            'typesettings' => '{"placeholder":"","code":"","multiline":"","initialRows":"4","charLimit":"","columnType":"text"}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                        'new2' => [
                            'type' => PlainText::class,
                            'name' => 'Placeholder',
                            'handle' => 'placeholder',
                            'instructions' => '',
                            'required' => 0,
                            'typesettings' => '{"placeholder":"","code":"","multiline":"","initialRows":"4","charLimit":"","columnType":"text"}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ]
                    ]
                ],
                'new2' => [
                    'name' => 'Paragraph',
                    'handle' => 'paragraph',
                    'fields' => [
                        'new1' => [
                            'type' => PlainText::class,
                            'name' => 'Label',
                            'handle' => 'label',
                            'instructions' => '',
                            'required' => 1,
                            'typesettings' => '{"placeholder":"","code":"","multiline":"","initialRows":"4","charLimit":"","columnType":"text"}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                        'new2' => [
                            'type' => PlainText::class,
                            'name' => 'Placeholder',
                            'handle' => 'placeholder',
                            'instructions' => '',
                            'required' => 0,
                            'typesettings' => '{"placeholder":"","code":"","multiline":"","initialRows":"4","charLimit":"","columnType":"text"}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                        'new3' => [
                            'type' => Number::class,
                            'name' => 'Initial Rows',
                            'handle' => 'initialRows',
                            'instructions' => '',
                            'required' => 1,
                            'typesettings' => '{"min":"2","max":null,"decimals":"0","size":null}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ]
                    ]
                ],
                'new3' => [
                    'name' => 'Dropdown',
                    'handle' => 'dropdown',
                    'fields' => [
                        'new1' => [
                            'type' => PlainText::class,
                            'name' => 'Label',
                            'handle' => 'label',
                            'instructions' => '',
                            'required' => 1,
                            'typesettings' => '{"placeholder":"","code":"","multiline":"","initialRows":"4","charLimit":"","columnType":"text"}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                        'new2' => [
                            'type' => Table::class,
                            'name' => 'Options',
                            'handle' => 'options',
                            'required' => '1',
                            'instructions' => '',
                            'typesettings' => '{"addRowLabel":"Add an option","maxRows":"","minRows":"1","columns":{"col1":{"heading":"Option Label","handle":"optionLabel","width":"","type":"singleline"},"col2":{"heading":"Value","handle":"value","width":"","type":"singleline"}},"defaults":{"row1":{"col1":"","col2":""}},"columnType":"text"}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                    ]
                ],
                'new4' => [
                    'name' => 'Radio Buttons',
                    'handle' => 'radioButtons',
                    'fields' => [
                        'new1' => [
                            'type' => PlainText::class,
                            'name' => 'Label',
                            'handle' => 'label',
                            'instructions' => '',
                            'required' => 1,
                            'typesettings' => '{"placeholder":"","code":"","multiline":"","initialRows":"4","charLimit":"","columnType":"text"}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                        'new2' => [
                            'type' => Table::class,
                            'name' => 'Options',
                            'handle' => 'options',
                            'required' => '1',
                            'instructions' => '',
                            'typesettings' => '{"addRowLabel":"Add an option","maxRows":"","minRows":"1","columns":{"col1":{"heading":"Option Label","handle":"optionLabel","width":"","type":"singleline"},"col2":{"heading":"Value","handle":"value","width":"","type":"singleline"}},"defaults":{"row1":{"col1":"","col2":""}},"columnType":"text"}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                    ]
                ],
                'new5' => [
                    'name' => 'Number',
                    'handle' => 'number',
                    'fields' => [
                        'new1' => [
                            'type' => PlainText::class,
                            'name' => 'Label',
                            'handle' => 'label',
                            'instructions' => '',
                            'required' => 1,
                            'typesettings' => '{"placeholder":"","code":"","multiline":"","initialRows":"4","charLimit":"","columnType":"text"}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                        'new2' => [
                            'type' => Number::class,
                            'name' => 'Min Value',
                            'handle' => 'minValue',
                            'required' => 0,
                            'instructions' => '',
                            'typesettings' => '{"min":null,"max":null,"decimals":"0","size":null}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                        'new3' => [
                            'type' => Number::class,
                            'name' => 'Max Value',
                            'handle' => 'maxValue',
                            'required' => 0,
                            'instructions' => '',
                            'typesettings' => '{"min":null,"max":null,"decimals":"0","size":null}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                    ]
                ],
                'new6' => [
                    'name' => 'CheckBoxes',
                    'handle' => 'checkboxes',
                    'fields' => [
                        'new1' => [
                            'type' => PlainText::class,
                            'name' => 'Label',
                            'handle' => 'label',
                            'instructions' => '',
                            'required' => 1,
                            'typesettings' => '{"placeholder":"","code":"","multiline":"","initialRows":"4","charLimit":"","columnType":"text"}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                        'new2' => [
                            'type' => Table::class,
                            'name' => 'Options',
                            'handle' => 'options',
                            'required' => '1',
                            'instructions' => '',
                            'typesettings' => '{"addRowLabel":"Add an option","maxRows":"","minRows":"1","columns":{"col1":{"heading":"Option Label","handle":"optionLabel","width":"","type":"singleline"},"col2":{"heading":"Value","handle":"value","width":"","type":"singleline"}},"defaults":{"row1":{"col1":"","col2":""}},"columnType":"text"}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                    ]
                ],
            ]
        ];

        $currentFieldContext = Craft::$app->getContent()->fieldContext;
        Craft::$app->getContent()->fieldContext = 'enupalStripe:';
        $matrixBasicField = Craft::$app->fields->getFieldByHandle(self::BASIC_FORM_FIELDS_HANDLE);
        Craft::$app->getContent()->fieldContext = $currentFieldContext;

        if (!is_null($matrixBasicField)){
            // For some reason the field already exits
            return $matrixBasicField;
        }

        // Our basic fields is a matrix field
        $matrixBasicField = $fieldsService->createField([
            'type' => Matrix::class,
            'name' => 'Basic Form Fields',
            'context' => 'enupalStripe:',
            'handle' => self::BASIC_FORM_FIELDS_HANDLE,
            'settings' => json_encode($matrixSettings),
            'instructions' => 'All data saved are stored as “metadata” with each Stripe payment record within your Stripe dashboard.',
            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
        ]);

        return $matrixBasicField;
    }

    /**
     * @return \craft\base\FieldInterface
     */
    private function createMultiplePlansMatrixField()
    {
        $fieldsService = Craft::$app->getFields();

        $matrixSettings = [
            'minBlocks' => "",
            'maxBlocks' => "",
            'blockTypes' => [
                'new1' => [
                    'name' => 'Subscription Plan',
                    'handle' => 'subscriptionPlan',
                    'fields' => [
                        'new1' => [
                            'type' => Dropdown::class,
                            'name' => 'Select Plan',
                            'handle' => 'selectPlan',
                            'instructions' => "Can't see your plans? Go to Settings -> Subscriptions and click on Refresh Plans",
                            'required' => 1,
                            'typesettings' => '{"options":[{"label":"Select Plan...","value":"","default":""}]}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                        'new2' => [
                            'type' => PlainText::class,
                            'name' => 'Custom Label',
                            'handle' => 'customLabel',
                            'instructions' => 'Override the default text displayed for each plan (i.e. “Nickname amount/interval”)',
                            'required' => 0,
                            'typesettings' => '{"placeholder":"Awesome Plan $35.00/month","code":"","multiline":"","initialRows":"4","charLimit":"","columnType":"text"}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                        'new3' => [
                            'type' => Number::class,
                            'name' => 'Setup Fee',
                            'handle' => 'setupFee',
                            'instructions' => 'Setup Fee for the first payment',
                            'required' => 0,
                            'typesettings' => '{"min":null,"max":null,"decimals":"2","size":null}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                        'new4' => [
                            'type' => Lightswitch::class,
                            'name' => 'Default',
                            'handle' => 'default',
                            'instructions' => 'Please make sure that just one default is enabled',
                            'required' => 0,
                            'typesettings' => '{"default":""}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ]

                    ]
                ]
            ]
        ];

        $currentFieldContext = Craft::$app->getContent()->fieldContext;
        Craft::$app->getContent()->fieldContext = 'enupalStripe:';
        $matrixMultiplePlansField = Craft::$app->fields->getFieldByHandle(self::MULTIPLE_PLANS_HANDLE);
        Craft::$app->getContent()->fieldContext = $currentFieldContext;

        if (!is_null($matrixMultiplePlansField)){
            // For some reason the field already exits
            return $matrixMultiplePlansField;
        }

        // Our multiple plans matrix field
        // Let the customer can sign up for one of several available plans (and optionally set a custom amount).
        $matrixMultiplePlansField = $fieldsService->createField([
            'type' => Matrix::class,
            'name' => 'Add Plan',
            'context' => 'enupalStripe:',
            'handle' => self::MULTIPLE_PLANS_HANDLE,
            'settings' => json_encode($matrixSettings),
            'instructions' => 'Customize the plans that the customer should select',
            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
        ]);

        return $matrixMultiplePlansField;
    }


}
