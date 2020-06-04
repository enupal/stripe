(function($) {
    /**
     * EnupalStripeSettings class
     */
    var EnupalStripeSettings = Garnish.Base.extend({

        $testModeToggle: null,
        $testModeToggleField: null,

        /**
         * The constructor.
         */
        init: function() {
            // init method
            this.$testModeToggleField = $("#settings-testMode-field");
            this.$testModeToggle = $("input[name='settings[testMode]']");

            this.addListener(this.$testModeToggleField, 'change', 'handleTestModeToggle');

            this.handleTestModeToggle();
        },

        handleTestModeToggle: function()
        {
            var value = this.$testModeToggle.val();
            var testWrapper = $("#settings-testKeys");
            var liveWrapper = $("#settings-liveKeys");

            if (value == 1){
                testWrapper.removeClass('enupal--hidden');
                liveWrapper.addClass('enupal--hidden');
            }
            else{
                testWrapper.addClass('enupal--hidden');
                liveWrapper.removeClass('enupal--hidden');
            }
        },
    });

    window.EnupalStripeSettings = EnupalStripeSettings;

})(jQuery);