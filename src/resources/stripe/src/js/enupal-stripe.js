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
        // All Stripe Payment Forms
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
            if (typeof $(enupalButtonElement).find('[name="enupalStripe[stripeData]"]').val() === 'undefined'){
                return false;
            }
            // get the form ID
            var enupalStripeData = $.parseJSON($(enupalButtonElement).find('[name="enupalStripe[stripeData]"]').val());

            enupalStripeData.lineItems = enupalButtonElement.find('[name="enupalStripe[enupalLineItems]"]').val();
            enupalStripeData.removeDefaultItem = enupalButtonElement.find('[name="enupalStripe[enupalRemoveDefaultItem]"]').val();

            //  Firefox is cached for some reason when we empty the enupal--hidden input.
            if (navigator.userAgent.indexOf("Firefox") < 0) {
                // reset our values
                $(enupalButtonElement).find('[name="enupalStripe[stripeData]"]').val('');
                enupalButtonElement.find('[name="enupalStripe[enupalLineItems]"]').val('');
                enupalButtonElement.find('[name="enupalStripe[enupalRemoveDefaultItem]"]').val('');
            }

            this.finalData.finalAmount = enupalStripeData.stripe.amount;

            //  Stripe config
            var stripeHandler = null;
            var that = this;

            var paymentFormId = 'stripe-payments-submit-button-'+enupalStripeData.paymentFormId;

            if (!enupalStripeData.useSca) {
                // Stripe Checkout handler configuration.
                // Docs: https://stripe.com/docs/checkout#integration-custom
                stripeHandler = StripeCheckout.configure({
                    key: enupalStripeData.pbk,
                    token: processStripeToken,
                    opened: function() {
                    },
                    closed: function() {
                    }
                });

                // Callback function to handle StripeCheckout.configure
                function processStripeToken(token, args) {
                    // At this point the Stripe Checkout overlay is validated and submitted.
                    // Set values to enupal--hidden elements to pass via POST when submitting the form for payment.
                    enupalButtonElement.find('[name="enupalStripe[token]"]').val(token.id);
                    enupalButtonElement.find('[name="enupalStripe[email]"]').val(token.email);

                    // Add others values to form
                    enupalStripe.addValuesToForm(enupalButtonElement, args, enupalStripeData);

                    // Disable pay button and show a nice UI message
                    that.showProcessingText(enupalButtonElement, enupalStripeData);

                    // Unbind original form submit trigger before calling again to "reset" it and submit normally.
                    enupalButtonElement.unbind('submit', [enupalButtonElement, enupalStripeData]);

                    enupalButtonElement.submit();
                }
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

            // Pay Button clicked
            enupalButtonElement.find('#'+paymentFormId).on('click', function(e) {
                var form = enupalButtonElement[0];
                if (!form.checkValidity()) {
                    if (form.reportValidity) {
                        form.reportValidity();
                    } else {
                        //warn IE users somehow
                    }
                }else{
                    e.preventDefault();
                    enupalStripe.submitPayment(enupalButtonElement, enupalStripeData, stripeHandler);
                }
            });
        },

        showProcessingText(enupalButtonElement, enupalStripeData)
        {
            enupalButtonElement.find('#stripe-payments-submit-button-' + enupalStripeData.paymentFormId)
                .prop('disabled', true);
            if (enupalButtonElement.find('#stripe-payments-submit-button-' + enupalStripeData.paymentFormId).find('span').length){
                enupalButtonElement.find('#stripe-payments-submit-button-' + enupalStripeData.paymentFormId).find('span')
                .text(enupalStripeData.paymentButtonProcessingText);
            }else{
                enupalButtonElement.find('#stripe-payments-submit-button-' + enupalStripeData.paymentFormId).text(enupalStripeData.paymentButtonProcessingText);
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

        addValuesToForm: function(enupalButtonElement, args, enupalStripeData) {
            if (enupalStripeData.stripe.shippingAddress){
                var suffix = 'shipping';
                var namespace = 'address';
                this.setAddressToHiddenValues(suffix, namespace, args, enupalButtonElement)
            }

            if (enupalStripeData.stripe.billingAddress){
                var suffix = 'billing';
                var namespace = 'billingAddress';
                this.setAddressToHiddenValues(suffix, namespace, args, enupalButtonElement)
            }
        },

        setAddressToHiddenValues(suffix, namespace, args, enupalButtonElement)
        {
            if (args[suffix+'_name']) {
                enupalButtonElement.find('[name="enupalStripe['+namespace+'][name]"]').val(args[suffix+'_name']);
            }

            if (args[suffix+'_address_country']) {
                enupalButtonElement.find('[name="enupalStripe['+namespace+'][country]"]').val(args[suffix+'_address_country_code']);
            }

            if (args[suffix+'_address_zip']) {
                enupalButtonElement.find('[name="enupalStripe['+namespace+'][zip]"]').val(args[suffix+'_address_zip']);
            }

            if (args[suffix+'_address_state']) {
                enupalButtonElement.find('[name="enupalStripe['+namespace+'][state]"]').val(args[suffix+'_address_state']);
            }

            if (args[suffix+'_address_line1']) {
                enupalButtonElement.find('[name="enupalStripe['+namespace+'][line1]"]').val(args[suffix+'_address_line1']);
            }

            if (args[suffix+'_address_city']) {
                enupalButtonElement.find('[name="enupalStripe['+namespace+'][city]"]').val(args[suffix+'_address_city']);
            }
        },

        redirectToCheckoutSession: function(enupalButtonElement, enupalStripeDataSubmission) {
            this.showProcessingText(enupalButtonElement, enupalStripeDataSubmission);
            var data = enupalButtonElement.serializeArray();
            
            data.push({name: 'enupalStripe[enupalLineItems]', value: enupalStripeDataSubmission.lineItems});
            data.push({name: 'enupalStripe[enupalRemoveDefaultItem]', value: enupalStripeDataSubmission.removeDefaultItem});

            data.push({name: 'action', value: 'enupal-stripe/checkout/create-session'});
            data.push({name: 'enupalStripeData', value: JSON.stringify(enupalStripeDataSubmission)});

            $.ajax({
                type:"POST",
                url:"enupal/stripe/create-checkout-session",
                data: data,
                dataType : 'json',
                success: function(response) {
                    if (response.success === true){
                        var stripe = Stripe(enupalStripeDataSubmission.pbk);
                        stripe.redirectToCheckout({
                            sessionId: response.sessionId
                        });
                    }else{
                        console.log("Something went wrong, please contact the admin");
                    }
                }.bind(this),
                error: function(xhr, status, err) {
                    console.error(xhr, status, err.toString());
                }.bind(this)
            });
        },

        submitPayment: function(enupalButtonElement, enupalStripeData, stripeHandler) {
            var enupalStripeDataSubmission = $.extend(true,{},enupalStripeData);
            var that = this;
            var stripeConfig = enupalStripeDataSubmission.stripe;
            stripeConfig.amount = this.convertToCents(this.getFinalAmount(enupalButtonElement, enupalStripeDataSubmission), stripeConfig.currency);
            enupalButtonElement.find('[name="enupalStripe[amount]"]').val(stripeConfig.amount);
            enupalButtonElement.find('[name="enupalStripe[testMode]"]').val(enupalStripeDataSubmission.testMode);
            // Show amount if coupon
            if (enupalStripeData.coupon.enabled){
                var couponCode = enupalButtonElement.find('[name="enupalCouponCode"]').val();
                var isRecurring = this.getIsRecurring(enupalButtonElement, enupalStripeData);
                if (couponCode){
                    var data = {
                        'action': 'enupal-stripe/coupons/validate',
                        'amount' : stripeConfig.amount,
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
                                stripeConfig.amount = response.finalAmountInCents;
                            }
                            if (enupalStripeData.useSca){
                                that.redirectToCheckoutSession(enupalButtonElement, enupalStripeDataSubmission);
                            }else{
                                stripeHandler.open(stripeConfig);
                            }
                        }.bind(this),
                        error: function(xhr, status, err) {
                            console.error(xhr, status, err.toString());
                        }.bind(this)
                    });
                }else{
                    if (enupalStripeData.useSca){
                        this.redirectToCheckoutSession(enupalButtonElement,enupalStripeDataSubmission);
                    }else{
                        stripeHandler.open(stripeConfig);
                    }
                }
            }else{
                // let's open the form
                if (enupalStripeData.useSca){
                    this.redirectToCheckoutSession(enupalButtonElement,enupalStripeDataSubmission);
                }else{
                    stripeHandler.open(stripeConfig);
                }
            }
        },

        getFinalAmount: function(enupalButtonElement, enupalStripeData){
            // We always return a default amount
            var finalAmount = enupalStripeData.stripe.amount;
            var fee = 0;
            var isRecurring = false;

            if (!enupalStripeData.enableSubscriptions){
                // Check if custom amount
                if ( enupalStripeData.amountType == 1) {
                    var customAmount = enupalButtonElement.find( '[name="enupalStripe[customAmount]"]' ).val();
                    isRecurring = enupalButtonElement.find( '[name="enupalStripe[recurringToggle]"]' ).is(":checked");

                    if ( ( 'undefined' !== customAmount ) && ( customAmount > 0 ) ) {
                        finalAmount = customAmount;
                    }
                }
            }else{
                // Subscriptions!
                var subscriptionType = enupalStripeData.subscriptionType;

                if (subscriptionType == 0){

                    if (enupalStripeData.singleSetupFee > 0){
                        fee = enupalStripeData.singleSetupFee;
                    }
                    // single plan
                    if (enupalStripeData.enableCustomPlanAmount){
                        // Custom plan
                        var customPlanAmount = enupalButtonElement.find( '[name="enupalStripe[customPlanAmount]"]' ).val();

                        if ( ( 'undefined' !== customPlanAmount ) && ( customPlanAmount > 0 ) ) {
                            finalAmount = customPlanAmount;
                        }
                    }
                }else{
                    // Custom plan
                    var customPlanAmountId = null;
                    if (enupalStripeData.subscriptionStyle == 'radio'){
                        customPlanAmountId = $('input[name="enupalStripe[enupalMultiPlan]"]:checked').val();
                    }else{
                        customPlanAmountId = enupalButtonElement.find( '[name="enupalStripe[enupalMultiPlan]"]' ).val();
                    }
                    var customPlanAmount = null;

                    if (customPlanAmountId in enupalStripeData.multiplePlansAmounts){
                        customPlanAmount = enupalStripeData.multiplePlansAmounts[customPlanAmountId]['amount'];
                        enupalStripeData.stripe['currency'] =  enupalStripeData.multiplePlansAmounts[customPlanAmountId]['currency'];
                    }

                    if (customPlanAmountId in enupalStripeData.setupFees){
                        var multiplePlanFee = enupalStripeData.setupFees[customPlanAmountId];

                        if (multiplePlanFee > 0){
                            fee = multiplePlanFee;
                        }
                    }

                    // Multi-select plan
                    if ( ( 'undefined' !== customPlanAmount ) && ( customPlanAmount > 0 ) ) {
                        finalAmount = customPlanAmount;
                    }
                }
            }

            if ((enupalStripeData.applyTax || isRecurring) && enupalStripeData.enableTaxes){
                var tax = parseFloat((enupalStripeData.tax / 100) * (parseFloat(finalAmount) + parseFloat(fee))).toFixed(2);
                finalAmount = parseFloat(finalAmount) + parseFloat(tax);
                var taxLabel = enupalStripeData.taxLabel + ': '+enupalStripeData.currencySymbol+tax;

                enupalButtonElement.find('[name="enupalStripe[taxAmount]"]').val(tax);
                enupalButtonElement.find( '[name="tax-amount-label"]' ).empty().append(taxLabel);
            }

            return parseFloat(finalAmount) + parseFloat(fee);
        },

        convertToCents: function(amount, currency) {
            if (this.hasZeroDecimals(currency)){
                return amount;
            }

            return (amount * 100).toFixed(0);
        },

        convertFromCents: function(amount, currency) {
            if (this.hasZeroDecimals(currency)){
                return amount;
            }

            return (amount / 100);
        },

        hasZeroDecimals: function(currency){
            // Adds support for currencies with zero decimals
            for (var i = 0; i < this.zeroDecimals.length; i++) {
                if (this.zeroDecimals[i] === currency.toUpperCase()){
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