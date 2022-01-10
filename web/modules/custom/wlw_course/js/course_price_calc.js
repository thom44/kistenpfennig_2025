(function($, Drupal) {
    Drupal.behaviors.wlw_course = {
        attach: function (context, settings) {

            // @todo: get unique form id to get it work with multible forms on a page.
            $("[id^='wlw-course-add-to-cart-form']").each(function () {
              var formId = $(this).attr('id');

              /* On page load process all calculations. */
              /* First process display to calculate only displayed fields. */
              control_display(formId);
              calculate_price(formId);

            });


            /* Event on option change */
            //$("#wlw-course-add-to-cart-form .wlw-checkbox-products").change(function () {
            $("[id^='wlw-course-add-to-cart-form'] .wlw-checkbox-products").change(function () {

               var formId = $(this).closest("form").attr('id');

               control_display(formId);
               calculate_price(formId);

            });


        }
    };

    function price_formatter(price) {
        /* Force 2 digits */
        price = parseFloat(Math.round(price * 100) / 100).toFixed(2);
        price = price.toString();
        price = price.replace(".",",");
        return '<span class="price-value">' + price + ' €</span>';
        /* Tax info not necessary for courses <span class="price-postfix">inkl. MwSt</span> */
    }

    function add_price(total, variation_id, formId) {
        var price_field = $('#' + formId + ' input.product-variation-price-value[name="price_' + variation_id + '"]');
        /* Checks if the field is displayed in the moment of calculation. */
        if (price_field.hasClass("count-price")) {
            var price = price_field.val();
            var priceFloat = parseFloat(price);
            if (priceFloat > 0) {
                return total + priceFloat;
            }
        }

        return total;
    }

    /* Calculate total from all checked products */
    function calculate_price(formId) {
        var total = 0;
        var tax_rate = '';
        var markup = '';

        /* Calculates default variations */
        $("#" + formId + " .product-variation-default-value").each(function( index ) {
            var variation_id = $(this).attr('name');

            total = add_price(total, variation_id, formId);
        });

        /* Calculates options */
        $("#" + formId + " .wlw-checkbox-products").each(function( index ) {
            var variation_id = $(this).attr('name');
            var id = $(this).attr('id');

            if ($("#"+id).is(":checked")) {
               total = add_price(total, 'add_' + variation_id, formId);
            }
        });

        markup += price_formatter(total);

        // Gets instance id from the form.
        var instance = $("#" + formId + " .form-instance").attr('value');
        /* Displays price calculation */
        $("#jquery-calculated-price-" + instance).html(markup);
    }

    /* Control's the display of variations according to the display classes. */
    function control_display(formId) {
        var i;
        var id;
        for (i = 1; i < 4; i++) {
            /* Checks if a field with depending fields is checked or unchecked. */
            if ($("#" + formId + " .display_option_" + i).is(":checked")) {

                /* Show all depending fields and adds it to the price calculation. */
               $("#" + formId + " .display_" + i).each(function( index ) {
                   id = $(this).attr("id");

                   if (id != undefined) {
                       /* Displays input field and label */
                       $('#' + id).show();
                       $("label[for=" + id + "]").show();

                       /* Adds a class to the hidden price field to be calculated */
                       name_attr = $(this).attr("name");
                       $("input[name=price_add_" + name_attr + "]").addClass("count-price");
                   }
                });

            } else {

                /* Hide all depending fields and removes it to the price calculation. */
                $("#" + formId + " .display_" + i).each(function( index ) {
                    id = $(this).attr("id");

                    if (id != undefined) {
                        /* Hides input field */
                        $('#' + id).hide();
                        /* Hides label */
                        $("label[for=" + id + "]").hide();

                        /*
                         * Unchecks it if it is invisible. We do not
                         * want add invisible variations to the cart.
                         */
                        $('#' + id).prop( "checked", false );

                        /* Removes a class from the hidden price field to be not calculated */
                        name_attr = $(this).attr("name");
                        $("input[name=price_add_" + name_attr + "]").removeClass("count-price");
                    }
                });
            }
        }
    }

})(jQuery, Drupal);
