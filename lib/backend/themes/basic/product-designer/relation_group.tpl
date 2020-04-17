<tr role="row" prefix="element-box-{$oGroup->id}">
    <td class="sort-pointer"></td>
    <td class="ast-name-element">
        {$oGroup->name}
        <input type="hidden" name="group_id[]" value="{$oGroup->id}" />
        {if count($oGroup->itemsTree) > 0}
            <ul>
                {foreach $oGroup->itemsTree as $eKey => $oItem}
                    <li>
                        {$oItem->name}
                    </li>
                {/foreach}
            </ul>
        {/if}
    </td>
    <td class="remove-ast" onclick="deleteSelectedElement(this)"></td>
</tr>