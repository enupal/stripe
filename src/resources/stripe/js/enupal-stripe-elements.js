/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

var enupalStripe = {};

(function($) {
    'use strict';
    enupalStripe = {

        stripe: null,
        paymentFormsList: {},
        finalData: {},
        zeroDecimals: {},

        init: function() {
            this.paymentFormsList = $('.enupal-stripe-form');

            this.zeroDecimals = ['MGA', 'BIF', 'CLP', 'PYG', 'DJF', 'RWF', 'GNF', 'UGX', 'JPY', 'VND', 'VUV', 'XAF', 'KMF', 'KRW', 'XOF', 'XPF'];

            this.paymentFormsList.each(function() {
                var enupalButtonElement = $(this);
                enupalStripe.initializeForm(enupalButtonElement);
            });

        },

        initializeForm: function(enupalButtonElement) {
            if (typeof $(enupalButtonElement).find('[name="enupalStripe[stripeData]"]').val() === 'undefined') {
                return false;
            }
            // get the form ID
            var enupalStripeData = $.parseJSON($(enupalButtonElement).find('[name="enupalStripe[stripeData]"]').val());

            // reset our values
            $(enupalButtonElement).find('[name="enupalStripe[stripeData]"]').val('');

            this.finalData.finalAmount = enupalStripeData.stripe.amount;

            // Create a Stripe client.
            var stripe = Stripe(enupalStripeData.pbk);

            // Create an instance of Elements.
            var elements = stripe.elements();

            // Custom styling can be passed to options when creating an Element.
            // (Note that this demo uses a wider set of styles than the guide below.)
            var style = {
                base: {
                    color: '#32325d',
                    lineHeight: '18px',
                    fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                    fontSmoothing: 'antialiased',
                    fontSize: '16px',
                    '::placeholder': {
                        color: '#aab7c4'
                    }
                },
                invalid: {
                    color: '#fa755a',
                    iconColor: '#fa755a'
                }
            };

            //this.createCardElement(stripe, enupalStripeData, elements, style);
            this.createIdealElement(stripe, enupalStripeData, enupalButtonElement, elements, style);
        },

        createCardElement: function(stripe, enupalStripeData, elements, style) {
            // Create an instance of the card Element.
            var card = elements.create('card', {style: style});

            // Add an instance of the card Element into the `card-element` <div>.
            card.mount('#card-element-' + enupalStripeData.paymentFormId);

            // Handle real-time validation errors from the card Element.
            card.addEventListener('change', function(event) {
                var displayError = document.getElementById('card-errors-' + enupalStripeData.paymentFormId);

                if (event.error) {
                    displayError.textContent = event.error.message;
                } else {
                    displayError.textContent = '';
                }
            });

            // Handle form submission.
            enupalButtonElement[0].addEventListener('submit', function(event) {
                event.preventDefault();

                stripe.createToken(card).then(function(result) {
                    if (result.error) {
                        // Inform the user if there was an error.
                        var errorElement = document.getElementById('card-errors-' + enupalStripeData.paymentFormId);
                        errorElement.textContent = result.error.message;
                    } else {
                        // Send the token to your server.
                        console.log(result.token);
                    }
                });
            });
        },

        createIdealElement: function(stripe, enupalStripeData, enupalButtonElement, elements, style) {

            var that = this;

            // Create an instance of the idealBank Element.
            //var idealBank = elements.create('idealBank', {style: style});

            // Add an instance of the idealBank Element into the `ideal-bank-element` <div>.
            //idealBank.mount('#ideal-bank-element-' + enupalStripeData.paymentFormId);

            var errorMessage = document.getElementById('error-message-' + enupalStripeData.paymentFormId);

            var form = enupalButtonElement[0];

            // Handle form submission.
            form.addEventListener('submit', function(event) {
                event.preventDefault();
                //showLoading();

                var enupalStripeDataSubmission = $.extend(true, {}, enupalStripeData);
                var stripeConfig = enupalStripeDataSubmission.stripe;
                stripeConfig.amount = that.convertToCents(that.getFinalAmount(enupalButtonElement, enupalStripeDataSubmission), stripeConfig.currency);
                enupalButtonElement.find('[name="enupalStripe[amount]"]').val(stripeConfig.amount);
                enupalButtonElement.find('[name="enupalStripe[testMode]"]').val(enupalStripeDataSubmission.testMode);

                var sourceData = {
                    type: 'ideal',
                    amount: stripeConfig.amount,
                    currency: 'eur',
                    owner: {
                        name: enupalButtonElement.find('[name="name"]').val(),
                    },
                    statement_descriptor: enupalStripeData.description,
                    // Specify the URL to which the customer should be redirected
                    // after paying.
                    redirect: {
                        return_url: 'http://craft3.test/pay?message=thank-you',
                    },
                };

                enupalButtonElement.submit();

                /*

                // Call `stripe.createSource` with the idealBank Element and additional options.
                stripe.createSource(idealBank, sourceData).then(function(result) {
                    if (result.error) {
                        // Inform the customer that there was an error.
                        errorMessage.textContent = result.error.message;
                        errorMessage.classList.add('visible');
                        //stopLoading();
                    } else {
                        // Redirect the customer to the authorization URL.
                        errorMessage.classList.remove('visible');
                        console.log(result.source);
                        $(form).append('<input type="hidden" name="source" value="'+JSON.stringify(result.source, null, 2)+'" />');
                        //enupalButtonElement.submit();
                    }
                });*/
            });
        },

        getFinalAmount: function(enupalButtonElement, enupalStripeData) {
            // We always return a default amount
            var finalAmount = enupalStripeData.stripe.amount;
            var fee = 0;
            var isRecurring = false;

            if (!enupalStripeData.enableSubscriptions) {
                // Check if custom amount
                if (enupalStripeData.amountType == 1) {
                    var customAmount = enupalButtonElement.find('[name="enupalStripe[customAmount]"]').val();
                    isRecurring = enupalButtonElement.find('[name="enupalStripe[recurringToggle]"]').is(":checked");

                    if (('undefined' !== customAmount) && (customAmount > 0)) {
                        finalAmount = customAmount;
                    }
                }
            } else {
                // Subscriptions!
                var subscriptionType = enupalStripeData.subscriptionType;

                if (subscriptionType == 0) {

                    if (enupalStripeData.singleSetupFee > 0) {
                        fee = enupalStripeData.singleSetupFee;
                    }
                    // single plan
                    if (enupalStripeData.enableCustomPlanAmount) {
                        // Custom plan
                        var customPlanAmount = enupalButtonElement.find('[name="enupalStripe[customPlanAmount]"]').val();

                        if (('undefined' !== customPlanAmount) && (customPlanAmount > 0)) {
                            finalAmount = customPlanAmount;
                        }
                    }
                } else {
                    // Custom plan
                    var customPlanAmountId = null;
                    if (enupalStripeData.subscriptionStyle == 'radio') {
                        customPlanAmountId = $('input[name="enupalStripe[enupalMultiPlan]"]:checked').val();
                    } else {
                        customPlanAmountId = enupalButtonElement.find('[name="enupalStripe[enupalMultiPlan]"]').val();
                    }
                    var customPlanAmount = null;

                    if (customPlanAmountId in enupalStripeData.multiplePlansAmounts) {
                        customPlanAmount = enupalStripeData.multiplePlansAmounts[customPlanAmountId]['amount'];
                        enupalStripeData.stripe['currency'] = enupalStripeData.multiplePlansAmounts[customPlanAmountId]['currency'];
                    }

                    if (customPlanAmountId in enupalStripeData.setupFees) {
                        var multiplePlanFee = enupalStripeData.setupFees[customPlanAmountId];

                        if (multiplePlanFee > 0) {
                            fee = multiplePlanFee;
                        }
                    }

                    // Multi-select plan
                    if (('undefined' !== customPlanAmount) && (customPlanAmount > 0)) {
                        finalAmount = customPlanAmount;
                    }
                }
            }

            if ((enupalStripeData.applyTax || isRecurring) && enupalStripeData.enableTaxes) {
                var tax = parseFloat((enupalStripeData.tax / 100) * (parseFloat(finalAmount) + parseFloat(fee))).toFixed(2);
                finalAmount = parseFloat(finalAmount) + parseFloat(tax);
                var taxLabel = enupalStripeData.taxLabel + ': ' + enupalStripeData.currencySymbol + tax;

                enupalButtonElement.find('[name="enupalStripe[taxAmount]"]').val(tax);
                enupalButtonElement.find('[name="tax-amount-label"]').empty().append(taxLabel);
            }

            return parseFloat(finalAmount) + parseFloat(fee);
        },

        convertToCents: function(amount, currency) {
            if (this.hasZeroDecimals(currency)) {
                return amount;
            }

            return (amount * 100).toFixed(0);
        },

        convertFromCents: function(amount, currency) {
            if (this.hasZeroDecimals(currency)) {
                return amount;
            }

            return (amount / 100);
        },

        hasZeroDecimals: function(currency) {
            // Adds support for currencies with zero decimals
            for (var i = 0; i < this.zeroDecimals.length; i++) {
                if (this.zeroDecimals[i] === currency.toUpperCase()) {
                    return true;
                }
            }

            return false;
        }
    };

    $(document).ready(function($) {
        enupalStripe.init();
    });
})(jQuery);