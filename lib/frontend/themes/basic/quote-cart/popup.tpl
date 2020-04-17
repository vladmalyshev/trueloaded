{use class="Yii"}
{use class="frontend\design\boxes\quote\Products"}
{use class="frontend\design\boxes\cart\GiveAway"}
{use class="frontend\design\Block"}
{use class="frontend\design\Info"}
{use class="\yii\helpers\Html"}
<div class="cart-page" id="cart-page">  
  {Html::beginForm($action, 'post', ['id' => 'cart-form'])}

    <h1>{$smarty.const.TEXT_ITEM_IN_YOUR_CART}</h1>
    {$message_shopping_cart}
    {use class="frontend\design\boxes\quote\Products"}
    {Products::widget()}


    <div class="buttons">
      <div class="left-buttons"><span class="btn btn-cancel">{$smarty.const.CONTINUE_SHOPPING}</span></div>
      <div class="right-buttons"><a href="{$cart_link}" class="btn-2">{$smarty.const.TEXT_GO_TO_CART}</a></div>
    </div>
    {Html::endForm()}
    {use class="common\components\google\widgets\GoogleTagmanger"}
    {GoogleTagmanger::trigger()}



  <script type="text/javascript">
    tl('{Info::themeFile('/js/main.js')}', function(){

      var form = $('#cart-form');

      {\frontend\design\Info::addBoxToCss('quantity')}
      $('input.qty-inp-s').quantity({
        event: function(){
          form.trigger('cart-change');
        }
      });

      $('.cart-page .btn-cancel').on('click', function(){
        $('.popup-box-wrap:last').remove();
      });

      var send = 0;
      form.on('cart-change', function(){
        send++;
        $.post(form.attr('action') + '&popup=1', form.serializeArray(), function(d){
          send--;
          if (send == 0) {
            $('#cart-page').replaceWith(d)
          };
          $(window).trigger('cart_change')
        });
      });

      $('.remove-btn').on('click', function(){
        $.get($(this).attr('href')+ '&popup=1', function(d){
          $('#cart-page').replaceWith(d)
        });
        return false
      });

      $('.input-apple button').on('click', function(){
        $.post(form.attr('action'), form.serializeArray(), function(d){
          $('#cart-page').replaceWith(d);
          $(window).trigger('cart_change')
        });
        return false
      });


      $(window).trigger('cart_change');
      $('.addresses input').radioHolder({ holder: '.address-item'});
      $('.shipping-method input').radioHolder();
    })
  </script>
</div>

