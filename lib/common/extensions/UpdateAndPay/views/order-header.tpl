{use class="frontend\design\Info"}
{use class = "yii\helpers\Html"}
{Info::addBoxToCss('history-info')}
<div class="account_history_info">
    {if $payment_error && $payment_error.title }
       <p class="payment-error"><strong>{$payment_error.title}</strong><br>{$payment_error.error}</p>
    {/if}
    <h1>{$smarty.const.HEADING_ORDER_NUMBER_NEW}:#{$order_id} / Invoice </h1>
    <style>
     .history_info .block .box { display: inline-block; width: 33%; }
     .historyInfoColumn{ display: inline-block;width:49%; }
    </style>
    <div class="history_info">
        <div class="block">
            <div class="box">
                <h2 class="title-name">{$smarty.const.HEADING_NAME}</h2>
                <div class="contentColumn">{$order->customer['firstname']} {$order->customer['lastname']}</div>
            </div>
            <div class="box">
                <h2 class="title-phone">{$smarty.const.ENTRY_TELEPHONE_NUMBER}</h2>
                <div class="contentColumn">{$order->customer['telephone']}</div>
            </div>
            <div class="box">
                <h2 class="title-email">{$smarty.const.ENTRY_EMAIL_ADDRESS}</h2>
                <div class="contentColumn">{$order->customer['email_address']}</div>
            </div>
        </div>
        {if $order->delivery}        
            <div class="historyInfoColumn">
                <h2 class="title-delivery-address">{$smarty.const.HEADING_DELIVERY_ADDRESS}</h2>
                <div class="contentColumn">
                    <div>{\common\helpers\Address::address_format($order->delivery['format_id'], $order->delivery, 1, ' ', '<br>')}</div>            
                </div>
                {if $order->info['shipping_method'] !=''}
                    <h2 class="title-ship-method">{$smarty.const.HEADING_SHIPPING_METHOD}</h2>
                    <div class="contentColumn">{$order->info['shipping_method']}</div>
                {/if}
            </div>
        {/if} 
        <div class="historyInfoColumn">
            <h2 class="title-billing-address">{$smarty.const.TEXT_BILLING_ADDRESS}</h2>
            <div class="contentColumn">
                <div>{\common\helpers\Address::address_format($order->billing['format_id'], $order->billing, 1, ' ', '<br>')}</div>                
            </div>
            <h2 class="title-payment">{$smarty.const.HEADING_PAYMENT_METHOD}</b></h2>
            <div class="contentColumn">{$payment_method}</div>
        </div> 
    </div>
    <div class="productsDiv">
        <h2 class="product_details">{$smarty.const.HEADING_PRODUCT_DETAILS}</h2>
        <div class="cart-listing w-cart-listing{\frontend\design\Info::addBlockToWidgetsList('cart-listing')}">
            <div class="headings">
                <div class="head image">{$smarty.const.HEADING_PRODUCTS}</div>
                <div class="head name"></div>
                <div class="head qty"></div>
                <div class="head price">{$smarty.const.HEADING_PRICE}</div>
            </div>
            {foreach $order_product as $order_product_array}
                <div class="item">
                    <div class="image">
                    {if $order_product_array.product_info_link}
                        <a href="{$order_product_array.product_info_link}" target="_blank" title="{$order_product_array.order_product_name|escape:'html'}"><img src="{$order_product_array.products_image}" alt="{$order_product_array.order_product_name|escape:'html'}"></a>
                    {else}
                        <img src="{$order_product_array.products_image}" alt="{$order_product_array.order_product_name|escape:'html'}">
                    {/if}
                    </div>
                    <div class="name">
                        {if $order_product_array.product_info_link}
                          <a href="{$order_product_array.product_info_link}" target="_blank" title="{$order_product_array.order_product_name|escape:'html'}">{$order_product_array.order_product_name}</a>
                        {else}
                          {$order_product_array.order_product_name}
                        {/if}

                        {if count($order_product_array['attr_array'])>0}
                                        <div class="history_attr">
                                        {foreach $order_product_array['attr_array'] as $info_attr}
                                                        {if $info_attr.order_pr_option}
                                                                        <div><strong>{$info_attr.order_pr_option}:</strong><span>{$info_attr.order_pr_value}</span></div>
                                                        {/if}
                                        {/foreach}
                                        </div>
                        {/if}
                    </div>
                    
                        <div class="qty">
                            
                            {$order_product_array.order_product_qty}
                            
                        </div>
                        <div class="price">{$order_product_array.final_price}</div>
                    
                </div>
            {/foreach}
        </div>
    </div>
    {frontend\design\Info::addBoxToCss('sub-totals')}
    <div class="historyTotal">
        <table class="tableForm">
            {foreach $order_totals as $order_total}
                <tr  class="'{$order_total['class']} {if $order_total['show_line']} totals-line{/if}">
                    <td align="right">{$order_total.title}</td>
                    <td align="right">{$order_total.text}</td>
                </tr>
            {/foreach}
        </table>
    </div>
</div>