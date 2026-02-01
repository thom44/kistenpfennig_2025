
(function ($) {

// alert("Krass!");

$('#ig-load').click(function(){

    // Embed-Code dynamisch einfügen
    var embedHtml = '<div class="sk-instagram-feed" data-embed-id="25650054"></div>';
    $('#ig-feed').html(embedHtml);

    // Widget-Script nur einmal einfügen
    if ($('#sociablekit-widget').length === 0) {
        var s = document.createElement('script');
        s.id = 'sociablekit-widget';
        s.src = 'https://widgets.sociablekit.com/instagram-feed/widget.js';
        document.body.appendChild(s);
    }

    // Placeholder ausblenden, Feed anzeigen
    $('#ig-placeholder').hide();
    $('#ig-feed').show();
});

})(jQuery);
