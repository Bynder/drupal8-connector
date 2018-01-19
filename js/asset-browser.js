(function ($, Drupal) {

    Drupal.behaviors.webdamAssetBrowser = {
        attach: function () {
            // Resize the asset browser frame.
            $(".webdam-asset-browser").height($(window).height() - $(".filter-sort-container").height() - 175);
            $(window).on('resize',function(){
              $(".webdam-asset-browser").height($(window).height() - $(".filter-sort-container").height() - 175);
            });
        }
    };

})(jQuery, Drupal);
