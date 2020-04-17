{if is_array($product.promo_details) && count($product.promo_details) > 0 || $product.price_special}
<div class="promos-info">
    {*if $product.promo_class}
        <div class="{$product.promo_class}"></div>
    {/if*}
    {foreach $product.promo_details as $key => $info}
        {if $info.working_promo}
        <div class="promo-item">
            {if isset($info.promo_icon)}
                <div class="promo-icon"><img src="{$info.promo_icon}" alt="{$info.promo_name}"></div>
            {/if}
            <div class="promo-name">{$info.promo_name}</div>
        </div>
        {/if}
    {/foreach}
    {if $product.price_special && $product.promo_class}
        <div class="promo-item sale-flag">
            <div class="promo-name">{$smarty.const.SALE_TEXT}</div>
        </div>
    {/if}
</div>
{/if}