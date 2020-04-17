{use class="Yii"}
{if Yii::$app->user->isGuest && \common\helpers\PlatformConfig::getFieldValue('platform_please_login')}
    <span class="current">{sprintf($smarty.const.TEXT_PLEASE_LOGIN, tep_href_link(FILENAME_LOGIN,'','SSL'))}</span>
{else}
    <span class="old" {if !$product.price_special} style="display:none;"{/if}>{$product.price_old}</span>
    <span class="specials" {if !$product.price_special} style="display:none;"{/if}>{$product.price_special}</span>
    <span class="current" {if $product.price_special} style="display:none;"{/if}>{$product.price}</span>
{/if}