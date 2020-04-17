if (!ProductListing) var ProductListing = {};
ProductListing.applyItemImage = function($item, widgetId) {
    var productId = $item.data('id');
    tl(entryData.jsPathUrl + '/jquery.lazy.min.js', function(){
        $('.image img', $item).lazy({
            beforeLoad: function(){
                $('source', $item).each(function(){
                    let srcset = $(this).data('srcset');
                    $(this).attr('srcset', srcset).removeAttr('data-srcset')
                })
            }
        });
    })
}