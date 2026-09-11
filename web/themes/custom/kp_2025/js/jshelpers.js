
(function ($) {

// alert("Krass!");

$('#ig-load').click(function () {

    // Elfsight-Widget dynamisch einfügen
    var embedHtml = '<div class="elfsight-app-ca88ea31-1f52-4f4e-b051-0282a1cb4f50" data-elfsight-app-lazy></div>';
    $('#ig-feed').html(embedHtml);

    // Elfsight-Script nur einmal laden
    if ($('#elfsight-platform').length === 0) {
        var s = document.createElement('script');
        s.id = 'elfsight-platform';
        s.src = 'https://elfsightcdn.com/platform.js';
        s.async = true;
        document.body.appendChild(s);
    }

    // Platzhalter ausblenden, Feed anzeigen
    $('#ig-placeholder').hide();
    $('#ig-feed').show();

});

(function ($, Drupal, once) {
  Drupal.behaviors.productDetails = {
    attach: function (context) {
      $(once('product-details', '.product-details-button', context)).on('click', function () {
        $(this)
          .closest('.product-teaser')
          .toggleClass('details-open');
      });
    }
  }
})(jQuery, Drupal, once);


(function ($, Drupal, once) {
  Drupal.behaviors.openingHours = {
    attach: function (context) {
      $(once('opening-hours', '.view-oeffnungszeiten h3', context)).on('click', function () {
        var content = $(this)
          .next('.views-row')
          .find('.opening-hours-content');
          $(this).toggleClass('open');
        if (content.is(':visible')) {
          content.slideUp(250, function () {
            content.removeClass('is-open');
          });
        } else {
          content.addClass('is-open');
          content.hide().slideDown(250);
        }
      });
    }
  };
})(jQuery, Drupal, once);

// der Input Feld Code für Produkt/Warenkorb
(function ($) {

  // Quantity Buttons
  Drupal.behaviors.quantityButtons = {
    attach: function (context) {

      $('#edit-quantity-0-value', context).each(function () {
        const $input = $(this);

        // Verhindert, dass die Buttons mehrfach erzeugt werden.
        if ($input.parent().hasClass('quantity-controls')) {
          return;
        }

        const $minus = $('<button>', {
          type: 'button',
          class: 'quantity-minus',
          text: '−',
          'aria-label': 'Anzahl verringern'
        });

        const $plus = $('<button>', {
          type: 'button',
          class: 'quantity-plus',
          text: '+',
          'aria-label': 'Anzahl erhöhen'
        });

        const $controls = $('<div>', {
          class: 'quantity-controls'
        });

        // Input in unseren Wrapper packen.
        $input.wrap($controls);

        const $wrapper = $input.parent();

        // Buttons links und rechts vom Input einfügen.
        $wrapper.prepend($minus);
        $wrapper.append($plus);

        // Minus
        $minus.on('click', function () {
          const min = parseFloat($input.attr('min')) || 1;
          const step = parseFloat($input.attr('step')) || 1;
          const value = parseFloat($input.val()) || min;

          $input
            .val(Math.max(min, value - step))
            .trigger('change');
        });

        // Plus
        $plus.on('click', function () {
          const step = parseFloat($input.attr('step')) || 1;
          const value = parseFloat($input.val()) || 0;
          const max = $input.attr('max');

          let newValue = value + step;

          if (max !== undefined && newValue > parseFloat(max)) {
            newValue = parseFloat(max);
          }

          $input
            .val(newValue)
            .trigger('change');
        });

      });

    }
  };

})(jQuery);
// Ende des Input Felds Code

})(jQuery);
