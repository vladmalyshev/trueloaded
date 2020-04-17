{use class="Yii"}
{if Yii::$app->user->isGuest && \common\helpers\PlatformConfig::getFieldValue('platform_please_login')}
    
{elseif $settings['b2b']}
    <input
            type="text"
            name="qty_p[]"
            value="{if isset($product.add_qty)}{if $product.stock_indicator.quantity_max < $product.add_qty}{$product.stock_indicator.quantity_max}{else}{$product.add_qty}{/if}{else}0{/if}"
            data-zero-init="1"
            class="qty-inp"
            {if $product.stock_indicator.quantity_max>0}
                data-max="{$product.stock_indicator.quantity_max}"
            {/if}
            {if $product.order_quantity_data && $product.order_quantity_data.order_quantity_minimal>0}
                data-min="{$product.order_quantity_data.order_quantity_minimal}"
            {else}
                data-min="0"
            {/if}
            {if $product.order_quantity_data && $product.order_quantity_data.order_quantity_step>1}
                data-step="{$product.order_quantity_data.order_quantity_step}"
            {/if}
    />
{else}
    <input
            type="text"
            name="qty_p"
            value="1"
            class="qty-inp"
            {if $product.stock_indicator.quantity_max > 0 }
                data-max="{$product.stock_indicator.quantity_max}"
            {/if}
            {if \common\helpers\Acl::checkExtension('MinimumOrderQty', 'setLimit')}
                {\common\extensions\MinimumOrderQty\MinimumOrderQty::setLimit($product.order_quantity_data)}
            {/if}
            {if \common\helpers\Acl::checkExtension('OrderQuantityStep', 'setLimit')}
                {\common\extensions\OrderQuantityStep\OrderQuantityStep::setLimit($product.order_quantity_data)}
            {/if}
    />
{/if}
