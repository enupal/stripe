(function($)
{
    /**
     * EnupalStripeProduct class
     */
    var EnupalStripeProduct = Garnish.Base.extend({

        options: null,
        errorModal: null,
        productModal: null,

        /**
         * The constructor.
         */
        init: function()
        {
            this.addListener($("#fields-show-product"), 'activate', 'showProduct');
            this.addListener($("#close-modal"), 'activate', 'closeModal');
        },

        showProduct: function(option)
        {
            if (this.productModal)
            {
                this.productModal.show();
            }
            else
            {
                var $div = $('#modal-product');
                this.productModal = new Garnish.Modal($div);
            }
        },

        closeModal: function(option)
        {
            this.productModal.hide();
        },

    });

    window.EnupalStripeProduct = EnupalStripeProduct;

})(jQuery);