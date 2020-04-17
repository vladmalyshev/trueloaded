{use class="\common\helpers\Html"}
<tr>
    <td>{if $discountRO}{$discountData['quantity_from']}{else}{Html::textInput('suppliers_data['|cat:$supplier_idx|cat:'][discount_table]['|cat:$row_idx|cat:'][quantity_from]', $discountData['quantity_from'], ['class'=>'form-control'])}{/if}</td>
    <td>{if $discountRO}{$discountData['quantity_to']}{else}{Html::textInput('suppliers_data['|cat:$supplier_idx|cat:'][discount_table]['|cat:$row_idx|cat:'][quantity_to]', $discountData['quantity_to'], ['class'=>'form-control'])}{/if}</td>
    <td>{if $discountRO}{$discountData['supplier_discount']}{else}{Html::textInput('suppliers_data['|cat:$supplier_idx|cat:'][discount_table]['|cat:$row_idx|cat:'][supplier_discount]', $discountData['supplier_discount'], ['class'=>'form-control'])}{/if}</td>
    {if not $discountRO}<td><i class="icon-trash color-alert js-qdt-remove-row"></i></td>{/if}
</tr>