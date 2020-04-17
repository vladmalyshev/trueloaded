if (!ProductListing) var ProductListing = {};
ProductListing.applyItemQtyInput = function($item, widgetId) {
    var productId = $item.data('id');
    var $box = $('.qtyInput', $item);
    var $input = $('input', $box);

    var state = tl.store.getState();
    var product = state.products[productId];
    if (entryData.GROUPS_DISABLE_CHECKOUT || product.show_attributes_quantity){
        $('> *', $box).hide();
        return '';
    }

    var listingName = state['widgets'][widgetId]['listingName'];
    var itemElements = state['productListings'][listingName]['itemElements'];
    var hasAttributes = +state['products'][productId]['product_has_attributes'];
    var isBundle = +state['products'][productId]['isBundle'];
    if (!itemElements.attributes && hasAttributes || isBundle) {
        $('> *', $box).remove();
        return '';
    }

    tl.subscribe(['widgets', widgetId, 'products', productId, 'canAddToCart'], function(){
        var state = tl.store.getState();
        if (
            !isElementExist(['widgets', widgetId, 'products', productId, 'canAddToCart'], state)
            && !(+state['products'][productId]['is_virtual'])
        ) {
            $box.hide();
        } else {
            $box.show();
        }
    });

    $input.quantity();

    $input.on('change', changeQty);
    if (isElementExist(['widgets', widgetId, 'products', productId, 'qty'], state)) {
        $input.val(state['widgets'][widgetId]['products'][productId]['qty'])
    } else {
        changeQty();
    }

    function changeQty(){
        tl.store.dispatch({
            type: 'WIDGET_CHANGE_PRODUCT_QTY',
            value: {
                widgetId: widgetId,
                productId: productId,
                qty: $input.val(),
            },
            file: 'boxes/ProductListing/applyItemQtyInput'
        })
    }
}