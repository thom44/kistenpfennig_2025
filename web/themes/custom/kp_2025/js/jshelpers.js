
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

})(jQuery);
