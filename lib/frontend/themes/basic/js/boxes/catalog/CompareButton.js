tl(entryData.jsPathUrl + '/main.js', function(){
    var params = {};
    params.compare = [];
    var $compareButton = $('.w-catalog-compare-button .compare_button');
    $compareButton.popUp({
        box: "<div class='popup-box-wrap'><div class='around-pop-up'></div><div class='popup-box popupCompare'><div class='pop-up-close'></div><div class='popup-heading compare-head'>" + entryData.tr.BOX_HEADING_COMPARE_LIST + "</div><div class='pop-up-content'><div class='preloader'></div></div></div></div>",
        data: params,
    });

    tl.subscribe(['productListings', 'compare'], function(){
        var state = tl.store.getState();

        params.compare = state['productListings']['compare']['products']
        if (params.compare.length > 1) {
            $compareButton.show()
        } else {
            $compareButton.hide()
        }
    });
})