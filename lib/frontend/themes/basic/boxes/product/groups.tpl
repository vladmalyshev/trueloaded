{foreach $properties as $property}
<div class="radioBox2 radioBox">
    <div class="radioBoxHead"><span class="title">{$property['properties_name']}:</span> <span class="value">{$property['values'][$property['current_value']]['text']}</span></div>
        {foreach $property['values'] as $value}
        <label>
            <input type="radio" name="prop_{$property['properties_id']}"{if $value['product']['selected']} checked{/if}>
            {if !$value['product']['selected']}<a href="{$value['product']['link']}">{/if}
            <div class="containerBlock" title="{$value['product']['name']}">
                {if strlen($value['image']) > 0}
                <img src="{$app->request->baseUrl}/images/{$value['image']}" title="{$value['text']}" alt="{$value['text']}">
                {* <div class="val2">{$value['product']['price']}</div> *}
                {else}
                <div class="val1">{$value['text']}</div>
                <div class="val2">{$value['product']['price']}</div>
                {/if}
            </div>
            {if !$value['product']['selected']}</a>{/if}
        </label>
    {/foreach}
</div>
{foreachelse}
<div class="radioBox2 radioBox">
    <div class="radioBoxHead"><span class="title">{$smarty.const.TEXT_PRODUCTS_GROUPS_VARIANTS}:</span></div>
        {foreach $products as $product}
        <label>
            <input type="radio" name="products"{if $product['selected']} checked{/if}>
            {if !$product['selected']}<a href="{$product['link']}">{/if}
            <div class="containerBlock" title="{$product['name']}">
                <img src="{$product['image']}" title="{$product['name']}" alt="{$product['name']}">
                {* <div class="val2">{$product['price']}</div> *}
            </div>
            {if !$product['selected']}</a>{/if}
        </label>
    {/foreach}
</div>
{/foreach}
