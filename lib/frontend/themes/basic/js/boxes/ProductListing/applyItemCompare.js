if (!ProductListing) var ProductListing = {};
ProductListing.applyItemCompare = function($item, widgetId) {
    var productId = $item.data('id');
    var $checkbox = $('.compare input', $item);
    var $viewButton = $('.compare .view', $item);
    var params = {};
    params.compare = [];

    tl.subscribe(['productListings', 'compare', 'products'], function(){
        var state = tl.store.getState();
        params.compare = state['productListings']['compare']['products'];

        if (
            isElementExist(['productListings', 'compare', 'products'], state) &&
            state['productListings']['compare']['products'].indexOf(productId) !== -1
        ) {
            $checkbox.prop('checked', true);

            if (state['productListings']['compare']['products'].length > 1) {
                $viewButton.show()
            }
        } else {
            $checkbox.removeAttr('checked');
            $viewButton.hide()
        }
    });

    $viewButton.popUp({
        box: "<div class='popup-box-wrap'><div class='around-pop-up'></div><div class='popup-box popupCompare'><div class='pop-up-close'></div><div class='popup-heading compare-head'>" + entryData.tr.BOX_HEADING_COMPARE_LIST + "</div><div class='pop-up-content'><div class='preloader'></div></div></div></div>",
        data: params
    })

    $checkbox.on('change', function(){
        var qty = 0;
        if ($checkbox.prop('checked')) {
            tl.store.dispatch({
                type: 'ADD_PRODUCT_TO_COMPARE',
                value: {
                    productId: productId,
                },
                file: 'boxes/ProductListing/applyItemCompare'
            });
        } else {
            tl.store.dispatch({
                type: 'REMOVE_PRODUCT_FROM_COMPARE',
                value: {
                    productId: productId,
                },
                file: 'boxes/ProductListing/applyItemCompare'
            });
        }

        var state = tl.store.getState();
        localStorage.setItem('compare', JSON.stringify(state['productListings']['compare']['products']))
    })
}