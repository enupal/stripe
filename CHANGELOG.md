# Stripe Payments Changelog

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