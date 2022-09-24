(function ($) {
  Drupal.behaviors.justified2 = {
    attach: function(context, settings) {

  $('.field--name-field-gallery').justifiedGallery({
    rowHeight : 320,
    maxRowHeight : 320,
    lastRow : 'justify',
    margins : 3
  });

    }
  }
})(jQuery);
