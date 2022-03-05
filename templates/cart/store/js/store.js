$(function () {
    // Let's update the cart on page refresh
    getCart();

    // EVENTS
    // Handles Cart clear event
    $('button[data-cart-clear=""]').on("click", function(e, res){
        clearCart();
    });
    // Handles Cart Checkout event
    $('button[data-cart-checkout=""]').on("click", function(e, res){
        checkoutCart();
    });
    // Handles Cart updates event
    $("#enupal-stripe-cart").on("on-add-to-cart", function(e, res){
        $("body").find("[data-cart-render='item_count']").html(res.item_count);
        $("body").find("[data-cart-render='total_price_money_with_currency']").html(res.total_price_with_currency);
    });

    // Handles add to cart event
    $('button[data-cart-add]').on("click", function(e, res){
        var priceId = $(this).data('cart-add');
        var quantity = $(this).data('cart-quantity');

        var lineItem = {
            "price": priceId,
            "quantity": parseInt(quantity),
            // Optionally
            "adjustable_quantity": {
                "enabled": true
            }
        }

        var cartData = {
            // Pass metadata if needed
            //"metadata": {
            //    "is_gift": "yes"
            //},
            "items": [
                lineItem
            ]
        }

        addCart(cartData);
    });

    // Handles update to cart event
    $('form[data-cart-submit="data-cart-submit"]').submit(function(e) {
        e.preventDefault();
        var $form = $(this);
        var data = $form.serializeArray().reduce(function(obj, item) {
            obj[item.name] = item.value;
            return obj;
        }, {});

        var lineItem = {
            "quantity": parseInt(data.quantity),
            // Optionally
            "adjustable_quantity": {
                "enabled": true
            }
        }

        if (Boolean(data.custom_label)) {
            lineItem['description'] = data.custom_label;
        }

        var cartData = {
            // Pass metadata if needed
            //"metadata": {
            //    "is_gift": "yes"
            //},
            "updates": {
                [data.id]: lineItem
            }
        }

        updateCart(cartData);
    });

    function showLoading() {
        $("#cart-visible-loading").removeClass('hidden');
    }

    function hideLoading() {
        $("#cart-visible-loading").addClass('hidden');
    }

    function getCart(data){
        showLoading();
        $.ajax({
            type:"GET",
            url:"/enupal-stripe/cart",
            dataType : 'json',
            success: function(response) {
                $("#enupal-stripe-cart").trigger("on-add-to-cart", [response]);
                hideLoading();
            }.bind(this),
            error: function(xhr, status, err) {
                console.error(xhr, status, err.toString());
                hideLoading();
            }.bind(this)
        });
    }

    function addCart(data){
        showLoading();
        $.ajax({
            type:"POST",
            url:"/enupal-stripe/cart/add",
            data: data,
            dataType : 'json',
            success: function(response) {
                $("#enupal-stripe-cart").trigger("on-add-to-cart", [response]);
                hideLoading();
            }.bind(this),
            error: function(xhr, status, err) {
                console.error(xhr, status, err.toString());
                hideLoading();
            }.bind(this)
        });
    }

    function updateCart(data){
        showLoading();
        $.ajax({
            type:"POST",
            url:"/enupal-stripe/cart/update",
            data: data,
            dataType : 'json',
            success: function(response) {
                $("#enupal-stripe-cart").trigger("on-add-to-cart", [response]);
                hideLoading();
            }.bind(this),
            error: function(xhr, status, err) {
                console.error(xhr, status, err.toString());
                hideLoading();
            }.bind(this)
        });
    }

    function clearCart(){
        showLoading();
        $.ajax({
            type:"POST",
            url:"/enupal-stripe/cart/clear",
            data: null,
            dataType : 'json',
            success: function(response) {
                $("#enupal-stripe-cart").trigger("on-add-to-cart", [response]);
                hideLoading();
            }.bind(this),
            error: function(xhr, status, err) {
                console.error(xhr, status, err.toString());
                hideLoading();
            }.bind(this)
        });
    }

    function checkoutCart(){
        showLoading();
        $.ajax({
            type:"POST",
            url:"/enupal-stripe/cart/checkout",
            data: null,
            dataType : 'json',
            success: function(response) {
                window.location.replace(response.url);
                hideLoading();
            }.bind(this),
            error: function(xhr, status, err) {
                console.error(xhr, status, err.toString());
                hideLoading();
            }.bind(this)
        });
    }
});