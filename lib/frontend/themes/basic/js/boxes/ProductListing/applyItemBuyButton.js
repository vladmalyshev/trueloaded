if (!ProductListing) var ProductListing = {};
ProductListing.applyItemBuyButton = function($item, widgetId){
    var productId = $item.data('id');
    var $buyBox = $('.buyButton', $item);
    var $btnBuy = $('.btn-buy', $buyBox);
    var $btnPreloader = $('.btn-preloader', $buyBox);
    var $btnChooseOptions = $('.btn-choose-options', $buyBox);
    var $btnInCart = $('.btn-in-cart', $buyBox);

    var state = tl.store.getState();
    var product = state.products[productId];
    if (entryData.GROUPS_DISABLE_CHECKOUT){
        $buyBox.hide().html('');
        return '';
    }

    var listingName = state['widgets'][widgetId]['listingName'];
    var itemElements = state['productListings'][listingName]['itemElements'];
    var hasAttributes = +state['products'][productId]['product_has_attributes'];
    var isBundle = +state['products'][productId]['isBundle'];
    if (!itemElements.attributes && hasAttributes || isBundle) {
        $btnChooseOptions.show();
        $btnBuy.hide();
        $btnInCart.hide();
        return '';
    }

    tl.subscribe(['widgets', widgetId, 'products', productId, 'canAddToCart'], function(){
        var state = tl.store.getState();
        if (
            !isElementExist(['widgets', widgetId, 'products', productId, 'canAddToCart'], state)
            && !(+state['products'][productId]['is_virtual'])
        ) {
            $buyBox.hide();
        } else {
            $buyBox.show();
        }
    });

    switchBuyButton();
    tl.subscribe(['widgets', widgetId, 'products', productId, 'productInCart'], function(){
        switchBuyButton()
    });

    tl.subscribe(['widgets', widgetId, 'products', productId, 'addingToCart'], function(){
        state = tl.store.getState()
        if (
            isElementExist( ['widgets', widgetId, 'products', productId, 'addingToCart'], state)
            && state['widgets'][widgetId]['products'][productId]['addingToCart']
        ){
            $btnInCart.addClass('hide');
            $btnBuy.addClass('hide');
            $btnPreloader.show();
        } else {
            $btnInCart.removeClass('hide');
            $btnBuy.removeClass('hide');
            $btnPreloader.hide();
        }
    });

    $btnBuy.on('click', function(e){
        e.preventDefault();
        var state = tl.store.getState()
        if (
            +product.product_has_attributes &&
            !isElementExist( ['widgets', widgetId, 'products', productId, 'attributes'], state)
        ){
            window.location.href = product.link
        }
        ProductListing.addProductToCart(widgetId, productId)
    });

    function switchBuyButton(){
        var state = tl.store.getState();
        if (
            isElementExist(['widgets', widgetId, 'products', productId, 'productInCart'], state) &&
            !isElementExist( ['themeSettings', 'showInCartButton'], state)
        ) {
            $btnInCart.show();
            $btnBuy.hide();
        } else {
            $btnInCart.hide();
            $btnBuy.show();
        }
    }
}