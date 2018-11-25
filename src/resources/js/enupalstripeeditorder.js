(function($) {
    /**
     * EnupalStripeEditOrder class
     */
    var EnupalStripeEditOrder = Garnish.Base.extend({

        $refreshSubscriptionButton: null,
        $cancelSubscriptionButton: null,
        $refundButton: null,

        /**
         * The constructor.
         */
        init: function(isSubscription) {
            // init method
            if (isSubscription){
                this.$refreshSubscriptionButton = $('#refresh-subscription');
                this.$cancelSubscriptionButton = $('#cancel-subscription');
                this.addListener(this.$refreshSubscriptionButton, 'click', 'handleRefreshSubscription');
                this.addListener(this.$cancelSubscriptionButton, 'click', 'handleCancelSubscription');
                this.handleRefreshSubscription();
            }

            this.$refundButton = $('#fields-refund-payment');
            this.addListener(this.$refundButton, 'click', 'handleRefundPayment');
        },

        handleRefreshSubscription: function(option) {
            if (this.$refreshSubscriptionButton.hasClass('disabled')) {
                return;
            }

            var that = this;

            this.$refreshSubscriptionButton.addClass('disabled').siblings('.spinner').removeClass('hidden');

            var data = {
                'subscriptionId' : $("#subscriptionId").val()
            };
            that.$cancelSubscriptionButton.addClass('disabled');

            Craft.postActionRequest('enupal-stripe/subscriptions/refresh-subscription', data, function(response, textStatus) {
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
                        $("#subs-status").html(subscription.statusHtml);
                        $("#subs-quantity").text(subscription.quantity);

                        if (subscription.canceledAt !== null){
                            that.$cancelSubscriptionButton.addClass('hidden');
                            $("#cancel-title").text(Craft.t('enupal-stripe', 'Canceled at'));
                            $("#subs-cancel").text(subscription.canceledAt);
                        }else{
                            that.$cancelSubscriptionButton.removeClass('disabled');
                        }
                    }else{
                        Craft.cp.displayError(Craft.t('enupal-stripe', 'Unable to get subscription info'));
                    }
                }
            });
        },

        handleCancelSubscription: function(option) {
            if (!confirm(Craft.t('enupal-stripe', 'Are you sure you want to cancel this subscription?'))) {
                return true;
            }

            if (this.$cancelSubscriptionButton.hasClass('disabled')) {
                return;
            }

            var that = this;

            this.$cancelSubscriptionButton.addClass('disabled').siblings('.spinner').removeClass('hidden');

            var data = {
                'subscriptionId' : $("#subscriptionId").val()
            };

            Craft.postActionRequest('enupal-stripe/subscriptions/cancel-subscription', data, function(response, textStatus) {
                that.$cancelSubscriptionButton.removeClass('disabled').siblings('.spinner').addClass('hidden');
                if (textStatus === 'success') {
                    if ("error" in response ){
                        Craft.cp.displayError(Craft.t('enupal-stripe', response.error));
                    }
                    else if (response.success) {
                        Craft.cp.displayNotice(Craft.t('enupal-stripe', 'Subscription Canceled'));
                        that.handleRefreshSubscription();
                    }else{
                        Craft.cp.displayError(Craft.t('enupal-stripe', 'Unable to cancel subscription'));
                    }
                }
            });
        },

        handleRefundPayment: function(option) {
            if (!confirm(Craft.t('enupal-stripe', 'Are you sure you want to refund this payment?'))) {
                return true;
            }

            if (this.$refundButton.hasClass('disabled')) {
                return;
            }

            var that = this;

            this.$refundButton.addClass('disabled').siblings('.spinner').removeClass('hidden');

            var data = {
                'orderNumber' : $("#fields-order-number-short").text()
            };

            Craft.postActionRequest('enupal-stripe/orders/refund-payment', data, function(response, textStatus) {
                that.$refundButton.removeClass('disabled').siblings('.spinner').addClass('hidden');
                if (textStatus === 'success') {
                    if ("error" in response ){
                        Craft.cp.displayError(Craft.t('enupal-stripe', response.error));
                        location.reload();
                    }
                    else if (response.success) {
                        Craft.cp.displayNotice(Craft.t('enupal-stripe', 'Payment Refunded - Check your messages tab'));
                        location.reload();
                    }else{
                        Craft.cp.displayError(Craft.t('enupal-stripe', 'Unable to refund payment - Check your messages tab'));
                        location.reload();
                    }
                }
            });
        },
    });

    window.EnupalStripeEditOrder = EnupalStripeEditOrder;

})(jQuery);