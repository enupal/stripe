# Stripe Payments Changelog

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