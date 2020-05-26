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
                $wrapper.removeClass('hidden');
            }
            else{
                $wrapper.addClass('hidden');
            }
        },

        handleEnableSubscription: function(option) {
            var $oneTimeWrapper = $("#fields-one-time-payment-wrapper");
            var value = $("input[name='fields[enableSubscriptions]']").val();

            if (value == 0){
                $oneTimeWrapper.removeClass('hidden');
            }
            else{
                $oneTimeWrapper.addClass('hidden');
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
                $elementsWrapper.removeClass('hidden');
                $("#fields-sca-warning").removeClass("hidden");
                if (useSca){
                    $returnUrlForm.removeClass("hidden");
                }

                $checkoutElementsWrapper.addClass('hidden');
                $successUrlWrapper.addClass('hidden');
                $cancelUrlWrapper.addClass('hidden');
                $submitTypeWrapper.addClass('hidden');
            }
            else{
                $("#fields-sca-warning").addClass("hidden");
                $elementsWrapper.addClass('hidden');
                $checkoutElementsWrapper.removeClass('hidden');
                $successUrlWrapper.removeClass('hidden');
                $cancelUrlWrapper.removeClass('hidden');
                $submitTypeWrapper.removeClass('hidden');
                if (useSca){
                    $returnUrlForm.addClass("hidden");
                }
            }
        },

        handleEnableTemplateOverrides: function(option) {
            var $templateOverridesWrapper = $("#fields-templateOverridesFolder-field");
            var value = $("input[name='fields[enableTemplateOverrides]']").val();

            if (value == 0){
                $templateOverridesWrapper.addClass('hidden');
            }
            else{
                $templateOverridesWrapper.removeClass('hidden');
            }
        },

        handleRefreshPlans: function(option) {
            if (this.$refreshPlansButton.hasClass('disabled')) {
                return;
            }

            var that = this;

            this.$refreshPlansButton.addClass('disabled').siblings('.spinner').removeClass('hidden');

            var $planSelect = $("#fields-singlePlanInfo");

            Craft.postActionRequest('enupal-stripe/payment-forms/refresh-plans', {}, function(response, textStatus) {
                that.$refreshPlansButton.removeClass('disabled').siblings('.spinner').addClass('hidden');
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
                $singleSubscriptionWrapper.removeClass('hidden');
                $multipleSubscriptionWrapper.addClass('hidden');
            }else{
                $singleSubscriptionWrapper.addClass('hidden');
                $multipleSubscriptionWrapper.removeClass('hidden');
            }
        },

        handleRecurringToggle: function()
        {
            var value = this.$recurringToggle.val();
            var recurringTitle = $("#fields-one-time-payment-wrapper h6");

            if (value == 1){
                recurringTitle.text("RECURRING PAYMENT");
                this.$recurringTypeField.removeClass('hidden');
            }
            else{
                recurringTitle.text("ONE TIME PAYMENT");
                this.$recurringTypeField.addClass('hidden');
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
                $fieldWrapper.addClass('hidden');
                amountLabel.text("Amount");
                this.$minimumAmountField.addClass('hidden');
                this.$recurringToggleField.addClass('hidden');
                this.$recurringTypeField.addClass('hidden');
            }
            else{
                $fieldWrapper.removeClass('hidden');
                amountLabel.text("Default Amount");
                this.$minimumAmountField.removeClass('hidden');
                this.$recurringToggleField.removeClass('hidden');
                if (recurringValue == 1) {
                    this.$recurringTypeField.removeClass('hidden');
                }
            }
        },

    });

    window.EnupalStripe = EnupalStripe;

})(jQuery);