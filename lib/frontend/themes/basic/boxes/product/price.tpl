<div class="price" {*itemprop="offers" itemscope itemtype="http://schema.org/Offer"*}>
    {if Yii::$app->user->isGuest && \common\helpers\PlatformConfig::getFieldValue('platform_please_login')}
        <div class="pnp_value">{sprintf($smarty.const.TEXT_PLEASE_LOGIN, tep_href_link(FILENAME_LOGIN,'','SSL'))}</div>
    {else}
  {if $old != ''}<span id="product-price-old" class="old">{$old}{if $smarty.const.DISPLAY_BOTH_PRICES =='True'} <small class="inc-vat-title">{$smarty.const.TEXT_INC_VAT}</small>{/if}</span>{/if}
  {if $old_ex != ''}<span id="product-price-old-ex" class="old old-ex">{$old_ex} <small class="ex-vat-title">{$smarty.const.TEXT_EXC_VAT}</small></span>{/if}
  {if $special != ''}<span id="product-price-special" class="special">{$special}{if $smarty.const.DISPLAY_BOTH_PRICES =='True'} <small class="inc-vat-title">{$smarty.const.TEXT_INC_VAT}</small>{/if}</span>{/if}
  {if $special_ex != ''}<span id="product-price-special-ex" class="special special-ex">{$special_ex} <small class="ex-vat-title">{$smarty.const.TEXT_EXC_VAT}</small></span>{/if}
  {if $current != ''}<span id="product-price-current" class="current">{$current}{if $smarty.const.DISPLAY_BOTH_PRICES =='True'} <small class="inc-vat-title">{$smarty.const.TEXT_INC_VAT}</small>{/if}</span>{/if}
  {if $current_ex != ''}<span id="product-price-current-ex" class="current current-ex">{$current_ex} <small class="ex-vat-title">{$smarty.const.TEXT_EXC_VAT}</small></span>{/if}
    {/if}
    <link itemprop="availability" href="http://schema.org/{if $stock_info['stock_code'] == 'out-stock'}OutOfStock{else}InStock{/if}" />
    {if $special && $expires_date}
        {*<meta itemprop="priceValidUntil" content="{$expires_date}" />*}
    {/if}
</div>