if (typeof Craft.StripeButton === typeof undefined) {
    Craft.StripeButton = {};
}

/**
 * Class Craft.PaymentForm.OrderIndex
 */
Craft.StripeButton.OrderIndex = Craft.BaseElementIndex.extend({
    getViewClass: function(mode) {
        switch (mode) {
            case 'table':
                return Craft.StripeButton.OrderTableView;
            default:
                return this.base(mode);
        }
    }
});

// Register the Stripe order index class
Craft.registerElementIndexClass('enupal\\stripe\\elements\\Order', Craft.StripeButton.OrderIndex);
