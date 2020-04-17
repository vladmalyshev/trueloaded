{use class="Yii"}
{use class="frontend\design\Info"}
{if $product && $product['settings']->show_attributes_quantity}
    {\frontend\design\boxes\product\MultiInventory::widget(['params' => $params, 'settings' => $settings])}
{else}
<div id="product-attributes" class="attributes">
  {foreach $attributes as $item}
    {if $item['type'] == 'radio'}
      {include file="`$smarty.current_dir`/attributes/radio.tpl" item=$item}
    {else}
      {include file="`$smarty.current_dir`/attributes/select.tpl" item=$item}
    {/if}
  {/foreach}
{if !Yii::$app->request->get('list_b2b')}
<script type="text/javascript">
{if not $isAjax}
  tl(function(){
    if (document.forms['cart_quantity']) {
      update_attributes(document.forms['cart_quantity']);
    }
  });
{/if}
  function update_attributes(theForm) {
    var _data = $(theForm).find('input, select, textarea').filter(function() { return $(this).closest(".item").length == 0; }).serialize();
    $.get("{Yii::$app->urlManager->createUrl('catalog/product-attributes')}", _data, function(data, status) {
      if (status == "success") {
        $('#product-price-old').html(data.product_price);
        $('#product-price-current').html(data.product_price);
        if(data.hasOwnProperty('special_price') && data.special_price.length > 0){
            $('#product-price-special').show().html(data.special_price);
            if (!$('#product-price-old').hasClass('old')) $('#product-price-old').addClass('old');
            if ($('#product-price-current').hasClass('price_1')){
                $('#product-price-current').html(data.special_price);
            }
        } else {
            $('#product-price-old').removeClass('old');
            $('#product-price-special').hide();
        }
          if(
              data.hasOwnProperty('personalCatalogButton') &&
              data.hasOwnProperty('personalCatalogButtonWrapId') &&
              data.personalCatalogButton.length > 0
          ){
              $('#personal-button-wrap-'+data.personalCatalogButtonWrapId).html(data.personalCatalogButton);
          }
        $('#product-attributes').replaceWith(data.product_attributes);
        if (data.product_valid > 0) {
            if (data.product_in_cart && !isElementExist( ['themeSettings', 'showInCartButton'], entryData)){
                $('.add-to-cart').hide();
                $('.in-cart').show();
                $('.qty-input').hide()
            } else {
                $('.add-to-cart').show();
                $('.in-cart').hide();
                $('.qty-input').show()
            }
            if ( data.stock_indicator ) {
              var stock_data = data.stock_indicator;
              if ( stock_data.add_to_cart ) {
                  $('#btn-cart').show();
                  $('.qty-input').show();
                  //$('.add-to-cart').show();
                  if (data.product_in_cart && !isElementExist( ['themeSettings', 'showInCartButton'], entryData)){
                      $('.add-to-cart').hide();
                      $('.in-cart').show();
                      $('.qty-input').hide()
                  } else {
                      $('.add-to-cart').show();
                      $('.in-cart').hide();
                      $('.qty-input').show()
                  }
                  $('#btn-cart-none:visible').hide();
              }else{
                  $('#btn-cart').hide();
                  if ($('.qty-input').length == 1){
                    $('.qty-input').hide();
                  }
                  //$('.add-to-cart').hide();
                  if (data.product_in_cart && !isElementExist( ['themeSettings', 'showInCartButton'], entryData)){
                      $('.add-to-cart').hide();
                      $('.in-cart').show();
                      $('.qty-input').hide()
                  } else {
                      $('.add-to-cart').show();
                      $('.in-cart').hide();
                      $('.qty-input').show()
                  }
                  $('#btn-cart-none:hidden').show();
              }
              if ( stock_data.request_for_quote ) {
                  $('.btn-rfq, #btn-rfq').show();
                  $('#btn-cart-none:visible').hide();
              }else{
                  $('.btn-rfq, #btn-rfq').hide();
              }
              if ( stock_data.ask_sample ) {
                  $('.btn-sample, #btn-sample').show();
              }else{
                  $('.btn-sample, #btn-sample').hide();
              }
              if ( stock_data.notify_instock ) {
                  $('#btn-notify').show();
              }else{
                  $('#btn-notify').hide();
              }
              if ( stock_data.quantity_max > 0 ) {
                  var qty = $('.qty-inp');
                  $.each(qty, function(i, e){
                      $(e).attr('data-max', stock_data.quantity_max).trigger('changeSettings');
                      if ($(e).val() > stock_data.quantity_max) {
                          $(e).val(stock_data.quantity_max);
                      }
                  });
              }
          }else{
              $('#btn-cart').hide();
              $('#btn-cart-none').show();
              $('#btn-notify').hide();
              $('.qty-input').hide();
          }
          {*
          if (data.stock_indicator && data.stock_indicator.max_qty > 0) {
            {if $smarty.const.STOCK_CHECK != 'false'}
						var qty = $('.qty-inp');
            $.each(qty, function(i, e){
              $(e).attr('data-max', data.stock_indicator.max_qty).trigger('changeSettings');
              if ($(e).val() > data.stock_indicator.max_qty) {
                $(e).val(data.stock_indicator.max_qty);
              }
            });
            /*var qty = $('#qty');
            qty.attr('data-max', data.product_qty).trigger('changeSettings');
            if (qty.val() > data.product_qty) {
              qty.val(data.product_qty);
            }
						*/
            {/if}
            $('#btn-cart').show();
            $('#btn-cart-none').hide();
            $('#btn-notify').hide();
            if (data.product_in_cart){
              $('.add-to-cart').hide();
              $('.qty-input').hide();
              $('.in-cart').show()
            } else {
              $('.add-to-cart').show();
              $('.qty-input').show();
              $('.in-cart').hide()
            }
          } else {
            {if $smarty.const.STOCK_CHECK == 'false'}
            $('#btn-cart').show();
            $('#btn-cart-none').hide();
            $('#btn-notify').hide();
            {else}
            $('#btn-cart').hide();
            $('#btn-cart-none').hide();
            $('#btn-notify').show();
            {/if}
          }
          *}
        } else {
          if ($('.qty-input').length == 1){
            $('.qty-input').hide();
          }
          $('#btn-cart').hide();
          $('#btn-cart').hide();
          $('#btn-cart-none').show();
          $('#btn-notify').hide();
        }
        if ( typeof data.image_widget != 'undefined' ) {
          $('.js-product-image-set').replaceWith(data.image_widget);
        }
        if ( typeof data.image_widget != 'undefined' ) {
            $('.js-product-image-set').replaceWith(data.image_widget);
        }
        if ( typeof data.dynamic_prop != 'undefined' ) {
            for( var prop_name in data.dynamic_prop ) {
                if ( !data.dynamic_prop.hasOwnProperty(prop_name) ) continue;
                var _value = data.dynamic_prop[prop_name];
                var $value_dest = $('.js_prop-'+prop_name);
                if ( $value_dest.length==0 ) continue;
                $value_dest.html(_value);
                $value_dest.parents('.js_prop-block').each(function() {
                    if (_value==''){
                        $(this).addClass('js-hide');
                    }else{
                        $(this).removeClass('js-hide');
                    }
                });
            }
        }
        if ( typeof data.stock_indicator != 'undefined' ) {
            $('.js-stock').html('<span class="'+data.stock_indicator.text_stock_code+'"><span class="'+data.stock_indicator.stock_code+'-icon">&nbsp;</span>'+data.stock_indicator.stock_indicator_text+'</span>');
        }
		$('#product-attributes select').addClass('form-control');
        $(theForm).trigger('attributes_updated', [data]);
        return data;
      }
    },'json').then(function(data){ if (typeof sProductsReload == 'function'){ sProductsReload(data); } });
  }
</script>
{/if}
</div>
{/if}
