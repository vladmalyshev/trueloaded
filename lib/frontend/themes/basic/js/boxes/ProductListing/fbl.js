if (!ProductListing) var ProductListing = {};
ProductListing.fbl = function() {
    var state = tl.store.getState();
    var listingId = state.productListings.mainListing;
    if (!isElementExist(['widgets', listingId, 'fbl'], state)) return false;

    var $listing = $('#box-'+listingId+' .products-listing');

    var url = state.productListings.href;
    var sentRequest = false;
    var sendData = {};
    var pageCount = state['widgets'][listingId]['pageCount'];
    var allPages = Math.ceil( state['widgets'][listingId]['numberOfProducts'] / state['widgets'][listingId]['productsOnPage']);
    sendData.productListing = 1;
    sendData.onlyProducts = 1;

    tl.subscribe(['widgets', listingId, 'pageCount'], function(){
        var state = tl.store.getState();
        sentRequest = false;
        pageCount = state['widgets'][listingId]['pageCount'];
    });
    tl.subscribe(['widgets', listingId, 'numberOfProducts'], function(){
        var state = tl.store.getState();
        allPages = Math.ceil( state['widgets'][listingId]['numberOfProducts'] / state['widgets'][listingId]['productsOnPage']);
    });
    tl.subscribe(['widgets', listingId, 'productsOnPage'], function(){
        var state = tl.store.getState();
        allPages = Math.ceil( state['widgets'][listingId]['numberOfProducts'] / state['widgets'][listingId]['productsOnPage']);
    });
    tl.subscribe(['productListings', 'href'], function(){
        var state = tl.store.getState();
        url = state.productListings.href;
    });

    $(window).on('scroll', function(){
        if (
            $listing.height() - $(window).scrollTop() < $(window).height() &&
            !sentRequest &&
            pageCount < allPages
        ) {
            var state = tl.store.getState();
            sentRequest = true;
            sendData.page = pageCount + 1;

            $.ajax({
                url: url,
                data: sendData,
                dataType: 'json'
            })
                .done(function(data) {
                    sentRequest = true;

                    tl.store.dispatch({
                        type: 'ADD_PRODUCTS',
                        value: {
                            products: data.entryData.products,
                        },
                        file: 'boxes/ProductListing'
                    });

                    var $newItems = $('<div>' + data.html + '</div>');
                    var $items = $('.item', $newItems);

                    $items.each(function(){
                        ProductListing.applyItem($(this), listingId);
                    });

                    tl.store.dispatch({
                        type: 'WIDGET_CHANGE_PAGE_COUNT',
                        value: {
                            widgetId: listingId,
                            pageCount: pageCount + 1,
                        },
                        file: 'boxes/ProductListing'
                    });

                    $listing.append($items);

                    ProductListing.alignItems($listing)
                })
        }
    })
}