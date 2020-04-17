<div class="shipping-method">
    {$order->info['shipping_method']}
</div>
{if $shipping_additional_info_block}
    <div class="additional-info">
        {$shipping_additional_info_block}
    </div>
{/if}