const mix = require('laravel-mix');
/**
 * @param {Object} mix
 * @param {method} mix.sass
 */
mix
// Forms
// Front-end
.js([
    'src/resources/stripe/src/js/enupal-stripe.js',
], 'src/resources/stripe/dist/js/enupal-stripe.min.js')
.sass(
   'src/resources/stripe/src/css/enupal-button.scss'
, 'src/resources/stripe/dist/css/enupal-button.min.css')
.sass(
 'src/resources/stripe/src/css/enupal-stripe-elements.scss',
 'src/resources/stripe/dist/css/enupal-stripe-elements.min.css')
.js([
'src/resources/stripe/src/js/enupal-stripe-elements.js',
], 'src/resources/stripe/dist/js/enupal-stripe-elements.min.js');