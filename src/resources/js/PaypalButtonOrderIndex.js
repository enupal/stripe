if (typeof Craft.PaypalButton === typeof undefined) {
    Craft.PaypalButton = {};
}

/**
 * Class Craft.StripeButton.OrderIndex
 */
Craft.PaypalButton.OrderIndex = Craft.BaseElementIndex.extend({
    getViewClass: function(mode) {
        switch (mode) {
            case 'table':
                return Craft.PaypalButton.OrderTableView;
            default:
                return this.base(mode);
        }
    }
});

// Register the Paypal order index class
Craft.registerElementIndexClass('enupal\\paypal\\elements\\Order', Craft.PaypalButton.OrderIndex);
