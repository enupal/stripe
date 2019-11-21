const mix = require('laravel-mix');

mix

// Forms
  // Front-end
  .js([
    'src/resources/stripe/src/js/enupal-stripe.js',
  ], 'src/resources/stripe/dist/js/enupal-stripe.min.js')
  .js([
    'src/resources/stripe/src/js/enupal-stripe-elements.js',
  ], 'src/resources/stripe/dist/js/enupal-stripe-elements.min.js');