tl(entryData.jsPathUrl + '/jquery.lazy.min.js', function(){
    $('.w-image').each(function(){
        var widgetId = $(this).attr('id').substring(4);
        if (!isElementExist(['widgets', widgetId, 'lazyLoad'], entryData)) {
            return ''
        }
        $('img').each(function(){
            var $img = $(this);
            $img.lazy({
                afterLoad: function(){
                    $img.removeClass('na-banner');
                }
            })
        })
    })
});
