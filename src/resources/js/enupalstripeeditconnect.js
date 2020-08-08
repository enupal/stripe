(function($) {
    /**
     * EnupalStripeEditConnect class
     */
    var EnupalStripeEditConnect = Garnish.Base.extend({

        $transferButton: null,

        /**
         * The constructor.
         */
        init: function() {
            // init method
            this.$transferButton = $('#fields-allProducts');
            this.addListener(this.$transferButton, 'click', 'handleAllProducts');
            this.handleAllProducts();
        },

        handleAllProducts: function(option) {
            var $productsWrapper = $("#fields-products-wrapper");
            var value = $("input[name='fields[allProducts]']").val();

            if (value == 0){
                $productsWrapper.removeClass('hidden');
            }
            else{
                $productsWrapper.addClass('hidden');
            }
        },
    });

    window.EnupalStripeEditConnect = EnupalStripeEditConnect;

})(jQuery);