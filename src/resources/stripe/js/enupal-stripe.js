(function($) {
    $(document).ready(function(){
        $('.enupal-stripe-form').each(function(){
            var handler = StripeCheckout.configure({
                key: $(this).find('[name="publishableKey"]').val(),
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
    });
})(jQuery);