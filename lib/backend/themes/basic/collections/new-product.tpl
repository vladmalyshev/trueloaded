<tr role="row" prefix="collection-box-{$collection['products_id']}" class="{$collection['status_class']}">
    <td class="sort-pointer"></td>
    <td class="ast-img-collection img-ast-img">
        {$collection['image']}
    </td>
    <td class="ast-name-collection">
        {$collection['products_name']} ({$collection['price']})
        <input type="hidden" name="collection_products_id[]" value="{$collection['products_id']}" />
    </td>
<!-- {*
    <td class="ast-discount-collection">
        <input type="text" name="collection_products_discount[{$collection['products_id']}]" value="{$collection['discount_configurator']}" class="form-control" />
    </td>
    <td class="ast-price-collection">
        <input type="text" name="collection_products_price[{$collection['products_id']}]" value="{$collection['price_configurator']}" class="form-control" placeholder="{$collection['price']}" />
    </td>
*} -->
    <td class="remove-ast" onclick="deleteSelectedCollection(this)"></td>
</tr>