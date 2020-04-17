{use class = "yii\helpers\Html"}
<div class="page-confirmation">
  <h1>{$smarty.const.ORDER_CONFIRMATION}</h1>
  {Html::beginForm($form_action_url, 'post', ['id' => 'frmCheckoutConfirm', 'name' => 'checkout_confirmation'], false)}
    {if $is_shipable_order}
  <div class="col-left">
    <div class="heading-4">{$smarty.const.SHIPPING_ADDRESS}<a href="{$shipping_address_link}" class="edit">{$smarty.const.EDIT}</a></div>
    <div class="confirm-info">
      {$address_label_delivery}
    </div>
  </div>
    {/if}

  <div class="heading-4">{$smarty.const.PRODUCT_S}<a href="{$cart_link}" class="edit">{$smarty.const.EDIT}</a></div>

  {use class="frontend\design\boxes\sample\Products"}
  {Products::widget(['type'=> 3])}

    {\frontend\design\Info::addBoxToCss('price-box')}
  <div class="price-box">
    {include file="./totals.tpl"}
  </div>

  <div class="buttons">
    <div class="right-buttons">
      <button type="submit" class="btn-2">{$smarty.const.CONFIRM_ORDER}</button>
    </div>
  </div>
  {$payment_process_button_hidden}
  {Html::endForm()}
</div>