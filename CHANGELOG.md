# Stripe Payments Changelog

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