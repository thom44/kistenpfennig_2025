(function($, Drupal) {
  Drupal.behaviors.wlw_sepa_payment = {
      attach: function (context, settings) {
          /* Mask definition for IBAN field using jquery.maskedinput.min.js */
        $('input[name="payment_information[add_payment_method][payment_details][sepa_payment_iban]"]').on('focus', function() {
          $(this).mask("aa99-?9999-9999-9999-9999-99");
        });

      }
  }
})(jQuery, Drupal);
