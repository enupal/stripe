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
        globalStyle: {},

        init: function(style) {
            this.globalStyle = {
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
            // Overrides styles
            if (style){
                this.globalStyle = style;
            }

            this.paymentFormsList = $('.enupal-stripe-form-elements');

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

            var that = this;

            //  Firefox is cached for some reason when we empty the enupal--hidden input.
            if (navigator.userAgent.indexOf("Firefox") < 0) {
                // reset our values
                $(enupalButtonElement).find('[name="enupalStripe[stripeData]"]').val('');
            }

            this.finalData.finalAmount = enupalStripeData.stripe.amount;

            // Create a Stripe client.
            var stripe = Stripe(enupalStripeData.pbk);

            // Create an instance of Elements.
            var elements = stripe.elements({locale: enupalStripeData.stripe.locale});

            var paymentTypeInput = $(enupalButtonElement).find('[name="paymentType"]');

            // Custom styling can be passed to options when creating an Element.
            // (Note that this demo uses a wider set of styles than the guide below.)

            var pTypes = enupalStripeData.paymentTypeIds;

            if (pTypes.length > 1){
                var ccWrapper = enupalButtonElement.find('.cc-wrapper');
                var idealWrapper = enupalButtonElement.find('.ideal-wrapper');
                var sofortWrapper = enupalButtonElement.find('.sofort-wrapper');
                $("#paymentMethod-"+enupalStripeData.paymentFormId).change(function () {
                    var paymentType = this.value;
                    if (paymentType == 1){// Credit Card
                        paymentTypeInput.val(paymentType);
                        ccWrapper.removeClass('enupal--hidden');
                        idealWrapper.addClass('enupal--hidden');
                        sofortWrapper.addClass('enupal--hidden');
                    }else if (paymentType == 2){// iDEAL
                        idealWrapper.removeClass('enupal--hidden');
                        ccWrapper.addClass('enupal--hidden');
                        sofortWrapper.addClass('enupal--hidden');
                        paymentTypeInput.val(paymentType);
                    }else if (paymentType == 3){// SOFORT
                        sofortWrapper.removeClass('enupal--hidden');
                        idealWrapper.addClass('enupal--hidden');
                        ccWrapper.addClass('enupal--hidden');
                        paymentTypeInput.val(paymentType);
                    }
                });
            }

            if (enupalStripeData.enableShippingAddress && enupalStripeData.enableBillingAddress){
                var shippingAddressWrapper = enupalButtonElement.find('.shippingAddressContainer');
                $("#sameAddressToggle-"+enupalStripeData.paymentFormId).change(function() {
                    if(this.checked) {
                        shippingAddressWrapper.addClass('enupal--hidden');
                    }else{
                        shippingAddressWrapper.removeClass('enupal--hidden');
                    }
                });
            }

            if (enupalStripeData.coupon.enabled){
                var $couponButton = $("#check-coupon-button-"+enupalStripeData.paymentFormId);
                this.updateTotalAmoutLabel(enupalButtonElement, enupalStripeData);
                var $removeCoupon = enupalButtonElement.find("#remove-coupon-"+enupalStripeData.paymentFormId);
                this.updatesTotalLabelOnChoosesPlan(enupalButtonElement, enupalStripeData);

                $removeCoupon.click(function(event) {
                    event.preventDefault();
                    that.handleRemoveCoupon(enupalButtonElement, enupalStripeData, that);
                });

                $couponButton.click(function(event) {
                    event.preventDefault();
                    that.handleCouponValidation(enupalButtonElement, enupalStripeData, that);
                });
            }

            for ( var i = 0, l = pTypes.length; i < l; i++ ) {
                if (pTypes[i] == 1){// Credit Card
                    if (i == 0){
                        enupalButtonElement.find('.cc-wrapper').removeClass('enupal--hidden');
                        paymentTypeInput.val(pTypes[i]);
                    }
                    this.createCardElement(stripe, enupalStripeData, elements, enupalButtonElement);
                }else if (pTypes[i] == 2){// iDEAL
                    if (i == 0){
                        enupalButtonElement.find('.ideal-wrapper').removeClass('enupal--hidden');
                        paymentTypeInput.val(pTypes[i]);
                    }
                    this.createIdealElement(stripe, enupalStripeData, enupalButtonElement, elements);
                }else if (pTypes[i] == 3){// SOFORT
                    if (i == 0){
                        enupalButtonElement.find('.ideal-wrapper').removeClass('enupal--hidden');
                        enupalButtonElement.find('.cc-wrapper').removeClass('enupal--hidden');
                        paymentTypeInput.val(pTypes[i]);
                    }

                    this.createSofortElement(stripe, enupalStripeData, enupalButtonElement, elements);
                }
            }

        },

        updateTotalAmoutLabel(enupalButtonElement, enupalStripeData)
        {
            var $totalMessage = enupalButtonElement.find("#total-amount-value-"+enupalStripeData.paymentFormId);
            var amount = this.getFinalAmount(enupalButtonElement, enupalStripeData);
            if ($totalMessage){
                $totalMessage.text(amount);
            }
        },

        handleRemoveCoupon(enupalButtonElement, enupalStripeData, that)
        {
            var $couponMessage = enupalButtonElement.find("#coupon-message-"+enupalStripeData.paymentFormId);
            var $removeCoupon = enupalButtonElement.find("#remove-coupon-"+enupalStripeData.paymentFormId);
            var $couponInput = enupalButtonElement.find("#couponCode-"+enupalStripeData.paymentFormId);
            if ($couponInput){
                $couponInput.val('');
            }
            if ($couponMessage){
                $couponMessage.text('');
            }
            $removeCoupon.addClass("enupal--hidden");

            enupalButtonElement.find('[name="enupalCouponCode"]').val('');
            that.updateTotalAmoutLabel(enupalButtonElement, enupalStripeData);
        },

        handleCouponValidation(enupalButtonElement, enupalStripeData, that)
        {
            var $couponButton = enupalButtonElement.find("#check-coupon-button-"+enupalStripeData.paymentFormId);
            var $couponMessage = enupalButtonElement.find("#coupon-message-"+enupalStripeData.paymentFormId);
            var $removeCoupon = enupalButtonElement.find("#remove-coupon-"+enupalStripeData.paymentFormId);
            var $totalMessage = enupalButtonElement.find("#total-amount-value-"+enupalStripeData.paymentFormId);
            var $couponInput = enupalButtonElement.find("#couponCode-"+enupalStripeData.paymentFormId);
            var couponCode = $couponInput.val();
            var stripeConfig = enupalStripeData.stripe;
            $couponInput.val('');

            var amount = this.convertToCents(that.getFinalAmount(enupalButtonElement, enupalStripeData), stripeConfig.currency);
            $couponButton.prop('disabled', true);
            var isRecurring = this.getIsRecurring(enupalButtonElement, enupalStripeData);

            var data = {
                'action': 'enupal-stripe/coupons/validate',
                'amount' : amount,
                'couponCode': couponCode,
                'isRecurring': isRecurring,
                'currency': stripeConfig.currency,
                'successMessage': enupalStripeData.coupon.successMessage
            };

            $.ajax({
                type:"POST",
                url:"enupal/validate-coupon",
                data: data,
                dataType : 'json',
                success: function(response) {
                    if (response.success === true){
                        $couponMessage.removeClass('coupon-error');
                        $couponMessage.text(response.successMessage);
                        $removeCoupon.removeClass('enupal--hidden');

                        if ($totalMessage){
                            $totalMessage.text(response.finalAmount);
                        }
                        enupalButtonElement.find('[name="enupalCouponCode"]').val(response.coupon.id);
                    }else{
                        $couponMessage.addClass('coupon-error');
                        $removeCoupon.addClass('enupal--hidden');
                        $couponMessage.text(enupalStripeData.coupon.errorMessage);
                    }
                    $couponButton.prop('disabled', false);
                }.bind(this),
                error: function(xhr, status, err) {
                    $couponButton.prop('disabled', false);
                    console.error(xhr, status, err.toString());
                }.bind(this)
            });
        },

        createCardElement: function(stripe, enupalStripeData, elements, enupalButtonElement) {
            var paymentFormId = 'stripe-payments-submit-button-'+enupalStripeData.paymentFormId;

            // Create an instance of the card Element.
            var card = elements.create('card', {hidePostalCode: true, style: this.globalStyle});

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

            var form = enupalButtonElement[0];
            var that = this;

            // Handle form submission.
            enupalButtonElement.find('#'+paymentFormId).on('click', function(e) {
                var paymentType = $(enupalButtonElement).find('[name="paymentType"]').val();
                if (paymentType != 1){
                    return true;
                }
                var form = enupalButtonElement[0];

                // Avoid focusable error on js
                if (enupalStripeData.enableBillingAddress && enupalStripeData.enableBillingAddress){
                    var sameBillingAndShipping = $(enupalButtonElement).find('[name="enupalStripe[sameAddressToggle]"]').val();
                    if (sameBillingAndShipping == 'on'){
                       $(enupalButtonElement).find('[name="address[name]"]').removeAttr('required');
                       $(enupalButtonElement).find('[name="address[line1]"]').removeAttr('required');
                       $(enupalButtonElement).find('[name="address[city]"]').removeAttr('required');
                       $(enupalButtonElement).find('[name="address[state]"]').removeAttr('required');
                       $(enupalButtonElement).find('[name="address[zip]"]').removeAttr('required');
                       $(enupalButtonElement).find('[name="address[country]"]').removeAttr('required');
                    }
                }

                if (!form.checkValidity()) {
                    var isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
                    if (isSafari){
                        $('#'+form.id+' :input:visible[required="required"]').each(function () {
                            if (!this.validity.valid) {
                                $(this).focus();
                                $(this).attr("placeholder", this.validationMessage).addClass('placeholderError');
                                $(this).val(''); // clear value so it shows error message on Placeholder.
                                return false;
                            }
                        });
                    }

                    if (form.reportValidity) {
                        form.reportValidity();
                    } else {
                        //warn IE users somehow
                    }
                }else {
                    e.preventDefault();
                    var options = {};
                    // Disable pay button and show a nice UI message
                    that.showProcessingText(enupalButtonElement, enupalStripeData);

                    if (enupalStripeData.enableBillingAddress){
                        options = {
                            name: $(enupalButtonElement).find('[name="billingAddress[name]"]').val(),
                            address_line1: $(enupalButtonElement).find('[name="billingAddress[line1]"]').val(),
                            address_city: $(enupalButtonElement).find('[name="billingAddress[city]"]').val(),
                            address_state: $(enupalButtonElement).find('[name="billingAddress[state]"]').val(),
                            address_zip: $(enupalButtonElement).find('[name="billingAddress[zip]"]').val(),
                            address_country: $(enupalButtonElement).find('[name="billingAddress[country]"]').val(),
                        };
                    }

                    stripe.createToken(card, options).then(function(result) {
                        if (result.error) {
                            // Inform the user if there was an error.
                            var errorElement = document.getElementById('card-errors-' + enupalStripeData.paymentFormId);
                            errorElement.textContent = result.error.message;
                        } else {
                            // Send the token to your server.
                            that.submitForm(enupalStripeData, enupalButtonElement, paymentFormId, result.token);
                        }
                    });
                }
            });
        },

        showProcessingText(enupalButtonElement, enupalStripeData)
        {
            var paymentFormId = 'stripe-payments-submit-button-'+enupalStripeData.paymentFormId;

            enupalButtonElement.find('#' + paymentFormId)
              .prop('disabled', true);
            if (enupalButtonElement.find('#' + paymentFormId).find('span').length){
                enupalButtonElement.find('#' + paymentFormId).find('span')
                  .text(enupalStripeData.paymentButtonProcessingText);
            }else{
                enupalButtonElement.find('#' + paymentFormId).text(enupalStripeData.paymentButtonProcessingText);
            }


        },

        submitForm: function(enupalStripeData, enupalButtonElement, paymentFormId, token) {
            var enupalStripeDataSubmission = $.extend(true, {}, enupalStripeData);
            var stripeConfig = enupalStripeDataSubmission.stripe;
            stripeConfig.amount = this.convertToCents(this.getFinalAmount(enupalButtonElement, enupalStripeDataSubmission), stripeConfig.currency);
            enupalButtonElement.find('[name="enupalStripe[amount]"]').val(stripeConfig.amount);
            enupalButtonElement.find('[name="enupalStripe[testMode]"]').val(enupalStripeDataSubmission.testMode);
            if (token){
                enupalButtonElement.find('[name="enupalStripe[token]"]').val(token.id);
            }

            // Unbind original form submit trigger before calling again to "reset" it and submit normally.
            enupalButtonElement.unbind('submit', [enupalButtonElement, enupalStripeData]);

            enupalButtonElement.submit();
        },

        createIdealElement: function(stripe, enupalStripeData, enupalButtonElement, elements) {
            var that = this;

            var form = enupalButtonElement[0];

            var paymentFormId = 'stripe-payments-submit-button-'+enupalStripeData.paymentFormId;

            // Create an instance of the idealBank Element.
            var idealBank = elements.create('idealBank', {style: this.globalStyle});
            // Add an instance of the idealBank Element into the `ideal-bank-element` <div>.
            idealBank.mount('#ideal-bank-element-'+enupalStripeData.paymentFormId);

            idealBank.on('change', function(event) {
                var bank = event.value;
                enupalButtonElement.find('[name="idealBank"]').val(bank);
            });

            // Handle form submission.
            form.addEventListener('submit', function(event) {
                var paymentType = $(enupalButtonElement).find('[name="paymentType"]').val();
                if (paymentType != 2){
                    return true;
                }
                event.preventDefault();
                // Disable pay button and show a nice UI message
                that.showProcessingText(enupalButtonElement, enupalStripeData);
                that.submitForm(enupalStripeData, enupalButtonElement, paymentFormId, null);
            });
        },

        createSofortElement: function(stripe, enupalStripeData, enupalButtonElement, elements) {
            var that = this;

            var form = enupalButtonElement[0];

            var paymentFormId = 'stripe-payments-submit-button-'+enupalStripeData.paymentFormId;

            // Handle form submission.
            form.addEventListener('submit', function(event) {
                var paymentType = $(enupalButtonElement).find('[name="paymentType"]').val();
                if (paymentType != 3){
                    return true;
                }
                event.preventDefault();
                that.showProcessingText(enupalButtonElement, enupalStripeData);
                that.submitForm(enupalStripeData, enupalButtonElement, paymentFormId, null);
            });
        },

        getIsRecurring: function(enupalButtonElement, enupalStripeData) {
            var isRecurring = false;
            if (enupalStripeData.amountType == 1) {
                isRecurring = enupalButtonElement.find('[name="enupalStripe[recurringToggle]"]').is(":checked");
            }

            if (enupalStripeData.enableSubscriptions) {
                isRecurring = true;
            }

            return isRecurring;
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
                var customPlanAmount = null;

                if (subscriptionType == 0) {

                    if (enupalStripeData.singleSetupFee > 0) {
                        fee = enupalStripeData.singleSetupFee;
                    }
                    // single plan
                    if (enupalStripeData.enableCustomPlanAmount) {
                        // Custom plan
                        customPlanAmount = enupalButtonElement.find('[name="enupalStripe[customPlanAmount]"]').val();

                        if (('undefined' !== customPlanAmount) && (customPlanAmount > 0)) {
                            finalAmount = customPlanAmount;
                        }
                    }
                } else {
                    // Custom plan
                    var customPlanAmountId = null;
                    if (enupalStripeData.subscriptionStyle == 'radio') {
                        customPlanAmountId = enupalButtonElement.find('input[name="enupalStripe[enupalMultiPlan]"]:checked').val();
                    } else {
                        customPlanAmountId = enupalButtonElement.find('[name="enupalStripe[enupalMultiPlan]"]').val();
                    }
                    customPlanAmount = null;

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

            var finalTotalAmount = parseFloat(finalAmount) + parseFloat(fee);

            return finalTotalAmount;
        },

        updatesTotalLabelOnChoosesPlan(enupalButtonElement, enupalStripeData)
        {
            var that = this;
            if (enupalStripeData.subscriptionType !== 0){
                if (enupalStripeData.subscriptionStyle == 'radio') {
                    var radio = enupalButtonElement.find('input[name="enupalStripe[enupalMultiPlan]"]');
                    $(radio).change(function(){
                        if ($(this).is(':checked')) {
                            that.handleRemoveCoupon(enupalButtonElement, enupalStripeData, that);
                        }
                     });
                } else {
                    var dropdown =  enupalButtonElement.find('[name="enupalStripe[enupalMultiPlan]"]');
                    $(dropdown).on('change', function() {
                        that.handleRemoveCoupon(enupalButtonElement, enupalStripeData, that);
                    });
                }
            }
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
        var globalStyle = null;
        if (typeof enupalStyleOverrides !== 'undefined'){
            globalStyle = enupalStyleOverrides;
        }
        enupalStripe.init(globalStyle);
    });
})(jQuery);