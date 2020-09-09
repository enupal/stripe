(function($) {
    /**
     * EnupalStripeConnectSettings class
     */
    var EnupalStripeConnectSettings = Garnish.Base.extend({

        $syncVendorsButton: null,

        /**
         * The constructor.
         */
        init: function() {
            // init method
            this.$syncVendorsButton = $('#settings-sync-vendors-btn');

            this.addListener(this.$syncVendorsButton, 'click', 'handleSyncVendors');
        },

        handleSyncVendors: function(option) {
            if (this.$syncVendorsButton.hasClass('disabled')) {
                return;
            }

            var userGroup = $("#settings-vendorUserGroupId").val();
            var lightSwitch = $("#settings-vendorUserFieldId").val();

            if (!userGroup && !lightSwitch) {
                Craft.cp.displayError(Craft.t('enupal-stripe', "Please select at least a Lightswitch User field or User Group"));
                return false;
            }

            var that = this;

            this.$syncVendorsButton.addClass('disabled').siblings('.spinner').removeClass('hidden');

            Craft.postActionRequest('enupal-stripe/vendors/sync-vendors', {}, function(response, textStatus) {
                that.$syncVendorsButton.removeClass('disabled').siblings('.spinner').addClass('hidden');
                if (textStatus === 'success') {
                    if ("error" in response ){
                        Craft.cp.displayError(Craft.t('enupal-stripe', 'Please select at least a Lightswitch User field or User Group and save your settings'));
                    }
                    else if (response.success === true) {
                        Craft.cp.displayNotice(Craft.t('enupal-stripe', 'Vendor Syncs job was added to the queue'))
                    }else{
                        Craft.cp.displayError(Craft.t('enupal-stripe', 'Something went wrong'));
                    }
                }
            });
        },
    });

    window.EnupalStripeConnectSettings = EnupalStripeConnectSettings;

})(jQuery);