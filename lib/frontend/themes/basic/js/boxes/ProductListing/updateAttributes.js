if (!ProductListing) var ProductListing = {};
ProductListing.updateAttributes = function(widgetId, productId, data, $item){
    var $box = $('.attributes', $item);

    $.ajax({
        url: entryData.mainUrl + 'catalog/product-attributes',
        data: data,
        dataType: 'json'
    })
        .done(function(data) {

            var price = {
                current: data.product_price,
                old: data.special_price ? data.product_price : '',
                special: data.special_price,
            }

            tl.store.dispatch({
                type: 'WIDGET_CHANGE_PRODUCT_PRICE',
                value: {
                    widgetId: widgetId,
                    productId: productId,
                    price: price,
                },
                file: 'boxes/ProductListing/updateAttributes',
            })

            if (typeof data.stock_indicator.can_add_to_cart !== "undefined") {
                tl.store.dispatch({
                    type: 'WIDGET_PRODUCT_CAN_ADD_TO_CART',
                    value: {
                        widgetId: widgetId,
                        productId: productId,
                        canAddToCart: data.stock_indicator.can_add_to_cart,
                    },
                    file: 'boxes/ProductListing/updateAttributes'
                });
            }

            tl.store.dispatch({
                type: 'WIDGET_PRODUCT_IN_CART',
                value: {
                    widgetId: widgetId,
                    productId: productId,
                    productInCart: data.product_in_cart,
                },
                file: 'boxes/ProductListing/updateAttributes'
            });

            $box.removeClass('loader');
            $box.html(data.product_attributes);
            ProductListing.applyItemAttributes($item, widgetId)

            ProductListing.alignItems($('#box-' + widgetId + ' .products-listing'))
        })
        .fail(function() {
            tl.store.dispatch({
                type: 'WIDGET_CHANGE_PRODUCT_ATTRIBUTE',
                value: {
                    widgetId: widgetId,
                    productId: productId,
                    attributes: attributes,
                },
                file: 'boxes/ProductListing/updateAttributes',
            })
        });
}