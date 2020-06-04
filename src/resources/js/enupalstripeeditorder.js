(function($) {
    /**
     * EnupalStripeEditOrder class
     */
    var EnupalStripeEditOrder = Garnish.Base.extend({

        $refreshSubscriptionButton: null,
        $cancelSubscriptionButton: null,
        $reactivateSubscriptionButtonWrapper: null,
        $reactivateSubscriptionButton: null,
        $refundButton: null,
        $captureButton: null,

        /**
         * The constructor.
         */
        init: function(isSubscription) {
            // init method
            if (isSubscription){
                this.$refreshSubscriptionButton = $('#refresh-subscription');
                this.$cancelSubscriptionButton = $('#cancel-subscription');
                this.$reactivateSubscriptionButtonWrapper = $('#subs-reactivate');
                this.$reactivateSubscriptionButton = $('#reactivate-subscription');
                this.addListener(this.$refreshSubscriptionButton, 'click', 'handleRefreshSubscription');
                this.addListener(this.$reactivateSubscriptionButton, 'click', 'handleReactivateSubscription');
                this.addListener(this.$cancelSubscriptionButton, 'click', 'handleCancelSubscription');
                this.handleRefreshSubscription();
            }

            this.$refundButton = $('#fields-refund-payment');
            this.addListener(this.$refundButton, 'click', 'handleRefundPayment');

            this.$captureButton = $('#fields-capture-payment');
            this.addListener(this.$captureButton, 'click', 'handleCapturePayment');
        },

        handleRefreshSubscription: function(option) {
            if (this.$refreshSubscriptionButton.hasClass('disabled')) {
                return;
            }

            var that = this;

            this.$refreshSubscriptionButton.addClass('disabled').siblings('.spinner').removeClass('enupal--hidden');

            var data = {
                'subscriptionId' : $("#subscriptionId").val()
            };
            that.$cancelSubscriptionButton.addClass('disabled');

            Craft.postActionRequest('enupal-stripe/subscriptions/refresh-subscription', data, function(response, textStatus) {
                that.$refreshSubscriptionButton.removeClass('disabled').siblings('.spinner').addClass('enupal--hidden');
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
                        if (!subscription.cancelAtPeriodEnd){
                            that.$reactivateSubscriptionButtonWrapper.addClass('enupal--hidden');
                            $("#cancel-title").text(Craft.t('enupal-stripe', ''));
                            $("#canceled-at").addClass("enupal--hidden");
                            $("#subs-cancel").removeClass("enupal--hidden");
                        }

                        if (subscription.canceledAt !== null){
                            $("#cancel-title").text(Craft.t('enupal-stripe', 'Canceled at'));
                            $("#canceled-at").removeClass("enupal--hidden");
                            $("#subs-cancel").addClass("enupal--hidden");
                            $("#canceled-at").text(subscription.canceledAt);
                            if (subscription.cancelAtPeriodEnd){
                                that.$reactivateSubscriptionButtonWrapper.removeClass('enupal--hidden');
                            }
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

            this.$cancelSubscriptionButton.addClass('disabled').siblings('.spinner').removeClass('enupal--hidden');

            var data = {
                'subscriptionId' : $("#subscriptionId").val()
            };

            Craft.postActionRequest('enupal-stripe/subscriptions/cancel-subscription', data, function(response, textStatus) {
                that.$cancelSubscriptionButton.removeClass('disabled').siblings('.spinner').addClass('enupal--hidden');
                if (textStatus === 'success') {
                    if ("error" in response ){
                        Craft.cp.displayError(Craft.t('enupal-stripe', response.error));
                    }
                    else if (response.success) {
                        Craft.cp.displayNotice(Craft.t('enupal-stripe', 'Subscription canceled'));
                        that.handleRefreshSubscription();
                    }else{
                        Craft.cp.displayError(Craft.t('enupal-stripe', 'Unable to reactivate subscription'));
                    }
                }
            });
        },

        handleReactivateSubscription: function(option) {
            if (!confirm(Craft.t('enupal-stripe', 'Are you sure you want to reactivate this subscription?'))) {
                return true;
            }

            if (this.$reactivateSubscriptionButton.hasClass('disabled')) {
                return;
            }

            var that = this;

            this.$reactivateSubscriptionButton.addClass('disabled').siblings('.spinner').removeClass('enupal--hidden');

            var data = {
                'subscriptionId' : $("#subscriptionId").val()
            };

            Craft.postActionRequest('enupal-stripe/subscriptions/reactivate-subscription', data, function(response, textStatus) {
                that.$reactivateSubscriptionButton.removeClass('disabled').siblings('.spinner').addClass('enupal--hidden');
                if (textStatus === 'success') {
                    if ("error" in response ){
                        Craft.cp.displayError(Craft.t('enupal-stripe', response.error));
                    }
                    else if (response.success) {
                        Craft.cp.displayNotice(Craft.t('enupal-stripe', 'Subscription reactivated'));
                        that.handleRefreshSubscription();
                    }else{
                        Craft.cp.displayError(Craft.t('enupal-stripe', 'Unable to reactive subscription'));
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

            this.$refundButton.addClass('disabled').siblings('.spinner').removeClass('enupal--hidden');

            var data = {
                'orderNumber' : $("#fields-order-number-short").text()
            };

            Craft.postActionRequest('enupal-stripe/orders/refund-payment', data, function(response, textStatus) {
                that.$refundButton.removeClass('disabled').siblings('.spinner').addClass('enupal--hidden');
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

        handleCapturePayment: function(option) {
            if (!confirm(Craft.t('enupal-stripe', 'Are you sure you want to capture this payment?'))) {
                return true;
            }

            if (this.$captureButton.hasClass('disabled')) {
                return;
            }

            var that = this;

            this.$captureButton.addClass('disabled').siblings('.spinner').removeClass('enupal--hidden');

            var data = {
                'orderNumber' : $("#fields-order-number-short").text()
            };

            Craft.postActionRequest('enupal-stripe/orders/capture-payment', data, function(response, textStatus) {
                that.$captureButton.removeClass('disabled').siblings('.spinner').addClass('enupal--hidden');
                if (textStatus === 'success') {
                    if ("error" in response ){
                        Craft.cp.displayError(Craft.t('enupal-stripe', response.error));
                        location.reload();
                    }
                    else if (response.success) {
                        Craft.cp.displayNotice(Craft.t('enupal-stripe', 'Payment Captured - Check your messages tab'));
                        location.reload();
                    }else{
                        Craft.cp.displayError(Craft.t('enupal-stripe', 'Unable to capture payment - Check your messages tab'));
                        location.reload();
                    }
                }
            });
        },
    });

    window.EnupalStripeEditOrder = EnupalStripeEditOrder;

})(jQuery);