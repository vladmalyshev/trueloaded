{use class="yii\helpers\Html"}
<div class="or_box_head">{$smarty.const.TEXT_HEADING_DELETE_GROUP}</div>
{Html::beginForm(['wedding-registries/delete', 'id' => $model->id], 'post', ['enctype' => 'multipart/form-data', 'id' => 'item_delete', 'onsubmit' => "return deleteItem({$model->id});"])}
<div class="row_fields">{$smarty.const.TEXT_DELETE_INTRO}</div>
<div class="row_fields"><b>{$model->id}</b></div>
<div class="btn-toolbar btn-toolbar-order" style="width: 100%; text-align: center;">
    <button class="btn btn-delete btn-no-margin" style="display: inline-block; width: 40%;">{$smarty.const.IMAGE_DELETE}</button>
    <input type="button" class="btn btn-cancel" style="display: inline-block; width: 40%;" value="{$smarty.const.IMAGE_CANCEL}" onClick="return cancelStatement()">
</div>
{Html::hiddenInput ( 'item_id', $model->id, [] )}
{Html::endForm()}

<script>

    function deleteItem(item_id) {
        $.post("wedding-registries/delete", $('#item_delete').serialize(), function (data, status) {
            if (status == "success") {
                resetStatement();
                $('#groups_management_data .scroll_col').html("");
                switchOffCollapse('groups_management_collapse');
            } else {
                alert("Request error.");
            }
        }, "html");

        return false;
    }

    function cancelStatement() {
        $(".order-wrap").show();
        $("#groups_management").hide();
        $("#customers_management").hide();
        var item_id = $('.table tbody tr.selected').find('input.cell_identify').val();
        $.post("wedding-registries/itempreedit", { 'item_id': item_id }, function(data, status){
            if (status == "success") {
                $('#groups_management_data .scroll_col').html(data);
                deleteScroll();
                heightColumn();
            } else {
                alert("Request error.");
            }
        },"html");

    }
</script>