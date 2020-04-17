<div class="search-products" style="overflow: overlay;" id="element-search-products">
    <ul name="tree" size="20" style="width: 100%;height: 500px;list-style: none; padding:0;">
    {foreach $promo->settings['tree'] as $key => $value}
        {if $value['category'] eq 1}
            {assign var="parent" value="cat_{$value['id']}"}
            <li id="{$value['id']}" value="cat_{$value['id']}" class="category_item {if $value['status'] eq 0}dis_prod{/if}" level="{$value['level']}" {if $promo->settings['disable_categories']}disabled="disabled"{/if}>{$value['text']}</li>
            {$first = false}
        {else}
            <li id="{$value['id']}" value="prod_{$value['id']}" parent="cat_{$value['parent_id']}" class="product_item  {if $value['status'] eq 0}dis_prod{/if}">{$value['text']}</li>
        {/if}
    {/foreach}
    </ul>
</div>
