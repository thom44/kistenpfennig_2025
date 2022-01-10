(function($, Drupal) {
    Drupal.behaviors.wlw_checkout = {
        attach: function (context, settings) {
            /* Event on option change */
            $("#wlw-billing-to-contact-address").on("click", function () {
                transfer_data();
            });
        }
    };

    function transfer_data() {

        // Copyies salutation.
        // Gets value from biling information add form.
        var add_salutation = $("select[name~='payment_information[add_payment_method][billing_information][field_salutation]']").val();
        // Gets value form billing information edit form.
        var salutation = $("select[name~='payment_information[billing_information][field_salutation]']").val();
        if (salutation) {
            $("select[name~='wlw_contact_address_checkout_pane[contact_profile][field_salutation]']").val(salutation);
        } else if(add_salutation) {
            //alert('add ' + add_salutation);
            $("select[name~='wlw_contact_address_checkout_pane[contact_profile][field_salutation]']").val(add_salutation);
        } else {

            // Gets salutation name from closed form.
            var closed_salutation_name = $("#edit-payment-information .field--name-field-salutation").html();

            if (closed_salutation_name) {
                var salutation_options = [];

                $("select[name~='wlw_contact_address_checkout_pane[contact_profile][field_salutation]']").find('option').each(function() {
                    var option_key = $(this).val();
                    var option_label = $(this).html();
                    salutation_options[option_label] = option_key;

                });

                $("select[name~='wlw_contact_address_checkout_pane[contact_profile][field_salutation]']").val(salutation_options[closed_salutation_name]);
            }
        }

        // Copyies given name.
        var add_given_name = $("input[name~='payment_information[add_payment_method][billing_information][address][0][address][given_name]']").val();
        if (add_given_name) {
            $("input[name~='wlw_contact_address_checkout_pane[contact_profile][address][0][address][given_name]']").val(add_given_name);
        }
        var given_name = $("input[name~='payment_information[billing_information][address][0][address][given_name]']").val();
        if (given_name) {
            $("input[name~='wlw_contact_address_checkout_pane[contact_profile][address][0][address][given_name]']").val(given_name);
        }
        var closed_given_name = $("#edit-payment-information .address .given-name").html();
        if (closed_given_name) {
            $("input[name~='wlw_contact_address_checkout_pane[contact_profile][address][0][address][given_name]']").val(closed_given_name);
        }


        // Copyies family name.
        var add_family_name = $("input[name~='payment_information[add_payment_method][billing_information][address][0][address][family_name]']").val();
        if (add_family_name) {
            $("input[name~='wlw_contact_address_checkout_pane[contact_profile][address][0][address][family_name]']").val(add_family_name);
        }
        var family_name = $("input[name~='payment_information[billing_information][address][0][address][family_name]']").val();
        if (family_name) {
            $("input[name~='wlw_contact_address_checkout_pane[contact_profile][address][0][address][family_name]']").val(family_name);
        }
        //
        var closed_family_name = $("#edit-payment-information .address .family-name").html();
        if (closed_family_name) {
            $("input[name~='wlw_contact_address_checkout_pane[contact_profile][address][0][address][family_name]']").val(closed_family_name);
        }

        // Copyies address line 1.
        var add_address_1 = $("input[name~='payment_information[add_payment_method][billing_information][address][0][address][address_line1]']").val();
        if (add_address_1) {
            $("input[name~='wlw_contact_address_checkout_pane[contact_profile][address][0][address][address_line1]']").val(add_address_1);
        }
        var address_1 = $("input[name~='payment_information[billing_information][address][0][address][address_line1]']").val();
        if (address_1) {
            $("input[name~='wlw_contact_address_checkout_pane[contact_profile][address][0][address][address_line1]']").val(address_1);
        }
        var closed_address_1 = $("#edit-payment-information .address .address-line1").html();
        if (closed_address_1) {
            $("input[name~='wlw_contact_address_checkout_pane[contact_profile][address][0][address][address_line1]']").val(closed_address_1);
        }


        // Copyies address line 2.
        var add_address_2 = $("input[name~='payment_information[add_payment_method][billing_information][address][0][address][address_line2]']").val();
        if (add_address_2) {
            $("input[name~='wlw_contact_address_checkout_pane[contact_profile][address][0][address][address_line2]']").val(add_address_2);
        }
        var address_2 = $("input[name~='payment_information[billing_information][address][0][address][address_line2]']").val();
        if (address_2) {
            $("input[name~='wlw_contact_address_checkout_pane[contact_profile][address][0][address][address_line2]']").val(address_2);
        }
        var closed_address_2 = $("#edit-payment-information .address .address-line2").html();
        if (closed_address_2) {
            $("input[name~='wlw_contact_address_checkout_pane[contact_profile][address][0][address][address_line2]']").val(closed_address_2);
        }

        // Copyies postal code.
        var add_postal_code = $("input[name~='payment_information[add_payment_method][billing_information][address][0][address][postal_code]']").val();
        if (add_postal_code) {
            $("input[name~='wlw_contact_address_checkout_pane[contact_profile][address][0][address][postal_code]']").val(add_postal_code);
        }
        var postal_code = $("input[name~='payment_information[billing_information][address][0][address][postal_code]']").val();
        if (postal_code) {
            $("input[name~='wlw_contact_address_checkout_pane[contact_profile][address][0][address][postal_code]']").val(postal_code);
        }
        var closed_postal_code = $("#edit-payment-information .address .postal-code").html();
        if (closed_postal_code) {
            $("input[name~='wlw_contact_address_checkout_pane[contact_profile][address][0][address][postal_code]']").val(closed_postal_code);
        }


        // Copyies city.
        var add_city = $("input[name~='payment_information[add_payment_method][billing_information][address][0][address][locality]']").val();
        if (add_city) {
            $("input[name~='wlw_contact_address_checkout_pane[contact_profile][address][0][address][locality]']").val(add_city);
        }
        var city = $("input[name~='payment_information[billing_information][address][0][address][locality]").val();
        if (city) {
            $("input[name~='wlw_contact_address_checkout_pane[contact_profile][address][0][address][locality]']").val(city);
        }
        var closed_city = $("#edit-payment-information .address .locality").html();
        if (closed_city) {
            $("input[name~='wlw_contact_address_checkout_pane[contact_profile][address][0][address][locality]']").val(closed_city);
        }

      // Copyies fon code.
      var add_fon_code = $("input[name~='payment_information[billing_information][field_fon][0][value]']").val();
      if (add_fon_code) {
        $("input[name~='wlw_contact_address_checkout_pane[contact_profile][field_fon][0][value]']").val(add_fon_code);
      }
      var fon_code = $("input[name~='payment_information[billing_information][field_fon][0][value]']").val();
      if (fon_code) {
        $("input[name~='wlw_contact_address_checkout_pane[contact_profile][field_fon][0][value]']").val(fon_code);
      }
      var closed_fon_code = $("#edit-payment-information .field--name-field-fon .field__item").html();
      if (closed_fon_code) {
        $("input[name~='wlw_contact_address_checkout_pane[contact_profile][field_fon][0][value]']").val(closed_fon_code);
      }
    }

})(jQuery, Drupal);
