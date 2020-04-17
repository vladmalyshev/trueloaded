<form name="pdf_catalogue" action="{Yii::$app->urlManager->createUrl('pdf-catalogues/save')}" method="post" onsubmit="return pdf_catalogueCreate();">
<div class="popup-heading">{$smarty.const.IMAGE_NEW_PDF_CATALOGUE}</div>
<div class="popup-content fields_style">
    <div class="main_row">{$smarty.const.TEXT_NEW_INTRO}</div>
    <div class="main_row"><div class="main_title">{$smarty.const.TEXT_INFO_PDF_CATALOGUES_NAME}</div><div class="main_value"><input name="pdf_catalogues_name" type="text" required></div></div>
    <div class="main_row"><div class="check_linear"><label>{tep_draw_checkbox_field('show_out_of_stock', '1', false)}<span>{$smarty.const.TEXT_SHOW_OUT_OF_STOCK}</span><label></div></div>
    <div class="main_row"><div class="check_linear"><label>{tep_draw_checkbox_field('show_product_link', '1', false)}<span>{$smarty.const.TEXT_SHOW_PRODUCT_LINK}</span><label></div></div>
</div>
<div class="noti-btn">
    <div class="btn-left">
        <a href="javascript:void(0);" class="btn btn-cancel" id="btnAssignCatalogCancel">{$smarty.const.IMAGE_CANCEL}</a>
    </div>
    <div class="btn-right">
        <button type="submit" class="btn btn-confirm" id="btnAssignCatalog">{$smarty.const.IMAGE_NEW}</button>
    </div>
</div>
</form>
<script type="text/javascript">
function pdf_catalogueCreate() {
    $.post('{Yii::$app->urlManager->createUrl('pdf-catalogues/save')}', $('form[name=pdf_catalogue]').serialize(), function(data, status) {
        if (status == 'success') {
            resetStatement();
            $.get('{Yii::$app->urlManager->createUrl('pdf-catalogues/edit-catalog')}', { pdf_catalogues_id: data['id'] }, function(data2, status2) {
                if (status2 == 'success') {
                    $('.popupNewPdfCatalogue').find('.pop-up-content').html(data2);
                }
            });
        } else {
            alert("Request error.");
        }
    },"json");
    return false;    
}
</script>