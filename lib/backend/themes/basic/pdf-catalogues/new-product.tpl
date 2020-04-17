<tr role="row" prefix="pdf-catalogue-box-{$pdf_catalogue['products_id']}" class="{$pdf_catalogue['status_class']}">
    <td class="ast-img-pdf-catalogue img-ast-img">
        {$pdf_catalogue['image']}
    </td>
    <td class="ast-name-pdf-catalogue">
        {$pdf_catalogue['products_name']} ({$pdf_catalogue['price']})
        <input type="hidden" name="pdf_catalogue_products_id[]" value="{$pdf_catalogue['products_id']}" />
    </td>
    <td class="ast-model-pdf-catalogue">
        {$pdf_catalogue['products_model']}
    </td>
    <td class="remove-ast" onclick="deleteSelectedPDFCatalogue(this)"></td>
</tr>