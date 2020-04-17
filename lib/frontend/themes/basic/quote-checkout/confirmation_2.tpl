{use class = "yii\helpers\Html"}
{use class="frontend\design\Info"}
{use class="frontend\design\Block"}
{\frontend\design\Info::addBoxToCss('price-box')}
{\frontend\design\Info::addBoxToCss('multi-page-checkout')}

{if $noShipping}{$noShipping = '_no_shipping'}{/if}


{if Info::isAdmin()}
<div class="multi-page-checkout">
  <div class="checkout-step" id="shipping-step">
    <div class="checkout-heading">
      <span class="edit">{$smarty.const.EDIT}</span>
      <span class="count">1</span>
        {Block::widget(['name' => 'checkout_delivery_title'|cat:$noShipping, 'params' => ['type' => 'confirmation', 'params' => $params]])}
    </div>
    <div class="checkout-content" style="display: none"></div>
  </div>
  <div class="checkout-step" id="payment-step">
    <div class="checkout-heading">
      <span class="edit">{$smarty.const.EDIT}</span>
      <span class="count">2</span>
        {Block::widget(['name' => 'checkout_payment_title'|cat:$noShipping, 'params' => ['type' => 'confirmation', 'params' => $params]])}
    </div>
    <div class="checkout-content" style="display: none"></div>
  </div>
  <div class="checkout-step active" id="confirmation-step">
    <div class="checkout-heading"><span class="count">3</span>
        {Block::widget(['name' => 'checkout_confirmation_title'|cat:$noShipping, 'params' => ['type' => 'confirmation', 'params' => $params]])}
    </div>
    <div class="checkout-content">
{/if}



<div class="page-confirmation">
  {Html::beginForm($form_action_url, 'post', ['id' => 'frmCheckoutConfirm', 'name' => 'checkout_confirmation'], false)}


     {Block::widget(['name' => 'checkout_confirmation'|cat:$noShipping|cat:'_q', 'params' => ['type' => 'confirmation', 'params' => $params]])}



  {$payment_process_button_hidden}
  {Html::endForm()}
</div>


        {if Info::isAdmin()}
    </div>
  </div>
</div>
{/if}