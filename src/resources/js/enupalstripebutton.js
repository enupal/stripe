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

        /**
         * The constructor.
         */
        init: function() {
            // init method
            this.$unlimitedStock = $("#fields-unlimited-stock");
            this.$currencySelect = $("#fields-currency");
            this.$subscriptionTypeSelect = $("#fields-subscriptionType");
            this.$recurringToggle = $("input[name='fields[enableRecurringPayment]']");
            this.$amountTypeSelect = $("#fields-amountType");
            this.$amountLabel = $("#fields-amount-label");
            this.$minimumAmountField = $("#fields-minimumAmount-field");
            this.$recurringToggleField = $("#fields-enableRecurringPayment-field");
            this.$recurringTypeField = $("#fields-recurringPaymentType-field");

            this.addListener(this.$unlimitedStock, 'change', 'handleUnlimitedStock');
            this.addListener(this.$subscriptionTypeSelect, 'change', 'handleSubscriptionTypeSelect');
            this.addListener(this.$currencySelect, 'change', 'handleCurrencySelect');
            this.addListener(this.$amountTypeSelect, 'change', 'handleAmountTypeSelect');
            this.addListener(this.$recurringToggleField, 'change', 'handleRecurringToggle');

            this.handleRecurringToggle();
            this.handleAmountTypeSelect();
            this.handleSubscriptionTypeSelect();
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
            var $minimumDiv = this.$minimumAmountField.find(".label, .light");

            $shippingDiv.text(value);
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

            if (value == 1){
                this.$recurringTypeField.removeClass('hidden');
            }
            else{
                this.$recurringTypeField.addClass('hidden');
            }
        },

        handleAmountTypeSelect: function()
        {
            var value = this.$amountTypeSelect.val();
            var $fieldWrapper = $("#fields-customAmountLabel-field");
            var currentAmountLabel = this.$amountLabel.html();

            if (value == '0'){
                $fieldWrapper.addClass('hidden');
                this.$minimumAmountField.addClass('hidden');
                this.$recurringToggleField.addClass('hidden');
                this.$recurringTypeField.addClass('hidden');
            }
            else{
                $fieldWrapper.removeClass('hidden');
                this.$minimumAmountField.removeClass('hidden');
                this.$recurringToggleField.removeClass('hidden');
                this.$recurringTypeField.removeClass('hidden');
            }
        },

    });

    window.EnupalStripe = EnupalStripe;

})(jQuery);