(function($) {
    /**
     * EnupalStripe class
     */
    var EnupalStripe = Garnish.Base.extend({

        options: null,
        $unlimitedStock: null,
        $currencySelect: null,
        $amountTypeSelect: null,
        $amountLabel: null,
        $minimumAmountField: null,
        $recurringToggleField: null,
        $recurringTypeField: null,
        $recurringToggle: null,
        $subscriptionTypeSelect: null,
        $refreshPlansButton: null,
        $enableSubscription: null,
        $enableCheckout: null,
        $enableSingleCustomAmount: null,
        $enableTemplateOverrides: null,

        /**
         * The constructor.
         */
        init: function() {
            // init method
            this.$refreshPlansButton = $('#fields-refresh-plans-btn');
            this.$unlimitedStock = $("#fields-unlimited-stock");
            this.$currencySelect = $("#fields-currency");
            this.$subscriptionTypeSelect = $("#fields-subscriptionType");
            this.$recurringToggle = $("input[name='fields[enableRecurringPayment]']");
            this.$amountTypeSelect = $("#fields-amountType");
            this.$amountLabel = $("#fields-amount-label");
            this.$minimumAmountField = $("#fields-minimumAmount-field");
            this.$recurringToggleField = $("#fields-enableRecurringPayment-field");
            this.$recurringTypeField = $("#fields-recurringPaymentType-field");
            this.$enableSubscription = $("#fields-enableSubscriptions");
            this.$enableCheckout = $("#fields-enableCheckout");
            this.$enableSingleCustomAmount = $("#fields-enableCustomPlanAmount");
            this.$enableTemplateOverrides = $("#fields-enableTemplateOverrides");

            this.addListener(this.$unlimitedStock, 'change', 'handleUnlimitedStock');
            this.addListener(this.$subscriptionTypeSelect, 'change', 'handleSubscriptionTypeSelect');
            this.addListener(this.$currencySelect, 'change', 'handleCurrencySelect');
            this.addListener(this.$amountTypeSelect, 'change', 'handleAmountTypeSelect');
            this.addListener(this.$recurringToggleField, 'change', 'handleRecurringToggle');
            this.addListener(this.$refreshPlansButton, 'click', 'handleRefreshPlans');
            this.addListener(this.$enableSubscription, 'change', 'handleEnableSubscription');
            this.addListener(this.$enableCheckout, 'change', 'handleEnableCheckout');
            this.addListener(this.$enableSingleCustomAmount, 'change', 'handleEnableSingleCustomAmount');
            this.addListener(this.$enableTemplateOverrides, 'change', 'handleEnableTemplateOverrides');

            this.handleRecurringToggle();
            this.handleAmountTypeSelect();
            this.handleSubscriptionTypeSelect();
            this.handleEnableTemplateOverrides();
            this.handleEnableCheckout();
        },

        handleEnableSingleCustomAmount: function(option) {
            var $wrapper = $("#fields-single-plan-select-wrapper");
            var value = $("input[name='fields[enableCustomPlanAmount]']").val();

            if (value == 0){
                $wrapper.removeClass('enupal--hidden');
            }
            else{
                $wrapper.addClass('enupal--hidden');
            }
        },

        handleEnableSubscription: function(option) {
            var $oneTimeWrapper = $("#fields-one-time-payment-wrapper");
            var value = $("input[name='fields[enableSubscriptions]']").val();

            if (value == 0){
                $oneTimeWrapper.removeClass('enupal--hidden');
            }
            else{
                $oneTimeWrapper.addClass('enupal--hidden');
            }
        },

        handleEnableCheckout: function(option) {
            var $elementsWrapper = $("#fields-paymentType-field");
            var $checkoutElementsWrapper = $("#fields-checkoutPaymentType-field");
            var $successUrlWrapper = $("#fields-checkoutSuccessUrl-field");
            var $cancelUrlWrapper = $("#fields-checkoutCancelUrl-field");
            var $submitTypeWrapper = $("#fields-checkoutSubmitType-field");
            var $returnUrlForm = $("#fields-returnUrl-field");

            var value = $("input[name='fields[enableCheckout]']").val();
            var useSca = $("input[name='useSca']").val();

            if (value == 0){
                $elementsWrapper.removeClass('enupal--hidden');
                $("#fields-sca-warning").removeClass("enupal--hidden");
                if (useSca == 1){
                    $returnUrlForm.removeClass("enupal--hidden");
                }

                $checkoutElementsWrapper.addClass('enupal--hidden');
                $successUrlWrapper.addClass('enupal--hidden');
                $cancelUrlWrapper.addClass('enupal--hidden');
                $submitTypeWrapper.addClass('enupal--hidden');
            }
            else{
                $("#fields-sca-warning").addClass("enupal--hidden");
                $elementsWrapper.addClass('enupal--hidden');
                $checkoutElementsWrapper.removeClass('enupal--hidden');

                $successUrlWrapper.addClass('enupal--hidden');
                $cancelUrlWrapper.addClass('enupal--hidden');
                $submitTypeWrapper.addClass('enupal--hidden');

                if (useSca == 1){
                    $returnUrlForm.addClass("enupal--hidden");
                    $successUrlWrapper.removeClass('enupal--hidden');
                    $cancelUrlWrapper.removeClass('enupal--hidden');
                    $submitTypeWrapper.removeClass('enupal--hidden');
                }else {
                    $successUrlWrapper.addClass('enupal--hidden');
                    $cancelUrlWrapper.addClass('enupal--hidden');
                    $submitTypeWrapper.addClass('enupal--hidden');
                }
            }
        },

        handleEnableTemplateOverrides: function(option) {
            var $templateOverridesWrapper = $("#fields-templateOverridesFolder-field");
            var value = $("input[name='fields[enableTemplateOverrides]']").val();

            if (value == 0){
                $templateOverridesWrapper.addClass('enupal--hidden');
            }
            else{
                $templateOverridesWrapper.removeClass('enupal--hidden');
            }
        },

        handleRefreshPlans: function(option) {
            if (this.$refreshPlansButton.hasClass('disabled')) {
                return;
            }

            var that = this;

            this.$refreshPlansButton.addClass('disabled').siblings('.spinner').removeClass('enupal--hidden');

            var $planSelect = $("#fields-singlePlanInfo");

            Craft.postActionRequest('enupal-stripe/payment-forms/refresh-plans', {}, function(response, textStatus) {
                that.$refreshPlansButton.removeClass('disabled').siblings('.spinner').addClass('enupal--hidden');
                if (textStatus === 'success') {
                    if ("error" in response ){
                        Craft.cp.displayError(Craft.t('enupal-stripe', response.error));
                    }
                    else if (response.plans.length > 0) {
                        var currentPlan = $planSelect.val(),
                            currentPlanStillExists = false;

                        $planSelect.empty();

                        for (var i = 0; i < response.plans.length; i++) {
                            if (response.plans[i].value === currentPlan) {
                                currentPlanStillExists = true;
                            }

                            $planSelect.append('<option value="' + response.plans[i].value + '">' + response.plans[i].label + '</option>');
                        }

                        if (currentPlanStillExists) {
                            $planSelect.val(currentPlan);
                        }
                    }else{
                        Craft.cp.displayError(Craft.t('enupal-stripe', 'Unable to found plans in your Stripe account'));
                    }
                }
            });
        },

        handleUnlimitedStock: function(option) {
            var $checkbox = $(option.currentTarget),
                $text = $checkbox.parent().prevAll('.textwrapper:first').children('.text:first');

            if ($checkbox.prop('checked')) {
                $text.prop('disabled', true).addClass('disabled').val('');
            }
            else {
                $text.prop('disabled', false).removeClass('disabled').focus();
            }
            
        },

        handleCurrencySelect: function() {
            var value = this.$currencySelect.val();
            var $shippingDiv = $("#fields-shippingAmount-field").find(".label, .light");
            var $amountDiv = $("#fields-amount-field").find(".label, .light");
            var $minimumDiv = this.$minimumAmountField.find(".label, .light");

            $shippingDiv.text(value);
            $amountDiv.text(value);
            $minimumDiv.text(value);
        },

        handleSubscriptionTypeSelect: function() {
            var value = this.$subscriptionTypeSelect.val();
            var $singleSubscriptionWrapper = $("#fields-single-subscription-wrapper");
            var $multipleSubscriptionWrapper = $("#fields-multiple-subscriptions-wrapper");

            if (value == 0) {
                $singleSubscriptionWrapper.removeClass('enupal--hidden');
                $multipleSubscriptionWrapper.addClass('enupal--hidden');
            }else{
                $singleSubscriptionWrapper.addClass('enupal--hidden');
                $multipleSubscriptionWrapper.removeClass('enupal--hidden');
            }
        },

        handleRecurringToggle: function()
        {
            var value = this.$recurringToggle.val();
            var recurringTitle = $("#fields-one-time-payment-wrapper h6");

            if (value == 1){
                recurringTitle.text("RECURRING PAYMENT");
                this.$recurringTypeField.removeClass('enupal--hidden');
            }
            else{
                recurringTitle.text("ONE TIME PAYMENT");
                this.$recurringTypeField.addClass('enupal--hidden');
            }
        },

        handleAmountTypeSelect: function()
        {
            var value = this.$amountTypeSelect.val();
            var $fieldWrapper = $("#fields-customAmountLabel-field");
            var currentAmountLabel = this.$amountLabel.html();
            var amountLabel = $("#fields-amount-label");
            var recurringValue = this.$recurringToggle.val();

            if (value == '0'){
                $fieldWrapper.addClass('enupal--hidden');
                amountLabel.text("Amount");
                this.$minimumAmountField.addClass('enupal--hidden');
                this.$recurringToggleField.addClass('enupal--hidden');
                this.$recurringTypeField.addClass('enupal--hidden');
            }
            else{
                $fieldWrapper.removeClass('enupal--hidden');
                amountLabel.text("Default Amount");
                this.$minimumAmountField.removeClass('enupal--hidden');
                this.$recurringToggleField.removeClass('enupal--hidden');
                if (recurringValue == 1) {
                    this.$recurringTypeField.removeClass('enupal--hidden');
                }
            }
        },

    });

    window.EnupalStripe = EnupalStripe;

})(jQuery);