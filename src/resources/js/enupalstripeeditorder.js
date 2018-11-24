(function($) {
    /**
     * EnupalStripeEditOrder class
     */
    var EnupalStripeEditOrder = Garnish.Base.extend({

        $refreshSubscriptionButton: null,

        /**
         * The constructor.
         */
        init: function() {
            // init method
            this.$refreshSubscriptionButton = $('#refresh-subscriptions');

            this.addListener(this.$refreshSubscriptionButton, 'click', 'handleRefreshSubscriptions');

            this.handleRefreshSubscriptions();
        },

        handleRefreshSubscriptions: function(option) {
            if (this.$refreshSubscriptionButton.hasClass('disabled')) {
                return;
            }

            var that = this;

            this.$refreshSubscriptionButton.addClass('disabled').siblings('.spinner').removeClass('hidden');

            var $planSelect = $("#fields-singlePlanInfo");

            var data = {
                'subscriptionId' : $("#subscriptionId").val()
            };

            Craft.postActionRequest('enupal-stripe/orders/refresh-subscription', data, function(response, textStatus) {
                that.$refreshSubscriptionButton.removeClass('disabled').siblings('.spinner').addClass('hidden');
                if (textStatus === 'success') {
                    if ("error" in response ){
                        Craft.cp.displayError(Craft.t('enupal-stripe', response.error));
                    }
                    else if (response.subscription) {
                        var subscription = response.subscription;

                        $("#subs-nickname").text(subscription.planNickName);
                        $("#subs-start").text(subscription.startDate);
                        $("#subs-end").text(subscription.endDate);
                        $("#subs-interval").text(subscription.interval);
                        $("#subs-quantity").text(subscription.quantity);
                    }else{
                        Craft.cp.displayError(Craft.t('enupal-stripe', 'Unable to get subscription info'));
                    }
                }
            });
        },
    });

    window.EnupalStripeEditOrder = EnupalStripeEditOrder;

})(jQuery);