{use class="Yii"}
{use class="frontend\design\boxes\quote\Products"}
{use class="frontend\design\boxes\cart\GiveAway"}
{use class="frontend\design\Block"}
{use class="frontend\design\Info"}
{use class="\yii\helpers\Html"}
{\frontend\design\Info::addBlockToWidgetsList('cart-listing')}

<div class="cart-page" id="cart-page">
  {Html::beginForm($action, 'post', ['id' => 'cart-form'])}

    {$message_shopping_cart}
    {Block::widget(['name' => 'quote', 'params' => ['type' => 'quote', 'params' => ['manager' => $manager]]])}

  {Html::endForm()}
  {use class="common\components\google\widgets\GoogleTagmanger"}
  {GoogleTagmanger::trigger()}
  {if !$app->request->isAjax}
  {GoogleTagmanger::getJsEvents([[ 'class' => '.w-quote-checkout-btn a', 'action' => 'click' , 'php_action' => 'checkout', 'page' => 'quote-cart', 'immidiately' => 'true' ]])}
  {/if}
</div>


<script type="text/javascript">
  tl([
      '{Info::themeFile('/js/main.js')}',
      '{Info::themeFile('/js/bootstrap-switch.js')}'
  ], function(){

    var form = $('#cart-form');

    {\frontend\design\Info::addBoxToCss('quantity')}
    $('input.qty-inp-s').quantity({
      event: function(){
        form.trigger('cart-change');
      }
    }).on('blur', function(){
      form.trigger('cart-change');
    });

    {\frontend\design\Info::addBoxToCss('switch')}
    $(".check-on-off").bootstrapSwitch({
      offText: '{$smarty.const.TEXT_NO}',
      onText: '{$smarty.const.TEXT_YES}',
      onSwitchChange: function () {
        $(this).closest('form').trigger('cart-change');
      }
    });

    var send = 0;
    form.off('cart-change').on('cart-change', function(){
      send++;
      $.post(form.attr('action'), form.serializeArray(), function(d){
        send--;
        if (send == 0) {
          $('#cart-page').replaceWith(d)
        }
        $(window).trigger('cart_change');
      });
    });

    $('.remove-btn').on('click', function(){
      $.get($(this).attr('href'), function(d){
        $('#cart-page').replaceWith(d);
        $(window).trigger('cart_change')
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


    $('.addresses input').radioHolder({ holder: '.address-item'});
    $('.shipping-method input').radioHolder();
  })
</script>