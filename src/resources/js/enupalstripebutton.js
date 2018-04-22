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

        /**
         * The constructor.
         */
        init: function() {
            // init method
            this.$unlimitedStock = $("#fields-unlimited-stock");
            this.$currencySelect = $("#fields-currency");
            this.$amountTypeSelect = $("#fields-amountType");
            this.$amountLabel = $("#fields-amount-label");
            this.$minimumAmountField = $("#fields-minimumAmount-field");


            this.addListener(this.$unlimitedStock, 'change', 'handleUnlimitedStock');
            this.addListener(this.$currencySelect, 'change', 'handleCurrencySelect');
            this.addListener(this.$amountTypeSelect, 'change', 'handleAmountTypeSelect');

            this.handleAmountTypeSelect();
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

        handleAmountTypeSelect: function()
        {
            var value = this.$amountTypeSelect.val();
            var $fieldWrapper = $("#fields-customAmountLabel-field");
            var currentAmountLabel = this.$amountLabel.html();

            if (value == '0'){
                $fieldWrapper.addClass('hidden');
                this.$minimumAmountField.addClass('hidden');
            }
            else{
                $fieldWrapper.removeClass('hidden');
                this.$minimumAmountField.removeClass('hidden');
            }
        },

    });

    window.EnupalStripe = EnupalStripe;

})(jQuery);