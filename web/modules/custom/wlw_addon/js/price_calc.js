(function($, Drupal) {
    Drupal.behaviors.wlw_product_checkbox = {
        attach: function (context, settings) {
            /* Event on option change */
            $("#wlw-addon-product-add-to-cart-form .wlw-checkbox-products").change(function () {
                   calculate_price();
            });
        }
    };

    function price_formatter(price) {
        /* Force 2 digits */
        price = parseFloat(Math.round(price * 100) / 100).toFixed(2);
        price = price.toString();
        price = price.replace(".",",");
        return '<span class="price-value">' + price + ' €</span><span class="price-postfix"> inkl. MwSt</span>';
    }

    /* Calculate total from all checked products */
    function calculate_price() {
        var total = 0;
        var tax_rate = '';
        var markup = '';

        /* class = wlw-product-checkbox-price */
        $("#wlw-addon-product-add-to-cart-form .wlw-checkbox-products").each(function( index ) {
            var variation_id = $(this).attr('name');
            var id = $(this).attr('id');

            if ($("#"+id).is(":checked")) {
                var price = $('#wlw-addon-product-add-to-cart-form input.product-variation-price-value[name="price_' + variation_id + '"]').val();
                var priceFloat = parseFloat(price);
                if (priceFloat > 0) {
                    total = total + priceFloat;
                }
            }
        });

        markup += price_formatter(total);

        /* Displays price calculation */
        $("#jquery-calculated-price").html(markup);
    }

})(jQuery, Drupal);