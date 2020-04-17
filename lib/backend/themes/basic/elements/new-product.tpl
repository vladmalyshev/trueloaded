<tr role="row" prefix="element-box-{$element['products_id']}" class="{$element['status_class']}">
    <td class="sort-pointer"></td>
    <td class="ast-img-element img-ast-img">
        {$element['image']}
    </td>
    <td class="ast-name-element">
        {$element['products_name']} ({$element['price']})
        <input type="hidden" name="element_products_id[]" value="{$element['products_id']}" />
    </td>
    <td class="ast-discount-element">
        <input type="text" name="element_products_discount[{$element['products_id']}]" value="{$element['discount_configurator']}" class="form-control" />
    </td>
    <td class="ast-price-element">
        <input type="text" name="element_products_price[{$element['products_id']}]" value="{$element['price_configurator']}" class="form-control" placeholder="{$element['price']}" />
    </td>
    <td class="remove-ast" onclick="deleteSelectedElement(this)"></td>
</tr>