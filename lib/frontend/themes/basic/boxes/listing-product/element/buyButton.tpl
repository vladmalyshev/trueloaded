{use class="Yii"}
{if Yii::$app->user->isGuest && \common\helpers\PlatformConfig::getFieldValue('platform_please_login')}
    
{else}
    <a href="{$product.link_buy}"
       class="btn-1 btn-buy"
       rel="nofollow"
       title="{$smarty.const.ADD_TO_CART}"
       {if $product.product_in_cart || $product.stock_indicator.flags.notify_instock}style="display: none"{/if}>{$smarty.const.ADD_TO_CART}</a>

    <a href="{tep_href_link(FILENAME_SHOPPING_CART)}"
       class=" btn btn-in-cart"
       rel="nofollow"
       title="{$smarty.const.TEXT_IN_YOUR_CART}"
       {if !$product.product_in_cart} style="display: none"{/if}>{$smarty.const.TEXT_IN_YOUR_CART}</a>

    <a href="{$product.link}"
       class=" btn btn-choose-options"
       title="{$smarty.const.TEXT_CHOOSE_OPTIONS}"
       style="display: none">{$smarty.const.TEXT_CHOOSE_OPTIONS}</a>

    <span class="btn-1 btn-preloader" style="display: none"></span>
{/if}