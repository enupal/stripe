# Stripe Payments Changelog

## 6.0.3 - 2025.02.04
### Fixed
- Fixed issue when calling `getBlockTypeFields` ([#406])

[#406]: https://github.com/enupal/stripe/issues/406

## 6.0.2 - 2024.04.07
### Added
- Added support for `useSca` and `capture` override via [config settings](https://docs.enupal.com/stripe-payments/getting-started/saving-your-stripe-api-keys.html#saving-the-stripe-api-keys-via-config-file)
- Added support for the `paymentForm` object to be used on the `Custom Plan Name` setting, e.g: `Custom Plan - {planId} - {paymentForm.handle}`

### Fixed
- Fixed issue when saving general settings and [having config settings](https://docs.enupal.com/stripe-payments/getting-started/saving-your-stripe-api-keys.html#saving-the-stripe-api-keys-via-config-file)

## 6.0.1 - 2024.03.31
### Updated
- Updated sidebar logo

## 6.0.0 - 2024.03.31
### Added
- Added Craft CMS 5 support 

## 5.5.1 - 2024.02.26
### Fixed
- Fixed issue where Subscription Grant user group was not updated after updating plan. ([#374])

[#374]: https://github.com/enupal/stripe/issues/374

## 5.5.0 - 2023.10.30
### Added
- Added Craft CMS requirement `^4.5.0`

### Fixed
- Fixed issue when adding subscription plan on customer chooses plan only on Craft CMS `^4.5.0` and for new installations ([#369])

[#369]: https://github.com/enupal/stripe/issues/369

## 5.4.0 - 2023.09.28
### Updated
- Updated Stripe lib to v10

## 5.3.1 - 2023.09.02
### Fixed
- Fixed issue on legacy Stripe Checkout

## 5.3.0 - 2023.09.01 [CRITICAL]
### Fixed
- Fixed vulnerability that allows the creation of spam orders. ([#363])
- The `enupal-stripe/stripe/save-order` action is now disabled for when SCA (Strong Customer Authentication) is enabled on the plugin settings

[#363]: https://github.com/enupal/stripe/issues/363

## 5.2.0 - 2023.07.30
### Fixed
- Fixed issue related to projectConfig and missing tables when deploying to new env with `allowAdminChanges` set to `false`. ([#356]) ([#357]) ([#345])

[#356]: https://github.com/enupal/stripe/issues/356
[#357]: https://github.com/enupal/stripe/issues/357
[#345]: https://github.com/enupal/stripe/issues/345

## 5.1.2 - 2023.05.05
### Fixed
- Fixed issue when syncing products, prices were limited to 10

## 5.1.1 - 2023.02.25
### Fixed
- Fixed issue that prevents creating checkout sessions on one-time payments

## 5.1.0 - 2023.02.09
### Added
- Adds support for [adjustable quantity](https://stripe.com/docs/payments/checkout/adjustable-quantity?locale=en-GB) 
to enable your customers to update the quantity of an item during checkout. Settings added on Payment Forms to set max and min ([#338])
- Adds support to expand `sources` info on Stripe Customer object ([#337])

### Fixed
- Fixed issue when converting subscription date ([#340])

[#338]: https://github.com/enupal/stripe/issues/338
[#340]: https://github.com/enupal/stripe/issues/340
[#337]: https://github.com/enupal/stripe/issues/337

## 5.0.9 - 2023.01.01
### Added
- Added validation on minimum amount on backend when using custom amount. ([#326])

### Fixed
- Fixed issue where custom name was not created when passing options ([#328])
- Fixed redirect issue when saving Checkout settings  ([#321])

[#328]: https://github.com/enupal/stripe/issues/328
[#321]: https://github.com/enupal/stripe/issues/321
[#326]: https://github.com/enupal/stripe/issues/326

## 5.0.8 - 2022.11.19
### Fixed
- Fixed issue where edit URL disappears on Element Index pages like Payment Forms, Orders, etc.

## 5.0.7 - 2022.09.01
### Fixed
- Fixed issue after processing cart checkout order.

## 5.0.6 - 2022.08.03
### Fixed
- Fixed issue when displaying subscription details ([#262])

[#262]: https://github.com/enupal/stripe/issues/262

## 5.0.5 - 2022.07.13
### Fixed
- Fixed issue when using metered volume plans ([#305])

[#305]: https://github.com/enupal/stripe/issues/305

## 5.0.4 - 2022.06.26
### Fixed
- Fixed issue on subscription when using stripe elements ([#295])
- Fixed issue when sync one-time and recurring orders ([#296])

[#295]: https://github.com/enupal/stripe/issues/295
[#296]: https://github.com/enupal/stripe/issues/296

## 5.0.3 - 2022.06.14
### Added
- Added support for [Affirm](https://stripe.com/en-gb/payments/affirm) payment method 

## 5.0.2 - 2022.06.02
### Added
- Added the `craft.enupalStripe.app` service layer to twig variable ([#289])
- Added the `Stripe Prices` field type ([#288])

[#288]: https://github.com/enupal/stripe/issues/288
[#289]: https://github.com/enupal/stripe/issues/289

## 5.0.1 - 2022.05.28
### Fixed
- Fixed issue when creating a Stripe Products field type

## 5.0.0 - 2022.05.27

> {tip} If you're migrating from Craft 3 please make sure to migrate your `stripePayments` settings from your `config/general.php` to `config/custom.php` file [docs](https://docs.enupal.com/stripe-payments/getting-started/saving-your-stripe-api-keys.html#migrating-from-craft-3-to-craft-4). Enjoy!

### Added
- Added Craft CMS 4 support

### Updated
- Updated to read Stripe Api keys from `config/custom.php` following [Craft 4 update](https://github.com/craftcms/cms/pull/10100)

## 4.0.1 - 2022.05.18

### Added
- Added error messages on Customer Portal (update billing) ([#232])
- Added support to update billing details [for guest users](https://docs.enupal.com/stripe-payments/templating/update-billing.html) ([#279])

### Fixed
- Fixed issue related to update billing (Customer portal simple reloads page) ([#232])
- Fixed issue on email template overrides ([#258])
- Fixed issue on Automatic tax ([#255])
- Fixed issue on showing archive tax rates ([#243])
- Fixed issue when craft cms user is activated ([#278])

[#232]: https://github.com/enupal/stripe/issues/232
[#278]: https://github.com/enupal/stripe/issues/278
[#279]: https://github.com/enupal/stripe/issues/279
[#258]: https://github.com/enupal/stripe/issues/258
[#255]: https://github.com/enupal/stripe/issues/255
[#243]: https://github.com/enupal/stripe/issues/243

## 4.0.0 - 2022.04.29

### Added
- Added [PRO edition](https://docs.enupal.com/stripe-payments/pro/overview.html)
- Added [Cart API](https://docs.enupal.com/stripe-payments/pro/cart-api.html) support
- Added [Checkout twig tag](https://docs.enupal.com/stripe-payments/pro/checkout.html)
- Added [CheckoutEvent](https://docs.enupal.com/stripe-payments/plugin-development/events.html#beforecreatesession)
- Added Cart element
- Added Checkout settings
- Added Sync Product setting
- Added Product and Price Elements
- Added `product.created` webhook
- Added `product.updated` webhook
- Added `product.deleted` webhook
- Added `price.created` webhook
- Added `price.updated` webhook
- Added `price.deleted` webhook
- Added Sync product and prices from Stripe functionality via webhooks
- Added Product element modal window view (hub modal on double click)
- Added Stripe Product field type
- Added Cart API `GET /enupal-stripe/cart` resource
- Added Cart API `POST /enupal-stripe/add` resource
- Added Cart API `POST /enupal-stripe/update` resource
- Added Cart API `POST /enupal-stripe/clear` resource
- Added Cart API `POST /enupal-stripe/checkout` resource
- Added shipping support
- Added dynamic taxes support
- Added promotional codes support
- Added Cart API sample templates

### Updated
- Updated API version to `2020-08-27`

## 3.4.2 - 2022.03.24

### Fixed
- Fixed issue when retrieving the subscription model ([#262])
- Fixed issue on overwrite checkoutSuccessUrl via options ([#257])
- Fixed wrong customer url on order details ([#261])

[#262]: https://github.com/enupal/stripe/issues/262
[#257]: https://github.com/enupal/stripe/issues/257
[#261]: https://github.com/enupal/stripe/issues/261

## 3.4.1 - 2022.02.16

### Fixed
- Fixed issue retrieving plans from Stripe

## 3.4.0 - 2022.02.16

### Added
- Added [automatic tax](https://stripe.com/docs/payments/checkout/taxes?tax-calculation=stripe-tax) support (subscriptions only) on Stripe Checkout ([#249])

### Updated
- Updated subscription dates format on Order details to mach same CP locale format ([#247])
- Updated subscription plan names to add the product name ([#244])

[#249]: https://github.com/enupal/stripe/issues/249
[#247]: https://github.com/enupal/stripe/issues/247
[#244]: https://github.com/enupal/stripe/issues/244

## 3.3.11 - 2022.01.07

### Fixed
- Fixed issue where orders (one-time and subscriptions) were duplicated if the checkout session event was sent multiple times (Stripe Checkout) ([#196]) ([#180])

[#196]: https://github.com/enupal/stripe/issues/196
[#180]: https://github.com/enupal/stripe/issues/180

## 3.3.10 - 2021.12.21

### Added
- Added [Klarna](https://stripe.com/docs/payments/klarna) payment method (Stripe Checkout)
- Added line2 to addresses on checkout and sync ([#178])

### Fixed
- Fixed issue on one-time sync ([#196])
- Fixed issue where export orders was adding deleted orders ([#180])

[#180]: https://github.com/enupal/stripe/issues/180
[#196]: https://github.com/enupal/stripe/issues/196
[#178]: https://github.com/enupal/stripe/issues/178

## 3.3.9 - 2021.11.19

### Fixed
- Fixed bug on ideal recurring payments ([#186])

[#186]: https://github.com/enupal/stripe/issues/186

## 3.3.8 - 2021.11.02

### Added
- Added "Custom Plan Name" setting (Settings -> Default)

### Fixed
- Fixed issue on the Tax tab ([#222])

[#222]: https://github.com/enupal/stripe/issues/222

## 3.3.7 - 2021.10.06

### Added
- Added `testWebhookSigningSecret` config setting ([#218]) ([#docs1]) ([#docs2])

[#218]: https://github.com/enupal/stripe/issues/218
[#docs1]: https://docs.enupal.com/stripe-payments/getting-started/saving-your-stripe-api-keys.html#saving-the-stripe-api-keys-via-config-file
[#docs2]: https://docs.enupal.com/stripe-payments/stripe-payment-forms/webhook.html#test-webhooks-locally

## 3.3.6 - 2021.09.09

### Fixed
- Fixed issue when using Stripe Checkout + SCA ([#213])
- Fixed error when Sync subscription orders ([#193])

[#213]: https://github.com/enupal/stripe/issues/213
[#193]: https://github.com/enupal/stripe/issues/193

## 3.3.5 - 2021.07.06

### Updated
- Updated stripe php lib ^7.0

## 3.3.4 - 2021.07.03

### Added
- Added Wechat Pay payment method (Stripe Checkout)
- Added Grab Pay payment method (Stripe Checkout)
- Added Afterpay (Clearpay) payment method (Stripe Checkout)
- Added Canadian Pre Authorized Debits payment method (Stripe Checkout)
- Added Boleto payment method (Stripe Checkout)
- Added OXXO payment method (Stripe Checkout)
- Added EVENT_BEFORE_PROCESS_TRANSFER event

## 3.3.3 - 2021.03.26

### Fixed
- Fixed issue on notification emails ([#181])

[#181]: https://github.com/enupal/stripe/issues/181

## 3.3.2 - 2021.02.28

### Fixed
- Fixed an issue when coupons were enabled on one time payments ([#167])

[#167]: https://github.com/enupal/stripe/issues/167

## 3.3.1 - 2021.02.23

### Fixed
- Fixes issue on Subscription grants that removed existing groups on new subscriptions ([#170])

### Updated
- Updated jQuery to v3.5.1 ([#166])

[#170]: https://github.com/enupal/stripe/issues/170
[#166]: https://github.com/enupal/stripe/issues/166

## 3.3.0 - 2021.01.30

### Added
- Added support for Metered and Tired subscriptions plans on the new Stripe Checkout
- Added support for `checkoutImages` via [options](https://docs.enupal.com/stripe-payments/templating/paymentform.html#options) ([#156])
- Added `allow_incomplete` when passing `enupalAllowPromotionCodes` via options
- Added metadata on subscriptions when Stripe Checkout is disabled

### Fixed
- Fixed an error when using the [reportUsage](https://docs.enupal.com/stripe-payments/stripe-payment-forms/metered-billing-plan.html) method

### Updated
- Updates requirement `craftcms/cms` to ^3.6.0
- Updates requirement `phpoffice/phpspreadsheet` to ^1.16.0 ([#162])

[#162]: https://github.com/enupal/stripe/issues/162
[#156]: https://github.com/enupal/stripe/issues/156

## 3.2.3 - 2020.12.15

### Added
- Added missing City column to reports (billing and shipping) ([#155])

### Fixed
- Fixed issue when creating new Stripe Connect account ([#158])

[#158]: https://github.com/enupal/stripe/issues/158

[#155]: https://github.com/enupal/stripe/issues/155

## 3.2.2 - 2020.11.12

### Fixed
- Fixed issue when using the Dynamic Tax rates setting on thew New Stripe Checkout

## 3.2.1 - 2020.11.07

### Added
- Added support to Collect Fixed and Dynamic tax rates in the new Stripe Checkout (Beta). [More info](https://docs.enupal.com/stripe-payments/stripe-payment-forms/taxes.html)
- Added support for new payment methods in the new Stripe Checkout: Alipay, BACS DEBIT, Bancontact, GIROPAY, P24, EPS, Sofort, Sepa Debit
- Added `allowPromotionCodes` property to the Payment Form element to enable coupons when the new Stripe Checkout is enabled. [More info](https://docs.enupal.com/stripe-payments/coupons/overview.html)

### Fixed
- Fixed issue where `removeDefaultItem` via options was not taken into account
- Fixed on iDEAL workflow (only subscriptions) where customers get an extra authorization charge on their bank (that was refunded).
- Fixed security issue when using the new Stripe Checkout ([#145])

[#145]: https://github.com/enupal/stripe/issues/145

## 3.1.5 - 2020.11.03

### Added
- Added support for `singlePlanTrialDays` via options for when "Set single plan" and new Stripe Checkout

## 3.1.4 - 2020.10.23

### Improved
- Improved UI on the general settings

### Fixed
- Fixed issue with `getReturnUrl` method of the PaymentForm model.

## 3.1.3 - 2020.09.14

### Fixed
- Fixed issue on Stripe Connect and Craft Commerce

## 3.1.2 - 2020.09.14

### Updated
- Updated default setting to refresh all plans instead of only plans with nickname

## 3.1.1 - 2020.09.09

### Fixed
- Fixed issue when commerce is not installed ([#138])

[#138]: https://github.com/enupal/stripe/issues/138

## 3.1.0 - 2020.09.09

### Added
- Added support for Craft Commerce and Stripe Connect [More Info](https://docs.enupal.com/stripe-payments/connect/overview.html)
- Added Commerce marketplace example templates
- Added Sync Vendors [More Info](https://docs.enupal.com/stripe-payments/connect/vendors.html#how-to-create-a-vendor)
- Added support for `customAmountStep` via options ([#135])

[#135]: https://github.com/enupal/stripe/issues/135

## 3.0.2 - 2020.08.25

### Fixed
- Fixed issues when project config is enabled ([#121])([#129])

[#121]: https://github.com/enupal/stripe/issues/121
[#129]: https://github.com/enupal/stripe/issues/129

## 3.0.1 - 2020.08.14

### Fixed
- Fixed issue saving general settings ([#126])

[#126]: https://github.com/enupal/stripe/issues/126

## 3.0.0 - 2020.08.10

> {tip} This release adds initial support for Stripe Connect, facilitate payments on your Craft CMS site, build a marketplace, and pay out sellers or service providers globally. To learn more please read our [docs](https://docs.enupal.com/stripe-payments/connect/overview.html). Enjoy!

### Added
- Added Stripe Connect support [(More Info)](https://docs.enupal.com/stripe-payments/connect/overview.html) ([#1])
- Added Vendors element type
- Added Connect element type
- Added Commissions element type
- Added support for promotional codes on subscriptions ([More Info](https://docs.enupal.com/stripe-payments/templating/paymentform.html))
- Added support for update billing details via the [Stripe Customer Portal](https://docs.enupal.com/stripe-payments/templating/update-billing.html)
- Added vendor notification email
- Added sample templates for manage vendors on the front-end (Stripe Payments only, commerce coming soon)
- Added After Populate Payment Form Event

### Fixed
- Fixed issue Syncing Stripe orders ([#112])
- Fixed issue where Billing address is not saved on subscriptions `new Stripe Checkout` ([#114])
- Fixed issue on fresh install Orders index page ([#120])
- Fixed issue when using Postgres ([#122])
- Fixed UI issues on the Edit Order page

[#1]: https://github.com/enupal/stripe/issues/1
[#112]: https://github.com/enupal/stripe/issues/112
[#114]: https://github.com/enupal/stripe/issues/114
[#120]: https://github.com/enupal/stripe/issues/120
[#122]: https://github.com/enupal/stripe/issues/122

## 2.7.2 - 2020.06.18

### Updated
- Updated to the autosuggest field on template override setting in customer/admin notification email settings ([#108])

### Fixed
- Fixed issue where `checkoutCancelUrl` via options was not working  ([#83])
- Fixed issue where payment description was not added on Stripe when SCA is enabled ([#107])
- Fixed issue where the payment form was submitted when pressing enter ([#109])

[#83]: https://github.com/enupal/stripe/issues/83
[#107]: https://github.com/enupal/stripe/issues/107
[#109]: https://github.com/enupal/stripe/issues/109
[#108]: https://github.com/enupal/stripe/issues/108

## 2.7.1 - 2020.06.11

### Fixed
- Fixed subscription grants not working when SCA is enabled ([#105])

[#105]: https://github.com/enupal/stripe/issues/105

## 2.7.0 - 2020.06.06

### Added
- Added `itemName`, `itemDescription`, `checkoutSuccessUrl` and `checkoutCancelUrl` to [options](https://docs.enupal.com/stripe-payments/templating/paymentform.html#options) (useful for Stripe Checkout) ([#88])([#83])

### Fixed
- Fixed issue where Stripe errors where not displayed on front-end
- Fixed coupon issue on Asynchronous Payments (iDEAL, Sofort)
- Fixed issue where required fields not showing error messages only on Safari browsers and card elements
- Fixed issue when `useProjectConfigFile` is enabled ([#94]) ([#49]) ([#37])

### Updated
- Updated front-end css class from `hidden` to `enupal-hidden`
- Updated front-end amount field increments from `.01` to `1` ([#99])

[#94]: https://github.com/enupal/stripe/issues/94
[#49]: https://github.com/enupal/stripe/issues/49
[#37]: https://github.com/enupal/stripe/issues/37
[#99]: https://github.com/enupal/stripe/issues/99
[#88]: https://github.com/enupal/stripe/issues/88
[#83]: https://github.com/enupal/stripe/issues/83

## 2.6.3 - 2020.05.29

### Fixed
- Fixed UI issue on editing payment form
- Fixed issue on new Stripe Checkout if Item Name is empty on payment form

## 2.6.2 - 2020.05.26

### Added
- Added checkCouponLabel to options and translation tag ([#96])

### Fixed
- Fixed address error when syncing orders ([#92])
- Fixed UI issues when SCA is enabled on editing payment form

[#96]: https://github.com/enupal/stripe/issues/96
[#92]: https://github.com/enupal/stripe/issues/92

## 2.6.1 - 2020.05.02

### Fixed
- Fixed issue when redirecting checkout success URL (new Stripe Checkout) ([#75])

[#75]: https://github.com/enupal/stripe/issues/75

## 2.6.0 - 2020.04.22

### Added
- Added `removeDefaultItem` option for the New Stripe Checkout ([#84]) [More Info](https://enupal.com/craft-plugins/stripe-payments/docs/templating/paymentform#custom-line-items)
- Added `getVariables()` to get all variables added from `{%- do craft.enupalStripe.addVariables({foo:'bar'}) -%}`

### Fixed
- Fixed issue when saving several admin notification emails
- Fixed issue saving shipping and billing addresses on Sync workflow. ([#87])
- Fixed issue where "Payment Button Processing Text" was not displaying on some scenarios. ([#86])

[#86]: https://github.com/enupal/stripe/issues/86
[#87]: https://github.com/enupal/stripe/issues/87
[#84]: https://github.com/enupal/stripe/issues/84

## 2.5.1 - 2020.03.24

### Updated
- Updated support for `stripe/stripe-php` to `^6.6|^7.0` to avoid conflicts with other plugins

## 2.5.0 - 2020.03.22

### Added
- Added support for PHP 7.4
- Added support for Shipping Address on New Stripe Checkout
- Added support to pass line items when using the New Stripe Checkout. [More Info](https://enupal.com/craft-plugins/stripe-payments/docs/templating/paymentform#custom-line-items)
- Added support for one-time [iDEAL](https://stripe.com/docs/payments/checkout/one-time/ideal) payments (common payment method in the Netherlands)  using the new Stripe Checkout
- Added support for one-time [FPX](https://stripe.com/docs/payments/checkout/one-time/fpx#enable-fpx) payments (common payment method in Malaysia) using the new Stripe Checkout

### Updated
- Updated `stripe/stripe-php` requirement to ^7.0.0 

## 2.4.0 - 2020.03.18

### Added
- Added support for subscription grants to assign and remove user groups for when subscriptions are created or deleted. [More Info](https://enupal.com/craft-plugins/stripe-payments/docs/stripe-payment-forms/subscription-grants)
- Added support to pass custom plans options. [More info](https://enupal.com/craft-plugins/stripe-payments/docs/templating/paymentform#custom-plans-options)

## 2.3.1 - 2020.02.26

### Improved
- Improved support to Craft 3.4

## 2.3.0 - 2020.02.26

### Added
- Added support to Craft 3.4

## 2.2.1 - 2020.02.17

### Added
- Added the `getPaidInvoices` method to the Subscription model. [More Info](https://enupal.com/craft-plugins/stripe-payments/docs/templating/get-all-paid-invoices-from-subscription)

## 2.2.0 - 2020.01.14

### Added
- Added support to pass the email via the options array in the `paymentForm` tag
- Added the "Update customer email on Stripe" setting for when an user updates their email. ([#65])

### Fixed
- Fixed bug where if the user double click the payment button it could generate multiple charges. ([#74]) 

[#74]: https://github.com/enupal/stripe/issues/74
[#65]: https://github.com/enupal/stripe/issues/65

## 2.1.7 - 2019.12.12

### Added
- Added support to update the subscription plan via front-end. [More Info](https://enupal.com/craft-plugins/stripe-payments/docs/templating/update-subscription-plan)
- Added `getStripePlans` to the enupalStripe variable

## 2.1.6 - 2019.12.11

### Added
- Added support to update card details. [More Info](https://enupal.com/craft-plugins/stripe-payments/docs/templating/update-card-info)
- Added `getStripeCustomerByEmail` on the Customers service layer
- Added `getStripeCustomer` to the enupalStripe variable
- Added `getPaymentMethod` to the Customers service layer

## 2.1.5 - 2019.12.05

### Fixed
- Fixed issue with the Payment Forms field when `Validate related element` is enabled
- Fixed error for when processing subscriptions with free plans (zero amounts)

## 2.1.4 - 2019.12.01

### Fixed
- Fixed issue when using checkboxes field and SCA was enabled

## 2.1.3 - 2019.11.21

### Added
- Added support to display payment errors, e.g: declined cards. [More info](https://enupal.com/craft-plugins/stripe-payments/docs/templating/display-errors)

### Fixed
- Fixed js error when using IE11
- Fixed issue displaying more that one payment form on the same page

## 2.1.2 - 2019.11.13

### Fixed
- Fixed issue when retrieving plans were limit to 10.

## 2.1.1 - 2019.09.18

### Added
- Added the `Checkout Submit Type` setting for when thew new Stripe Checkout is enabled. Supported values are auto, book, donate, or pay

## 2.0.6 - 2019.09.11

### Added
- Added API version to `2019-09-09`

## 2.0.5 - 2019.09.07

### Fixed
- Fixed issue when one time fee is set to 0 
- Fixed issue when sync subscription orders ([#46])

[#46]: https://github.com/enupal/stripe/issues/46

## 2.0.4 - 2019.09.07

### Added
- Added one-time setup fee to subscriptions when using new Stripe checkout
- Added Portuguese and Polish languages
- Added the `oneTimeSetupFeeLabel` setting.

### Fixed
- Fixed redirect issue after subscription payment on new Stripe Checkout. 
- Fixed issue when using the Norwegian language 

## 2.0.3 - 2019.09.04

### Added
- Added back the `getLogoAsset` method in the PaymentForm element (returns the first logo)

## 2.0.2 - 2019.09.04

### Fixed
- Fixed issue where locale was not passed to the new stripe checkout

## 2.0.1 - 2019.08.30

### Fixed
- Fixed redirect issue after payment on new stripe checkout.

## 2.0.0 - 2019.08.30

> {tip} This release adds initial support for Strong Customer Authentication, to learn more please read our [guide](https://enupal.com/craft-plugins/stripe-payments/docs/getting-started/sca). Enjoy!

### Added
- Added support for new [Stripe Checkout](https://stripe.com/payments/checkout) which is [SCA](https://stripe.com/payments/strong-customer-authentication) ready and comes with Apple Play support.
- Added `Use Strong Customer Authentication (SCA)` general setting

### Fixed
- Fixed flash message errors in the orders page after delete a payment form.
- Fixed issue where the "Processing Text" value was not showing on some scenarios.

## 1.9.10 - 2019.08.13

### Added
- Added support for `flat_amount` on plans with multiple tiers.

## 1.9.9 - 2019.08.10

### Added
- Added support to update the charge description in the default settings. 

## 1.9.8 - 2019.07.18

### Added
- Added support for storing billing and shipping subscription addresses on Stripe. 

## 1.9.7 - 2019.07.11

### Added
- Added support for Craft 3.2

## 1.9.6 - 2019.07.04

### Fixed
- Fixed wrong return URL on iDEAL payments.

## 1.9.5 - 2019.06.03

### Added
- Added `loadCss` setting

## 1.9.4 - 2019.05.27

### Added
- Added Authorize and Capture setting
- Added `charge.captured` webhook

## 1.9.3 - 2019.05.20

### Fixed
-  Fixed `404 bad request` error validating coupons with Stripe Checkout

## 1.9.2 - 2019.05.17

### Fixed
-  Fixed `404 bad request` error on validating coupons

## 1.9.1 - 2019.04.25

### Fixed
- Fixed issue when redeeming a coupon on one-time payments forms

## 1.9.0 - 2019.04.25

### Added
- Added Coupons support
- Added support for metered plans
- Added `reportUsage` to the subscription model on metered plans
- Added `Cancel subscription at period end` setting
- Added support to reactivate subscriptions via CP and Front-end

### Updated
- Updated sync orders to save the shippingAddressId and billingAddressId

### Removed
- Removed unused address column on orders table
- Removed unused discount column on orders table
- Removed unused discount columns on forms table

## 1.8.5 - 2019.04.02
### Fixed
- Fixed an error which could prevent the plugin from installing on PostgreSQL.

## 1.8.4 - 2019.03.22
### Fixed
- Fixed error when exporting Orders and a table prefix is set ([#29])

[#29]: https://github.com/enupal/stripe/issues/29

## 1.8.3 - 2019.03.19
### Added
- Added addresses info to CSV/XLS Report. ([#28])

[#28]: https://github.com/enupal/stripe/issues/28

## 1.8.2 - 2019.03.15
### Added
- Added the `paymentForms` twig tag. [More info](https://enupal.com/craft-plugins/stripe-payments/docs/templating/payment-forms)

## 1.8.1 - 2019.03.12
### Fixed
- Fixed issue where trial period was not set from the Stripe Plan.
- Fixed issue where emails where sent even if the Send Email lightswitch was disabled

## 1.8.0 - 2019.03.10
> {warning} we have updated the [front-end templates](https://github.com/enupal/stripe/tree/master/src/templates/_frontend) make sure to update the latest changes if you're using template overrides

### Added
- Added `enupal\stripe\elements\Order::getShippingAddressModel()`.
- Added `enupal\stripe\elements\Order::getBillingAddressModel()`.
- Added `enupal\stripe\elements\Order::getBillingAddress()`.
- Added export Orders button in the Orders index page. ([#22])
- Added `Same billing & shipping info` checkbox in the `paymentForm` template when using Stripe elements

### Improved
- Improved the shipping and billing address save behavior. ([#20])
- Improved unique field context

### Fixed
- Fixed bad "Read more" link on default customer email template ([#23])
- Fixed deprecation warnings ([#26])

[#23]: https://github.com/enupal/stripe/issues/23
[#26]: https://github.com/enupal/stripe/issues/26
[#22]: https://github.com/enupal/stripe/issues/22
[#20]: https://github.com/enupal/stripe/issues/20

## 1.7.1 - 2019.01.24
### Added
- Added support to generate a PDF Order via Enupal Snapshot

## 1.7.0 - 2019.01.08
> {warning} we have changed a few method names and namespaces please update your custom plugins after the upgrade. All changes are listed in the `Updated` section.

### Added
- Added the `$order->setFormFieldValue($handle, $value)` method 
- Added the `$order->setFormFieldValues($array)` method
- Added support to cancel subscriptions via front-end. [docs](https://enupal.com/craft-plugins/stripe-payments/docs/templating/cancel-subscriptions)
- Added the `craft.enupalStripe.getSubscriptionsByEmail` template function.
- Added the `craft.enupalStripe.getSubscriptionsByUser` template function.
- Added the `craft.enupalStripe.getOrdersByEmail` template function.
- Added the `craft.enupalStripe.getOrdersByUser` template function.

### Fixed
- Fixed issue where form field handles were saved as lowercase

### Updated
- Updated `Stripe::$app->orders->getOrderStatusById` to `Stripe::$app->orderStatuses->getOrderStatusById`
- Updated `Stripe::$app->orders->saveOrderStatus` to `Stripe::$app->orderStatuses->saveOrderStatus`
- Updated `Stripe::$app->orders->reorderOrderStatuses` to `Stripe::$app->orderStatuses->reorderOrderStatuses`
- Updated `Stripe::$app->orders->getAllOrderStatuses` to `Stripe::$app->orderStatuses->getAllOrderStatuses`
- Updated `Stripe::$app->orders->deleteOrderStatusById` to `Stripe::$app->orderStatuses->deleteOrderStatusById`
- Updated `Stripe::$app->orders->getOrderStatusRecordByHandle` to `Stripe::$app->orderStatuses->getOrderStatusRecordByHandle`
- Updated the `EVENT_BEFORE_SEND_NOTIFICATION_EMAIL` event from the `enupal\stripe\services\Orders` class to `enupal\stripe\services\Emails`
- Updated `sendAdminNotification` and `sendCustomerNotification` to `sendNotificationEmail` 

## 1.6.9 - 2018.12.20
### Fixed
- Fixed issue on Craft 3.1

## 1.6.8 - 2018.12.14
### Fixed
- Fixed issue with date field on data range setting

## 1.6.7 - 2018.12.14
### Added
- Adds support to date range filters in sync Setting

## 1.6.5 - 2018.12.11
### Added
- Adds support to older Stripe API versions in sync Setting

## 1.6.4 - 2018.12.05
### Added
- Added Sync Orders from Stripe under the advanced settings. `beta`

## 1.6.3 - 2018.11.30
### Added
- Added `calculateFinalAmount` setting to options on `paymentForm`

## 1.6.2 - 2018.11.30
### Added
- Added `getPaymentForm` to the variable class

## 1.6.1 - 2018.11.28
### Fixed
- Fixed issue on Orders chart.

## 1.6.0 - 2018.11.28
### Added
- Added support for [SOFORT](https://stripe.com/docs/sources/sofort) payment method, available in: Austria, Belgium, Germany, Italy, Netherlands and Spain
- Added a `Cancel` subscription button in the edit Order page
- Added a `Refund` button in the edit Order page
- Added a currency filter to the chart in the Orders index page
- Added support for tiered plans
- Added new Order filters: One-Time, Subscriptions, Succeeded, Pending and Refunded
- Added `afterRefundOrder` event
- Added `getSubscription()` method to the Order element

### Improved
- Improved Edit Order UI

### Fixed
- Fixed bug where address were not saved on asynchronous payment methods (iDEAL, SOFORT) 

## 1.5.10 - 2018.10.25
### Added
- Added ajax support to the `saveOrder` action - Orders Controller

## 1.5.9 - 2018.10.17
### Added
- Added support to `testMode` setting in config file

## 1.5.8 - 2018.10.17
### Added
- Added support to add Stripe API keys via config file

### Fixed
- Fixed bug where billing address was not saved with the order locally if shipping address is disabled

## 1.5.7 - 2018.10.15
### Added
- Added support to override default stripe element styles

## 1.5.6 - 2018.10.12
### Fixed
- Fixed bug where the postal code was not saved in shipping address

## 1.5.5 - 2018.10.03
### Fixed
- Fixed error `Received unknown parameter: source` on custom plans 

## 1.5.4 - 2018.10.01
### Added
- Added validations to customer and admin email settings

### Fixed
- Fixed issue in default admin email template

## 1.5.3 - 2018.09.19
### Added
- Adds support to save orders via the front-end

## 1.5.2 - 2018.09.08
### Fixed
- Fixed issue when retrieving customer deleted in the Stripe dashboard

## 1.5.1 - 2018.09.03
### Fixed
- Fixed error for when a customer is deleted in Stripe

### Improved
- Improved the `isCompleted` icon in the orders index page

## 1.5.0 - 2018.08.28
### Added
- Added Order Statuses
- Added user to orders, if no user is logged in will show a "Guest" message.
- Added `isCompleted` property to orders
- Added `messages` property to orders 
- Added `order` to `afterProcessWebhook` event

## 1.4.2 - 2018.08.22
### Added
- Added `afterProcessWebhook` event

## 1.4.1 - 2018.08.22
### Fixed
- Fixed bug in Firefox browsers

### Improved
- Improved code inspections

## 1.4.0 - 2018.08.19
### Added
- Added support for Stripe [API 2018-07-27](https://stripe.com/docs/upgrades#api-changelog) `make sure to upgrade your api before` before update the plugin. [Read how upgrade your API](https://stripe.com/docs/upgrades)
- Added support to one time setup fee for iDEAL payments
- Added Webhook setting page
- Added iDEAL bank to the paymentForm template

### Improved
- Improved Webhook response to don't throw a 404 error

## 1.3.3 - 2018.08.17
### Added
- Added support to pass `loadAssets` via options to disable load Stripe Payments assets.

## 1.3.2 - 2018.08.14
### Added
- Added setting to prevent load jquery

## 1.3.1 - 2018.08.13
### Added
- Added minify files

## 1.3.0 - 2018.08.13
### Added
- Added support for Card powered by Stripe Elements.
- Added support for iDEAL powered by Stripe Elements.
- Added template overrides
- Added support to pass the quantity as options on `paymentForm('handle', options)` (just available for single payments)
- Added support to pass the amount as options on `paymentForm('handle', options)` (just available for single payments)

### Improved
- Improved position of currency in Edit payment form.

### Fixed
- Fixed bug where number fields did not allow decimals in the front-end.

## 1.2.0 - 2018.07.09
### Added
- Added Taxes to subscriptions and recurring payments.

### Fixed
- Fixes bug where the order was saving the order in cents with some currencies
- Fixes bug where Set Single Plan with custom amount was adding the fee to the final amount
- Fixes bug when saving Single Plan with inventory

## 1.1.9 - 2018.06.28
### Fixed
- Fixes bug with camelcase filename in Orders view

## 1.1.8 - 2018.06.27
### Fixed
- Fixed bug with camel case in filenames: singleline a radiobuttons.

## 1.1.7 - 2018.06.21
### Added
- Added Handle field to all form fields to avoid error with metadata invalid params from Stripe
- Added better error messages for when devMode is enabled

### Fixed
- Fixed bug where the amount was not converted from cents
- Fixed deprecation error

## 1.1.4 - 2018.06.19
### Fixed
- Fixed issue with custom amounts

## 1.1.3 - 2018.06.19
### Added
- Added support to currencies with zero decimals
- Added link to view customer info in Stripe (Order view)

### Fixed
- Fixed issue where Frequency was not displayed after save
- Fixed issue when Free trial Period was not set.

### Added
- Added Set Status element action to Orders

## 1.1.2 - 2018.06.10

### Added
- Added Set Status element action to Orders

## 1.1.1 - 2018.06.08

### Updated
- Renames Status Shipped to Processed

## 1.1.0 - 2018.06.07

### Added
- Added Hidden field
- Added `order.getShippingAddressAsArray()`

## 1.0.5 - 2018.06.06

### Added
- Added filters to orders in variable

## 1.0.4 - 2018.06.06

### Added
- Added retrieve plans with nickname only

### Fixed
- Fixed issue with Field Type
- Fixed typo

## 1.0.3 - 2018.06.05
### Fixed
- Fixed issue with Dropdown form field in notification template

## 1.0.2 - 2018.06.03
### Fixed
- Fixed issue to display the `interval` dropdown when recurring payment is enabled

## 1.0.1 - 2018.06.03
### Added
- Added minified front-end files

## 1.0.0 - 2018.06.03
### Added
- Initial release