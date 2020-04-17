<tr role="row" prefix="element-box-{$product->getAttribute('products_id')}" class="{$product->getAttribute('status_class')}">
    <td class="sort-pointer"></td>
    <td class="ast-img-element img-ast-img">
        {$product->getAttribute('image')}
    </td>
    <td class="ast-name-element">
        {$product->getAttribute('products_name')} ({$product->getAttribute('price')})
        <input type="hidden" name="products_id[]" value="{$product->getAttribute('products_id')}" />
    </td>
    <td class="remove-ast" onclick="deleteSelectedElement(this)"></td>
</tr>