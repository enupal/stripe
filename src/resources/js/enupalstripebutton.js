(function($) {
    /**
     * EnupalStripe class
     */
    var EnupalStripe = Garnish.Base.extend({

        options: null,
        $unlimitedStock: null,
        $currencySelect: null,

        /**
         * The constructor.
         */
        init: function() {
            // init method
            this.$unlimitedStock = $("#fields-unlimited-stock");
            this.$currencySelect = $("#fields-currency");

            this.addListener(this.$unlimitedStock, 'change', 'handleUnlimitedStock');
            this.addListener(this.$currencySelect, 'change', 'handleCurrencySelect');
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

            $shippingDiv.text(value);
        }

    });

    window.EnupalStripe = EnupalStripe;

})(jQuery);