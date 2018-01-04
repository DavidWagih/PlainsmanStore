(function ($) {
    'use strict';

    let scrollHeight;

    function refresh() {
        $('.item span').removeClass('hidden');
        $('.masonry').masonry({
            columnWidth: '.grid-sizer',
            gutter: '.gutter-sizer',
            itemSelector: '.item'
        });
    }

    $("img.lazy").lazyload({
        effect : "fadeIn"
    });
    
    $('.masonry').imagesLoaded(refresh);

    setInterval(refresh, 1000);

    // $('img.lazy').load(function() {
    //     if (scrollHeight !== $(window).scrollTop()) {
    //         scrollHeight = $(window).scrollTop();
    //         $('.masonry').imagesLoaded(function(){
    //                 $('.item span').removeClass('hidden');
    //                 $('.masonry').masonry({
    //                     columnWidth: '.grid-sizer',
    //                     gutter: '.gutter-sizer',
    //                     itemSelector: '.item'
    //                 });
    //         });
    //     }
    // });
    
}(jQuery));
