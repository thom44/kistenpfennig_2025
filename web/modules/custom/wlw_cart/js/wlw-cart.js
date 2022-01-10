(function ($, Drupal, drupalSettings) {
    Drupal.behaviors.WlwCartBehavior = {
        attach: function (context, settings) {

            /*
             * Change quantity field of order_item_types.
             * @note: Classes comes from view commerce_cart_form.
             */
            $( ".order-item-type_default :input" ).each( refactorInputToDiv );
            $( ".order-item-type_course :input" ).each( refactorInputToDiv );


            /*
             * Submits the cart form on change of any quantity field.
             */
            $('input[id^=edit-edit-quantity]').on('change', function() {
              $(this).parents('form:first').submit();
            });
        }
    };

    function refactorInputToDiv() {

        var NewElement = $("<div />");

        $.each(this.attributes, function(i, attrib){
            $(NewElement).attr(attrib.name, attrib.value);
        });
        // The value attribute of the input element.
        var value = $(this).attr('value');

        // Replace the input element with a div and loads the attributes.
        $(this).replaceWith(function () {
            return $(NewElement).append($(this).contents()).html(value);
        });

    }

})(jQuery, Drupal, drupalSettings);
