<script src="https://www.finance-calculator.co.uk/js/imega2.js"></script>
<iframe style="border:none" class="" src="{$url}{$price}" width="100%" scrolling="no" frameborder="0" id="klarnaframe"></iframe>
<script>
tl(function(){
	iFrameResize();
    $("form[name=cart_quantity]").on('attributes_updated', function(event, data){
        var _price = '';
        if (data.hasOwnProperty('special_price') && data.special_price.length > 0){
            _price = data.special_price;
        } else {
            _price = data.product_price;
        }
        if (_price.length > 0){
            var regex = /(<([^>]+)>)/ig;
            $('#klarnaframe').attr('src', "{$url}" + _price.replace(regex, ""));
        }
    });
})
</script>