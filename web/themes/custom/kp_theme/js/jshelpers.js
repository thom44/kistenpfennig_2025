
(function ($) {

// alert("Krass!");


  /*
       $('#showall').click(function(){
             $('.targetDiv').show();
      });
  */
      $('.showSingle').click(function(){
            $('.targetDiv').hide();
            $('#div'+$(this).attr('target')).show();
          });

})(jQuery);
