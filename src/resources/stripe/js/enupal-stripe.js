var enupalStripe = {};

(function($) {
    enupalStripe = {
        // All Enupal Stripe buttons
        buttonsList: {},

        init: function() {
            // Set main vars on init.
            body = $( document.body );

            this.buttonsList = $('.enupal-stripe-form');

            this.buttonsList.each( function() {
                var enupalButtonElement = $( this );
                enupalStripe.initializeForm( enupalButtonElement );
            } );
        },

        initializeForm: function( enupalButtonElement ) {
            // get the form ID
            var formId = enupalButtonElement.attr('id');

            vara $(enupalButtonElement).find('[name="enupalStripeData"]').val()

            // Grab the localized data for this form ID
            var enupalStripeData = enupalButtonElement.find(".enupal-stripe-data");

            // Set a local variable to hold all of the form information.
            var formData = this.spFormData[ formId ];

            // Variable to hold the Stripe configuration
            var stripeHandler = null;

            // Set formData array index of the current form ID to match the localized data passed over for form settings.
            formData = $.extend( {},  localizedFormData.form.integers, localizedFormData.form.bools, localizedFormData.form.strings );

            formData.formId = formId;

            // Set a finalAmount setting so that we can perform all the actions on this. That way if we need to reverse anything we leave the base amount untouched and can revert to it.
            formData.finalAmount = formData.amount;

            // Set the default quantity to 1
            formData.quantity = 1;

            // Add a new object called stripeParams to the spFormData object. This contains only the stripeParams that need to be sent. This is so we don't have to manually set all the stripeParams
            // And we can just use what was passed from PHP so we only include the minimum needed and let Stripe defaults take care of anything that's missing here.
            formData.stripeParams = $.extend( {}, localizedFormData.stripe.strings, localizedFormData.stripe.bools );

            // Set a fallback button label
            formData.oldPanelLabel = undefined !== formData.stripeParams.panelLabel ? formData.stripeParams.panelLabel : '';

            body.trigger( 'simpayFormVarsInitialized', [ enupalButtonElement, formData ] );

            // Stripe Checkout handler configuration.
            // Only token callback function set here. All other params set in stripeParams.
            // Chrome on iOS needs handler set before click event or else checkout won't open in a new tab.
            // See "How do I prevent the Checkout popup from being blocked?"
            // Full docs: https://stripe.com/docs/checkout#integration-custom
            stripeHandler = StripeCheckout.configure( {

                // Key param MUST be sent here instead of stripeHandler.open(). Discovered 8/11/16.
                key: formData.stripeParams.key,

                token: handleStripeToken,

                opened: function() {
                },
                closed: function() {
                }
            } );

            // Internal Strike token callback function for StripeCheckout.configure
            function handleStripeToken( token, args ) {

                // At this point the Stripe Checkout overlay is validated and submitted.
                // Set values to hidden elements to pass via POST when submitting the form for payment.
                enupalButtonElement.find( '.simpay-stripe-token' ).val( token.id );
                enupalButtonElement.find( '.simpay-stripe-email' ).val( token.email );

                // Handle args
                simpayApp.handleStripeArgs( enupalButtonElement, args );

                // Disable original payment button and change text for UI feedback while POST-ing to Stripe.
                enupalButtonElement.find( '.simpay-payment-btn' )
                    .prop( 'disabled', true )
                    .find( 'span' )
                    .text( formData.loadingText );

                // Unbind original form submit trigger before calling again to "reset" it and submit normally.
                enupalButtonElement.unbind( 'submit', [ enupalButtonElement, formData ] );

                enupalButtonElement.submit();
            }

            // Page-level initial payment button clicked. Use over form submit for more control/validation.
            enupalButtonElement.find( '.simpay-payment-btn' ).on( 'click.simpayPaymentBtn', function( e ) {
                e.preventDefault();

                // Trigger custom event right before executing payment
                enupalButtonElement.trigger( 'simpayBeforeStripePayment', [ enupalButtonElement, formData ] );

                simpayApp.submitPayment( enupalButtonElement, formData, stripeHandler );
            } );

            this.spFormData[ formId ] = formData;

            /** Event handlers for form elements **/

            body.trigger( 'simpayBindEventsAndTriggers', [ enupalButtonElement, formData ] );
        },

        $('.enupal-stripe-form').each(function() {
            var handler = StripeCheckout.configure({
                key: $(this).find('[name="enupalStripeData"]').val(),
                image: 'https://stripe.com/img/documentation/checkout/marketplace.png',
                locale: 'auto',
                token: function(token) {
                    // You can access the token ID with `token.id`.
                    // Get the token ID to your server-side code for use.
                }
            });

            $(this).on('click', function(e) {
                // Open Checkout with further options:
                handler.open({
                    name: 'Stripe.com',
                    description: '2 widgets',
                    zipCode: true,
                    amount: 2000
                });
                e.preventDefault();
            });

            // Close Checkout on page navigation:
            $(window).on('popstate', function() {
                handler.close();
            });
        });
    };
})(jQuery);