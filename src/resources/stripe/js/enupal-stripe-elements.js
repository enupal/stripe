/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

var enupalStripe = {};

(function($) {
    'use strict';
    enupalStripe = {

        stripe: null,
        paymentFormsList: {},
        finalData: {},
        zeroDecimals: {},

        init: function() {
            this.paymentFormsList = $('.enupal-stripe-form');

            this.zeroDecimals = ['MGA', 'BIF', 'CLP', 'PYG', 'DJF', 'RWF', 'GNF', 'UGX', 'JPY', 'VND', 'VUV', 'XAF', 'KMF', 'KRW', 'XOF', 'XPF'];

            this.paymentFormsList.each(function() {
                var enupalButtonElement = $(this);
                enupalStripe.initializeForm(enupalButtonElement);
            });

        },

        initializeForm: function(enupalButtonElement) {
            if (typeof $(enupalButtonElement).find('[name="enupalStripe[stripeData]"]').val() === 'undefined'){
                return false;
            }
            // get the form ID
            var enupalStripeData = $.parseJSON($(enupalButtonElement).find('[name="enupalStripe[stripeData]"]').val());

            // reset our values
            $(enupalButtonElement).find('[name="enupalStripe[stripeData]"]').val('');

            this.finalData.finalAmount = enupalStripeData.stripe.amount;

            // Create a Stripe client.
            var stripe = Stripe(enupalStripeData.pbk);

            // Create an instance of Elements.
            var elements = stripe.elements();

            // Custom styling can be passed to options when creating an Element.
            // (Note that this demo uses a wider set of styles than the guide below.)
            var style = {
                base: {
                    color: '#32325d',
                    lineHeight: '18px',
                    fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                    fontSmoothing: 'antialiased',
                    fontSize: '16px',
                    '::placeholder': {
                        color: '#aab7c4'
                    }
                },
                invalid: {
                    color: '#fa755a',
                    iconColor: '#fa755a'
                }
            };

            // Create an instance of the card Element.
            var card = elements.create('card', {style: style});

            // Add an instance of the card Element into the `card-element` <div>.
            card.mount('#card-element-'+enupalStripeData.paymentFormId);

            // Handle real-time validation errors from the card Element.
            card.addEventListener('change', function(event) {
                var displayError = document.getElementById('card-errors-'+enupalStripeData.paymentFormId);

                if (event.error) {
                    displayError.textContent = event.error.message;
                } else {
                    displayError.textContent = '';
                }
            });

            // Handle form submission.
            enupalButtonElement[0].addEventListener('submit', function(event) {
                event.preventDefault();

                stripe.createToken(card).then(function(result) {
                    if (result.error) {
                        // Inform the user if there was an error.
                        var errorElement = document.getElementById('card-errors-'+enupalStripeData.paymentFormId);
                        errorElement.textContent = result.error.message;
                    } else {
                        // Send the token to your server.
                        console.log(result.token);
                    }
                });
            });
        }
    };

    $(document).ready(function($) {
        enupalStripe.init();
    });
})(jQuery);