<tr role="row" prefix="element-box-{$product['products_id']}" class="{$product['status_class']}">
    <td class="sort-pointer"></td>
    <td class="ast-img-element img-ast-img">
        {$product['image']}
    </td>
    <td class="ast-name-element">
        {$product['products_name']} ({$product['price']})
        <input type="hidden" name="pctemplate_{$elements_id}_products_id[]" value="{$product['products_id']}" />
    </td>
    <td class="ast-discount-element">
        <input type="text" name="pctemplate_{$elements_id}_qty_min[{$product['products_id']}]" value="{$product['qty_min']}" size="3" class="form-control" placeholder="1" />
    </td>
    <td class="ast-price-element">
        <input type="text" name="pctemplate_{$elements_id}_qty_max[{$product['products_id']}]" value="{$product['qty_max']}" size="3" class="form-control" />
    </td>
    <td>
        <input type="radio" name="pctemplate_{$elements_id}_def" value="{$product['products_id']}" {if $product['def']}checked{/if} class="pctemplate_def" title="{$smarty.const.TEXT_DEFAULT}">
    </td>
    <td class="remove-ast" onclick="deleteSelectedProduct(this)"></td>
</tr>