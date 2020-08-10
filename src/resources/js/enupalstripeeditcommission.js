(function($) {
    /**
     * EnupalStripeEditCommission class
     */
    var EnupalStripeEditCommission = Garnish.Base.extend({

        $transferButton: null,

        /**
         * The constructor.
         */
        init: function() {
            // init method

            this.$transferButton = $('#fields-pay-payment');
            this.addListener(this.$transferButton, 'click', 'handleTransfer');
        },

        handleTransfer: function(option) {
            var total = $("#fields-total-label").text();
            if (!confirm(Craft.t('enupal-stripe', 'Are you sure you want to transfer '+total+' to this vendor?'))) {
                return true;
            }

            if (this.$transferButton.hasClass('disabled')) {
                return;
            }

            var that = this;

            this.$transferButton.addClass('disabled').siblings('.spinner').removeClass('hidden');

            var commissionId = $("input[name='commissionId']").val();
            var data = {
                'commissionId' : commissionId
            };

            Craft.postActionRequest('enupal-stripe/commissions/transfer-payment', data, function(response, textStatus) {
                that.$transferButton.removeClass('disabled').siblings('.spinner').addClass('hidden');
                if (textStatus === 'success') {
                    if ("error" in response ){
                        Craft.cp.displayError(Craft.t('enupal-stripe', response.error));
                        location.reload();
                    }
                    else if (response.success) {
                        Craft.cp.displayNotice(Craft.t('enupal-stripe', 'Transfer successfully processed'));
                        location.reload();
                    }else{
                        Craft.cp.displayError(Craft.t('enupal-stripe', 'Unable to transfer payment - Check your logs'));
                        location.reload();
                    }
                }
            });
        },
    });

    window.EnupalStripeEditCommission = EnupalStripeEditCommission;

})(jQuery);