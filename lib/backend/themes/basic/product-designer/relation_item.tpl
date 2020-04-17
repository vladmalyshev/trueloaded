<tr role="row" prefix="element-box-{$oItem->id}">
    <td class="sort-pointer"></td>
    <td class="ast-name-element">
        {$oItem->name}
        <input type="hidden" name="item_id[]" value="{$oItem->id}" />
    </td>
    <td class="remove-ast" onclick="deleteSelectedElement(this)"></td>
</tr>