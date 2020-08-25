<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use Craft;
use craft\base\Field;
use craft\db\Query;
use craft\fields\Dropdown;
use craft\fields\Lightswitch;
use craft\fields\Matrix;
use craft\fields\Number;
use craft\fields\PlainText;
use craft\fields\Table;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use enupal\stripe\enums\CheckoutPaymentType;
use enupal\stripe\enums\PaymentType;
use enupal\stripe\enums\SubscriptionType;
use enupal\stripe\events\AfterPopulatePaymentFormEvent;
use enupal\stripe\web\assets\StripeAsset;
use enupal\stripe\elements\PaymentForm;
use enupal\stripe\enums\AmountType;
use enupal\stripe\web\assets\StripeElementsAsset;
use yii\base\Component;
use enupal\stripe\Stripe as StripePlugin;
use enupal\stripe\elements\PaymentForm as StripeElement;
use enupal\stripe\records\PaymentForm as PaymentFormRecord;
use craft\helpers\Template as TemplateHelper;

use yii\base\Exception;

class PaymentForms extends Component
{
    /**
     * @event OrderCompleteEvent The event that is triggered after a payment is made
     *
     * Plugins can get notified after populate payment form
     *
     * ```php
     * use enupal\stripe\events\AfterPopulatePaymentFormEvent;
     * use enupal\stripe\services\PaymentForms;
     * use yii\base\Event;
     *
     * Event::on(PaymentForms::class, PaymentForms::EVENT_AFTER_POPULATE, function(AfterPopulatePaymentFormEvent $e) {
     *      $paymentForm = $e->paymentForm;
     *     // Do something
     * });
     * ```
     */
    const EVENT_AFTER_POPULATE = 'afterPopulatePaymentForm';

    protected $paymentFormRecord;

    const BASIC_FORM_FIELDS_HANDLE = 'enupalStripeBasicFields';
    const MULTIPLE_PLANS_HANDLE = 'enupalMultiplePlans';

    /**
     * @var array
     */
    protected static $fieldVariables = [];

    /**
     * Returns a PaymentForm model if one is found in the database by id
     *
     * @param int $id
     * @param int $siteId
     *
     * @return null|PaymentForm
     */
    public function getPaymentFormById(int $id, int $siteId = null)
    {
        /** @var PaymentForm $paymentForm */
        $paymentForm = Craft::$app->getElements()->getElementById($id, StripeElement::class, $siteId);

        return $paymentForm;
    }

    /**
     * Returns a PaymentForm model if one is found in the database by handle
     *
     * @param string $handle
     * @param int $siteId
     *
     * @return null|StripeElement
     */
    public function getPaymentFormBySku($handle, int $siteId = null)
    {
        $query = StripeElement::find();
        $query->handle($handle);
        $query->siteId($siteId);

        return $query->one();
    }

    /**
     * Returns all PaymentForms
     *
     * @return null|StripeElement[]
     */
    public function getAllPaymentForms()
    {
        $query = StripeElement::find();

        return $query->all();
    }

    /**
     * @param $paymentForm StripeElement
     *
     * @param bool $updateSinglePlanInfo
     * @return bool
     * @throws Exception
     * @throws \Throwable
     */
    public function savePaymentForm(StripeElement $paymentForm, bool $updateSinglePlanInfo = true)
    {
        $isNewForm = true;
        if ($paymentForm->id) {
            $paymentFormRecord = PaymentFormRecord::findOne($paymentForm->id);
            $isNewForm = false;

            if (!$paymentFormRecord) {
                throw new Exception(StripePlugin::t('No PaymentForm exists with the ID “{id}”', ['id' => $paymentForm->id]));
            }
        }

        if ($paymentForm->enableSubscriptions && $updateSinglePlanInfo) {
            if ($paymentForm->subscriptionType == SubscriptionType::SINGLE_PLAN && $paymentForm->singlePlanInfo) {
                $plan = StripePlugin::$app->plans->getStripePlan($paymentForm->singlePlanInfo);
                $paymentForm->singlePlanInfo = Json::encode($plan);
            }
        }

        if (!$paymentForm->validate()) {
            return false;
        }

        $transaction = Craft::$app->db->beginTransaction();

        try {
            // Set the field context
            Craft::$app->content->fieldContext = $paymentForm->getFieldContext();
            if ($isNewForm) {
                $fieldLayout = $paymentForm->getFieldLayout();

                // Save the field layout
                Craft::$app->getFields()->saveLayout($fieldLayout);

                // Assign our new layout id info to our form model and records
                $paymentForm->fieldLayoutId = $fieldLayout->id;
                $paymentForm->setFieldLayout($fieldLayout);
                $paymentForm->fieldLayoutId = $fieldLayout->id;
            }

            if (Craft::$app->elements->saveElement($paymentForm)) {
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
     * @param $paymentForm
     *
     * @return array
     * @throws Exception
     */
    public function getFormTemplatePaths(StripeElement $paymentForm)
    {
        $templates = [];
        $templateFolderOverride = '';
        $defaultTemplate = $this->getEnupalStripePath();

        if ($paymentForm->enableTemplateOverrides && $paymentForm->templateOverridesFolder) {
            $templateFolderOverride = $this->getSitePath($paymentForm->templateOverridesFolder);
        }

        $settings = StripePlugin::$app->settings->getSettings();
        $mainTemplate = $settings->useSca ? 'paymentFormSca' : 'paymentForm';

        // Set our defaults
        $templates['paymentForm'] = $defaultTemplate;
        $templates['address'] = $defaultTemplate;
        $templates['fields'] = $defaultTemplate . DIRECTORY_SEPARATOR . 'fields';
        $templates['multipleplans'] = $defaultTemplate . DIRECTORY_SEPARATOR . 'multipleplans';

        // See if we should override our defaults
        if ($templateFolderOverride) {

            $formTemplate = $templateFolderOverride . DIRECTORY_SEPARATOR . $mainTemplate;
            $addressTemplate = $templateFolderOverride . DIRECTORY_SEPARATOR . 'address';
            $fieldsFolder = $templateFolderOverride . DIRECTORY_SEPARATOR . 'fields';
            $multiplePlansFolder = $templateFolderOverride . DIRECTORY_SEPARATOR . 'multipleplans';
            $basePath = $templateFolderOverride . DIRECTORY_SEPARATOR;

            foreach (Craft::$app->getConfig()->getGeneral()->defaultTemplateExtensions as $extension) {

                if (file_exists($formTemplate . '.' . $extension)) {
                    $templates['paymentForm'] = $basePath;
                }

                if (file_exists($addressTemplate . '.' . $extension)) {
                    $templates['address'] = $basePath;
                }

                if (file_exists($fieldsFolder)) {
                    $templates['fields'] = $basePath . 'fields';
                }

                if (file_exists($multiplePlansFolder)) {
                    $templates['multipleplans'] = $basePath . 'multipleplans';
                }
            }
        }

        return $templates;
    }

    /**
     * @param $path
     *
     * @return string
     * @throws \yii\base\Exception
     */
    private function getSitePath($path)
    {
        return Craft::$app->path->getSiteTemplatesPath() . DIRECTORY_SEPARATOR . $path;
    }

    /**
     * @param StripeElement $paymentForm
     *
     * @return StripeElement
     */
    public function populatePaymentFormFromPost(StripeElement $paymentForm)
    {
        $request = Craft::$app->getRequest();

        $postFields = $request->getBodyParam('fields');

        $postFields['amount'] = $this->getAmountAsFloat($postFields['amount']);
        $postFields['minimumAmount'] = $this->getAmountAsFloat($postFields['minimumAmount'] ?? 0);
        $postFields['singlePlanSetupFee'] = $this->getAmountAsFloat($postFields['singlePlanSetupFee'] ?? 0);
        $postFields['customPlanMinimumAmount'] = $this->getAmountAsFloat($postFields['customPlanMinimumAmount'] ?? 0);
        $postFields['customPlanDefaultAmount'] = $this->getAmountAsFloat($postFields['customPlanDefaultAmount']?? 0);

        $paymentForm->setAttributes(/** @scrutinizer ignore-type */
            $postFields, false);

        if (isset($postFields[PaymentForms::BASIC_FORM_FIELDS_HANDLE])) {
            $paymentForm->setFieldValue(PaymentForms::BASIC_FORM_FIELDS_HANDLE, $postFields[PaymentForms::BASIC_FORM_FIELDS_HANDLE]);
        }

        if (isset($postFields[PaymentForms::MULTIPLE_PLANS_HANDLE])) {
            $paymentForm->setFieldValue(PaymentForms::MULTIPLE_PLANS_HANDLE, $postFields[PaymentForms::MULTIPLE_PLANS_HANDLE]);
        }

        $this->triggerAfterPopulatePaymentFormEvent($paymentForm);

        return $paymentForm;
    }

    /**
     * Disabled the payment form is skipAdminReview is disabled
     * @param $paymentForm
     * @return bool
     */
    public function handleVendorPaymentForms($paymentForm)
    {
        $vendor = StripePlugin::$app->vendors->getCurrentVendor();
        if ($vendor === null) {
            return false;
        }

        if (!$vendor->skipAdminReview) {
            $paymentForm->enabled = false;
        }

        return true;
    }

    /**
     * @param $paymentForm
     */
    public function triggerAfterPopulatePaymentFormEvent($paymentForm)
    {
        Craft::info("Triggering After Populate Payment Form", __METHOD__);

        $event = new AfterPopulatePaymentFormEvent([
            'paymentForm' => $paymentForm
        ]);

        $this->trigger(self::EVENT_AFTER_POPULATE, $event);
    }

    /**
     * @param $money
     * @return float
     */
    public function getAmountAsFloat($money)
    {
        $cleanString = preg_replace('/([^0-9\.,])/i', '', $money);
        $onlyNumbersString = preg_replace('/([^0-9])/i', '', $money);

        $separatorsCountToBeErased = strlen($cleanString) - strlen($onlyNumbersString) - 1;

        $stringWithCommaOrDot = preg_replace('/([,\.])/', '', $cleanString, $separatorsCountToBeErased);
        $removedThousendSeparator = preg_replace('/(\.|,)(?=[0-9]{3,}$)/', '', $stringWithCommaOrDot);

        return (float)str_replace(',', '.', $removedThousendSeparator);
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
            'nb' => 'Norwegian',
            'es' => 'Spanish',
            'sv' => 'Swedish',
            'pt' => 'Portuguese',
            'pl' => 'Polish'
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
            SubscriptionType::MULTIPLE_PLANS => Craft::t('enupal-stripe', 'Customer chooses plan')
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
    public function createNewPaymentForm($name = null, $handle = null): StripeElement
    {
        $paymentForm = new StripeElement();
        $name = empty($name) ? 'Payment Form' : $name;
        $handle = empty($handle) ? 'paymentForm' : $handle;

        $settings = StripePlugin::$app->settings->getSettings();

        $paymentForm->name = $this->getFieldAsNew('name', $name);
        $paymentForm->handle = $this->getFieldAsNew('handle', $handle);
        $paymentForm->hasUnlimitedStock = 1;
        $paymentForm->enableBillingAddress = 0;
        $paymentForm->enableShippingAddress = 0;
        $paymentForm->customerQuantity = 0;
        $paymentForm->buttonClass = 'enupal-stripe-button';
        $paymentForm->amountType = AmountType::ONE_TIME_SET_AMOUNT;
        $paymentForm->currency = $settings->defaultCurrency ? $settings->defaultCurrency : 'USD';
        $paymentForm->enabled = 1;
        $paymentForm->language = 'en';

        // Set default variant
        $paymentForm = $this->addDefaultVariant($paymentForm);

        $this->savePaymentForm($paymentForm);

        return $paymentForm;
    }

    /**
     * It will return a payment form element if allows to vendor (already have a connect).
     *
     * @param int $paymentFormId
     * @param $vendorId
     *
     * @return PaymentForm|null
     */
    public function getVendorPaymentForm($paymentFormId, $vendorId = null)
    {
        $vendor = StripePlugin::$app->vendors->getCurrentVendor();

        if ($vendorId !== null) {
            $vendor = StripePlugin::$app->vendors->getVendorById($vendorId);
        }

        if ($vendor === null) {
            return null;
        }

        if ($vendor->isSuperVendor()) {
            return $this->getPaymentFormById($paymentFormId);
        }

        $connect = StripePlugin::$app->connects->getConnectsByPaymentFormId($paymentFormId, $vendor->id);

        if (empty($connect)) {
            return null;
        }

        return $this->getPaymentFormById($paymentFormId);
    }

    /**
     * @param null $vendorId
     * @return array|\craft\base\ElementInterface[]|null
     */
    public function getPaymentFormsByVendor($vendorId = null)
    {
        $vendor = StripePlugin::$app->vendors->getCurrentVendor();

        if ($vendorId !== null) {
            $vendor = StripePlugin::$app->vendors->getVendorById((int)$vendorId);
        }

        if ($vendor === null) {
            return null;
        }

        $paymentFormIdsArray = [];

        $connects = StripePlugin::$app->connects->getConnectsByVendorId($vendor->id);

        if (empty($connects)) {
            return null;
        }

        foreach ($connects as $connect) {
            if ($connect->allProducts) {
                return PaymentForm::find()->all();
            }

            if ($connect->products) {
                $productsArray = json_decode($connect->products, true);
                foreach ($productsArray as $item) {
                    $paymentFormIdsArray[$item] = 1;
                }
            }
        }

        if (empty($paymentFormIdsArray)) {
            return null;
        }

        $paymentFormIds   = array_keys($paymentFormIdsArray);
        $paymentFormQuery = PaymentForm::find();

        return $paymentFormQuery->where(['elements.id' => $paymentFormIds])->status(null)->all();
    }

    /**
     * This service allows add the variant to a Stripe Payment Form
     *
     * @param StripeElement $paymentForm
     *
     * @return StripeElement|null
     * @throws \yii\db\Exception
     */
    public function addDefaultVariant(StripeElement $paymentForm)
    {
        if (is_null($paymentForm)) {
            return null;
        }

        $currentFieldContext = Craft::$app->getContent()->fieldContext;
        Craft::$app->getContent()->fieldContext = StripePlugin::$app->settings->getFieldContext();

        $matrixBasicField = Craft::$app->fields->getFieldByHandle(self::BASIC_FORM_FIELDS_HANDLE);
        $matrixMultiplePlans = Craft::$app->fields->getFieldByHandle(self::MULTIPLE_PLANS_HANDLE);
        // Give back the current field context
        Craft::$app->getContent()->fieldContext = $currentFieldContext;

        if (is_null($matrixBasicField) || is_null($matrixMultiplePlans)) {
            // Can't add variants to this payment form (Someone delete the fields)
            // Let's not throw an exception and just return the Payment Form element with not variants
            Craft::error("Can't add variants to Stripe Payment Form", __METHOD__);
            return $paymentForm;
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

        $fieldLayout->type = PaymentForm::class;
        // Set the tab to the form
        $paymentForm->setFieldLayout($fieldLayout);

        return $paymentForm;
    }

    /**
     * Delete all fields created when installing
     */
    public function deleteVariantFields()
    {
        $currentFieldContext = Craft::$app->getContent()->fieldContext;

        $stripeFields = (new Query())
            ->select(['id'])
            ->from(["{{%fields}}"])
            ->where(['like', 'context', 'enupalStripe:'])
            ->all();

        Craft::$app->getContent()->fieldContext = StripePlugin::$app->settings->getFieldContext();

        if ($stripeFields) {
            foreach ($stripeFields as $stripeField) {
                if (Craft::$app->fields->deleteFieldById($stripeField['id'])){
                    Craft::info('Stripe Payments Field deleted: '.$stripeField['id'], __METHOD__);
                } else{
                    Craft::info('Unable to delete Stripe Payments Field: '.$stripeField['id'], __METHOD__);
                }
            }
        }

        // Give back the current field context
        Craft::$app->getContent()->fieldContext = $currentFieldContext;

        // Delete also from project config
        $fields = Craft::$app->projectConfig->get('enupalStripe.fields');
        if (is_array($fields) && $fields) {
            Craft::$app->projectConfig->remove('enupalStripe.fields');
        }
    }

    /**
     * Add the default two Matrix fields for variants
     *
     * @throws \Throwable
     */
    public function createDefaultVariantFields()
    {
        $this->createFormFieldsMatrixField();
        $this->createMultiplePlansMatrixField();
    }

    private function saveMatrixFieldOnProjectConfig($field)
    {
        $field->context = StripePlugin::$app->settings->getFieldContext();

        if (!$field->beforeSave($field->getIsNew())) {
            throw new \Exception('The Matrix field doesn\'t want to be saved');
        }

        if (!$field->validate()) {
            throw new \Exception('The Matrix field couldn\'t be saved due to a validation error');
        }

        Craft::$app->fields->prepFieldForSave($field);
        $configData = Craft::$app->fields->createFieldConfig($field);

        Craft::$app->projectConfig->set("enupalStripe.fields.$field->uid", $configData, "Save Stripe Payments Stripe field “{$field->handle}”");
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
            $newField = $field == "handle" ? $value . $i : $value . " " . $i;
            $paymentForm = $this->getFieldValue($field, $newField);
            if (is_null($paymentForm)) {
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
     * @return PaymentFormRecord
     */
    public function getFieldValue($field, $value)
    {
        $result = PaymentFormRecord::findOne([$field => $value]);

        return $result;
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
     * Returns a complete Stripe Payment Form for display in template
     *
     * @param string $handle
     * @param array|null $options
     *
     * @return string
     * @throws Exception
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \yii\base\InvalidConfigException
     */
    public function getPaymentFormHtml($handle, array $options = null)
    {
        $paymentForm = StripePlugin::$app->paymentForms->getPaymentFormBySku($handle);
        $paymentFormHtml = null;
        $settings = StripePlugin::$app->settings->getSettings();
        $mainTemplate = $settings->useSca ? 'paymentFormSca' : 'paymentForm';

        if ($settings->testMode) {
            if (!$settings->testPublishableKey || !$settings->testSecretKey) {
                return StripePlugin::t("Please add the Stripe API keys in the plugin settings for TEST mode");
            }
        } else {
            if (!$settings->livePublishableKey || !$settings->liveSecretKey) {
                return StripePlugin::t("Please add the Stripe API keys in the plugin settings for LIVE mode");
            }
        }

        if ($paymentForm) {
            // Add support for template overrides
            $templatePaths = StripePlugin::$app->paymentForms->getFormTemplatePaths($paymentForm);

            if (!$paymentForm->hasUnlimitedStock && (int)$paymentForm->quantity <= 0) {
                $outOfStockMessage = Craft::t('site', 'Out of Stock');
                $paymentFormHtml = '<span class="error">' . $outOfStockMessage . '</span>';

                return TemplateHelper::raw($paymentFormHtml);
            }

            $view = Craft::$app->getView();

            $view->setTemplatesPath($templatePaths['paymentForm']);
            $loadAssets = isset($options['loadAssets']) ? $options['loadAssets'] : true;

            if ($paymentForm->enableCheckout) {
                if ($settings->useSca) {
                    $view->registerJsFile("https://js.stripe.com/v3/");
                } else {
                    $view->registerJsFile("https://checkout.stripe.com/checkout.js");
                }
                if ($loadAssets) {
                    $view->registerAssetBundle(StripeAsset::class);
                }
            } else {
                $view->registerJsFile("https://js.stripe.com/v3/");
                if ($loadAssets) {
                    $view->registerAssetBundle(StripeElementsAsset::class);
                }
            }

            $paymentTypeIds = json_decode($paymentForm->paymentType, true);

            $paymentFormHtml = $view->renderTemplate(
                $mainTemplate, [
                    'paymentForm' => $paymentForm,
                    'settings' => $settings,
                    'options' => $options,
                    'paymentTypeIds' => $paymentTypeIds
                ]
            );

            $view->setTemplatesPath(Craft::$app->path->getSiteTemplatesPath());
        } else {
            $paymentFormHtml = StripePlugin::t("Stripe Payment Form not found or disabled");
        }

        return TemplateHelper::raw($paymentFormHtml);
    }

    /**
     * @param StripeElement $paymentForm
     *
     * @return bool
     * @throws \Throwable
     */
    public function deletePaymentForm(StripeElement $paymentForm)
    {
        $transaction = Craft::$app->db->beginTransaction();

        try {
            // Delete the orders
            $orders = (new Query())
                ->select(['id'])
                ->from(["{{%enupalstripe_orders}}"])
                ->where(['formId' => $paymentForm->id])
                ->all();

            foreach ($orders as $order) {
                Craft::$app->elements->deleteElementById($order['id']);
            }

            // Delete the Payment Form Element
            $success = Craft::$app->elements->deleteElementById($paymentForm->id);

            if (!$success) {
                $transaction->rollback();
                Craft::error("Couldn’t delete Stripe Payment Form", __METHOD__);

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
     * Removes payment forms and related records from the database given the ids
     *
     * @param $formElements
     *
     * @return bool
     * @throws \Throwable
     */
    public function deleteForms($formElements): bool
    {
        foreach ($formElements as $key => $formElement) {
            $paymentForm = $this->getPaymentFormById($formElement->id);

            if ($paymentForm) {
                $this->deletePaymentForm($paymentForm);
            } else {
                Craft::error("Can't delete the payment form with id: {$formElement->id}", __METHOD__);
            }
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
                'separator' => '_'
            ]
        );

        return $handle;
    }

    /**
     * @return bool
     * @throws \yii\db\Exception
     * @throws \Exception
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
                            'name' => 'Handle',
                            'handle' => 'fieldHandle',
                            'instructions' => '',
                            'required' => 1,
                            'typesettings' => '{"placeholder":"","code":"","multiline":"","initialRows":"4","charLimit":"","columnType":"text"}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                        'new3' => [
                            'type' => PlainText::class,
                            'name' => 'Placeholder',
                            'handle' => 'placeholder',
                            'instructions' => '',
                            'required' => 0,
                            'typesettings' => '{"placeholder":"","code":"","multiline":"","initialRows":"4","charLimit":"","columnType":"text"}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                        'new4' => [
                            'type' => Lightswitch::class,
                            'name' => 'Required',
                            'handle' => 'required',
                            'instructions' => 'This field is required?',
                            'required' => 0,
                            'typesettings' => '{"default":""}',
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
                            'name' => 'Handle',
                            'handle' => 'fieldHandle',
                            'instructions' => '',
                            'required' => 1,
                            'typesettings' => '{"placeholder":"","code":"","multiline":"","initialRows":"4","charLimit":"","columnType":"text"}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                        'new3' => [
                            'type' => PlainText::class,
                            'name' => 'Placeholder',
                            'handle' => 'placeholder',
                            'instructions' => '',
                            'required' => 0,
                            'typesettings' => '{"placeholder":"","code":"","multiline":"","initialRows":"4","charLimit":"","columnType":"text"}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                        'new4' => [
                            'type' => Number::class,
                            'name' => 'Initial Rows',
                            'handle' => 'initialRows',
                            'instructions' => '',
                            'required' => 1,
                            'typesettings' => '{"min":"2","max":null,"decimals":"0","size":null}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                        'new5' => [
                            'type' => Lightswitch::class,
                            'name' => 'Required',
                            'handle' => 'required',
                            'instructions' => 'This field is required?',
                            'required' => 0,
                            'typesettings' => '{"default":""}',
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
                            'type' => PlainText::class,
                            'name' => 'Handle',
                            'handle' => 'fieldHandle',
                            'instructions' => '',
                            'required' => 1,
                            'typesettings' => '{"placeholder":"","code":"","multiline":"","initialRows":"4","charLimit":"","columnType":"text"}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                        'new3' => [
                            'type' => Table::class,
                            'name' => 'Options',
                            'handle' => 'options',
                            'required' => '1',
                            'instructions' => '',
                            'typesettings' => '{"addRowLabel":"Add an option","maxRows":"","minRows":"1","columns":{"col1":{"heading":"Option Label","handle":"optionLabel","width":"","type":"singleline"},"col2":{"heading":"Value","handle":"value","width":"","type":"singleline"}},"defaults":{"row1":{"col1":"","col2":""}},"columnType":"text"}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                        'new4' => [
                            'type' => Lightswitch::class,
                            'name' => 'Required',
                            'handle' => 'required',
                            'instructions' => 'This field is required?',
                            'required' => 0,
                            'typesettings' => '{"default":""}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ]
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
                            'type' => PlainText::class,
                            'name' => 'Handle',
                            'handle' => 'fieldHandle',
                            'instructions' => '',
                            'required' => 1,
                            'typesettings' => '{"placeholder":"","code":"","multiline":"","initialRows":"4","charLimit":"","columnType":"text"}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                        'new3' => [
                            'type' => Table::class,
                            'name' => 'Options',
                            'handle' => 'options',
                            'required' => '1',
                            'instructions' => '',
                            'typesettings' => '{"addRowLabel":"Add an option","maxRows":"","minRows":"1","columns":{"col1":{"heading":"Option Label","handle":"optionLabel","width":"","type":"singleline"},"col2":{"heading":"Value","handle":"value","width":"","type":"singleline"}},"defaults":{"row1":{"col1":"","col2":""}},"columnType":"text"}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                        'new4' => [
                            'type' => Lightswitch::class,
                            'name' => 'Required',
                            'handle' => 'required',
                            'instructions' => 'This field is required?',
                            'required' => 0,
                            'typesettings' => '{"default":""}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ]
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
                            'type' => PlainText::class,
                            'name' => 'Handle',
                            'handle' => 'fieldHandle',
                            'instructions' => '',
                            'required' => 1,
                            'typesettings' => '{"placeholder":"","code":"","multiline":"","initialRows":"4","charLimit":"","columnType":"text"}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                        'new3' => [
                            'type' => Number::class,
                            'name' => 'Min Value',
                            'handle' => 'minValue',
                            'required' => 0,
                            'instructions' => '',
                            'typesettings' => '{"min":null,"max":null,"decimals":"0","size":null}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                        'new4' => [
                            'type' => Number::class,
                            'name' => 'Max Value',
                            'handle' => 'maxValue',
                            'required' => 0,
                            'instructions' => '',
                            'typesettings' => '{"min":null,"max":null,"decimals":"0","size":null}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                        'new5' => [
                            'type' => Lightswitch::class,
                            'name' => 'Required',
                            'handle' => 'required',
                            'instructions' => 'This field is required?',
                            'required' => 0,
                            'typesettings' => '{"default":""}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ]
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
                            'type' => PlainText::class,
                            'name' => 'Handle',
                            'handle' => 'fieldHandle',
                            'instructions' => '',
                            'required' => 1,
                            'typesettings' => '{"placeholder":"","code":"","multiline":"","initialRows":"4","charLimit":"","columnType":"text"}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                        'new3' => [
                            'type' => Table::class,
                            'name' => 'Options',
                            'handle' => 'options',
                            'required' => '1',
                            'instructions' => '',
                            'typesettings' => '{"addRowLabel":"Add an option","maxRows":"","minRows":"1","columns":{"col1":{"heading":"Option Label","handle":"optionLabel","width":"","type":"singleline"},"col2":{"heading":"Value","handle":"value","width":"","type":"singleline"}},"defaults":{"row1":{"col1":"","col2":""}},"columnType":"text"}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                        'new4' => [
                            'type' => Lightswitch::class,
                            'name' => 'Required',
                            'handle' => 'required',
                            'instructions' => 'This field is required?',
                            'required' => 0,
                            'typesettings' => '{"default":""}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ]
                    ]
                ],
                'new7' => [
                    'name' => 'Hidden',
                    'handle' => 'hidden',
                    'fields' => [
                        'new1' => [
                            'type' => PlainText::class,
                            'name' => 'Handle',
                            'handle' => 'label',
                            'instructions' => 'This field will not visible in the form, just in the source code',
                            'required' => 1,
                            'typesettings' => '{"placeholder":"","code":"","multiline":"","initialRows":"4","charLimit":"","columnType":"text"}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                        ],
                        'new2' => [
                            'type' => PlainText::class,
                            'name' => 'Hidden Value',
                            'handle' => 'hiddenValue',
                            'instructions' => 'You can use twig code',
                            'required' => 1,
                            'typesettings' => '{"placeholder":"{{ craft.request.path }}","code":"","multiline":"","initialRows":"4","charLimit":"","columnType":"text"}',
                            'translationMethod' => Field::TRANSLATION_METHOD_SITE
                        ]
                    ]
                ],
            ]
        ];

        $matrixBasicField = $this->getStripeMatrixFieldFromDb(self::BASIC_FORM_FIELDS_HANDLE);

        if (!is_null($matrixBasicField)) {
            Craft::info('Skipped '.self::BASIC_FORM_FIELDS_HANDLE. ' as already exists', __METHOD__);
            return true;
        }

        // Our basic fields is a matrix field
        $matrixBasicField = $fieldsService->createField([
            'type' => Matrix::class,
            'name' => 'Basic Form Fields',
            'context' => StripePlugin::$app->settings->getFieldContext(),
            'handle' => self::BASIC_FORM_FIELDS_HANDLE,
            'settings' => json_encode($matrixSettings),
            'instructions' => 'All data saved are stored as “metadata” with each Stripe payment record within your Stripe dashboard.',
            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
        ]);

        $this->saveMatrixFieldOnProjectConfig($matrixBasicField);

        return true;
    }

    public function getStripeMatrixFieldFromDb($handle)
    {
        $currentFieldContext = Craft::$app->getContent()->fieldContext;
        Craft::$app->getContent()->fieldContext = StripePlugin::$app->settings->getFieldContext();
        $matrixBasicField = Craft::$app->fields->getFieldByHandle($handle);
        Craft::$app->getContent()->fieldContext = $currentFieldContext;

        $projectConfig = Craft::$app->config->getGeneral()->useProjectConfigFile ?? false;

        if (is_null($matrixBasicField) && $projectConfig) {
            // lets check project config
            $stripeFields = Craft::$app->projectConfig->get("enupalStripe.fields", true);
            if ($stripeFields) {
                foreach ($stripeFields as $stripeField) {
                    if (isset($stripeField['handle']) && $stripeField['handle'] === $handle) {
                        $matrixBasicField =  $stripeField;
                    }
                }
            }
        }

        return $matrixBasicField;
    }

    /**
     * @return bool
     * @throws \yii\db\Exception
     * @throws \Exception
     */
    private function createMultiplePlansMatrixField()
    {
        $fieldsService = Craft::$app->getFields();

        $subscriptionUrl = UrlHelper::cpUrl('enupal-stripe/settings/subscriptions');

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
                            'instructions' => "Can't see your plans? Go to [Subscriptions]($subscriptionUrl) and click on Refresh Plans",
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

        $matrixMultiplePlansField = $this->getStripeMatrixFieldFromDb(self::MULTIPLE_PLANS_HANDLE);

        if (!is_null($matrixMultiplePlansField)) {
            Craft::info('Skipped '.self::MULTIPLE_PLANS_HANDLE. ' as already exists', __METHOD__);
            return true;
        }

        // Our multiple plans matrix field
        // Let the customer can sign up for one of several available plans (and optionally set a custom amount).
        $matrixMultiplePlansField = $fieldsService->createField([
            'type' => Matrix::class,
            'name' => 'Add Plan',
            'context' => StripePlugin::$app->settings->getFieldContext(),
            'handle' => self::MULTIPLE_PLANS_HANDLE,
            'settings' => json_encode($matrixSettings),
            'instructions' => 'Customize the plans that the customer should select',
            'translationMethod' => Field::TRANSLATION_METHOD_SITE,
        ]);

        $this->saveMatrixFieldOnProjectConfig($matrixMultiplePlansField);

        return true;
    }

    /**
     * Adds variables to parse in templates
     *
     * @param array $variables
     */
    public static function addVariables(array $variables)
    {
        static::$fieldVariables = array_merge(static::$fieldVariables, $variables);
    }

    /**
     * @return array
     */
    public function getFieldVariables()
    {
        return static::$fieldVariables;
    }

    /**
     * @return array
     */
    public function getPaymentTypes()
    {
        return [
            PaymentType::CC => 'Card',
            PaymentType::IDEAL => 'iDEAL',
            PaymentType::SOFORT => 'SOFORT',
        ];
    }

    /**
     * @return array
     */
    public function getCheckoutPaymentTypes()
    {
        return [
            CheckoutPaymentType::CC => 'Card',
            CheckoutPaymentType::IDEAL => 'iDEAL',
            CheckoutPaymentType::FPX => 'FPX',
        ];
    }

    /**
     * @return array
     */
    public function getPaymentTypesIds()
    {
        return [
            'card' => PaymentType::CC,
            'ideal' => PaymentType::IDEAL,
            'sofort' => PaymentType::SOFORT,
        ];
    }

    /**
     * @param $paymentType
     * @return mixed|null
     */
    public function getPaymentTypeName($paymentType)
    {
        $paymentTypes = $this->getPaymentTypes();

        return $paymentTypes[$paymentType] ?? null;
    }

    /**
     * @return array
     */
    public function getAsynchronousPaymentTypes()
    {
        $paymentTypes = $this->getPaymentTypes();
        unset($paymentTypes[PaymentType::CC]);

        return $paymentTypes;
    }

    /**
     * @param $paymentTypeOptions
     * @return array
     */
    public function getPaymentTypesAsOptions($paymentTypeOptions)
    {
        $optionsEnabled = json_decode($paymentTypeOptions, true);
        $paymentOptions = [];

        foreach ($optionsEnabled as $optionEnabled) {
            $paymentOptions[] = [
                'label' => $this->getPaymentTypeName($optionEnabled),
                'value' => $optionEnabled
            ];
        }

        return $paymentOptions;
    }

    /**
     * @return array
     */
    public function getSofortCountriesAsOptions()
    {
        return [
            [
                'label' => Craft::t('site', 'Austria'),
                'value' => 'AT'
            ],
            [
                'label' => Craft::t('site', 'Belgium'),
                'value' => 'BE'
            ],
            [
                'label' => Craft::t('site', 'Germany'),
                'value' => 'DE'
            ],
            [
                'label' => Craft::t('site', 'Italy'),
                'value' => 'IT'
            ],
            [
                'label' => Craft::t('site', 'Netherlands'),
                'value' => 'NL'
            ],
            [
                'label' => Craft::t('site', 'Spain'),
                'value' => 'ES'
            ]
        ];
    }
}
