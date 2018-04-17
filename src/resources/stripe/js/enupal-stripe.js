var enupalStripe = {};

(function($) {
    enupalStripe = {
        // All Enupal Stripe buttons
        buttonsList: {},
        finalData: {},

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

            var enupalStripeData = $.parseJSON($(enupalButtonElement).find('[name="enupalStripeData"]').val());

            this.finalData.finalAmount = enupalStripeData.amount;

            //  Stripe config
            var stripeHandler = null;

            // Stripe Checkout handler configuration.
            // Docs: https://stripe.com/docs/checkout#integration-custom
            stripeHandler = StripeCheckout.configure( {
                key: enupalStripeData.stripe.pbk,
                token: processStripeToken,
                opened: function() {
                },
                closed: function() {
                }
            } );

            // Callback function to handle StripeCheckout.configure
            function processStripeToken( token, args ) {
                // At this point the Stripe Checkout overlay is validated and submitted.
                // Set values to hidden elements to pass via POST when submitting the form for payment.
                enupalButtonElement.find( '.enupal-stripe-field-token' ).val( token.id );
                enupalButtonElement.find( '.enupal-stripe-field-email' ).val( token.email );

                // Handle args
                enupalStripe.handleStripeArgs( enupalButtonElement, args );

                // Disable pay button and show a nice UI message
                enupalButtonElement.find( '.enupal-stripe-button' )
                    .prop( 'disabled', true )
                    .find( 'span' )
                    .text( enupalStripeData.paymentButtonProcessingText );

                // Unbind original form submit trigger before calling again to "reset" it and submit normally.
                enupalButtonElement.unbind( 'submit', [ enupalButtonElement, enupalStripeData ] );

                enupalButtonElement.submit();
            }

            // Pay Button clicked
            enupalButtonElement.find( '.enupal-stripe-button' ).on( 'click', function( e ) {
                e.preventDefault();

                enupalStripe.submitPayment( enupalButtonElement, enupalStripeData, stripeHandler );
            } );
        },

        submitPayment: function( enupalButtonElement, enupalStripeData, stripeHandler ) {

            enupalStripe.setFinalAmount( enupalButtonElement, enupalStripeData );

            // Add in the final amount to Stripe params
            enupalStripeData.amount = parseInt( enupalStripeData.finalAmount );

            // If everything checks out then let's open the form
            stripeHandler.open({
                name: enupalStripeData.companyName,
                image: enupalStripeData.logoImage,
                description: enupalStripeData.name,
                locale: enupalStripeData.language,
                currency: enupalStripeData.currency,
                shippingAddress: enupalStripeData.enableShippingAddress,
                billingAddress: enupalStripeData.enableBillingAddress,
                zipCode :true,
                allowRememberMe: true,
                amount: this.convertToCents(this.finalData.finalAmount)
            });
        },

        convertToCents: function( amount ) {
            return ( amount * 100 );
        },

        // Run this to process and get the final amount when the payment button is clicked
        setFinalAmount: function( enupalButtonElement, enupalStripeData ) {

            this.finalData.finalAmount = enupalStripeData.amount;

            // Update hidden amount field for processing
            enupalButtonElement.find( '.enupal-stripe-field-amount' ).val( this.finalData.finalAmount );

        }
    };

    $( document ).ready( function( $ ) {
        enupalStripe.init();
    } );
})(jQuery);