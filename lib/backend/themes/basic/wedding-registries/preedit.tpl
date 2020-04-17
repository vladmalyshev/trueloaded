<div class="or_box_head">{$model->customer_firstname}</div>
<div class="row_or"><div>{$smarty.const.TEXT_DATE_ADDED}</div><div>{\common\helpers\Date::date_short($model->create_date)}</div></div>
<div class="row_or"><div>Event Date</div><div>{\common\helpers\Date::date_short($model->event_date)}</div></div>


<div class="btn-toolbar btn-toolbar-order">
    <a href="{\yii\helpers\Url::to(['wedding-registries/edit', 'item_id' => $model->id])}" class="btn btn-primary btn-process-order">View</a>
    <button onclick="return deleteItemConfirm('{$model->id}')" class="btn btn-delete btn-no-margin btn-process-order ">{$smary.const.IMAGE_DELETE}Delete</button>
</div>


<script>
    function deleteItemConfirm( item_id) {
        $.post("wedding-registries/confirm-item-delete", { 'item_id': item_id }, function (data, status) {
            if (status == "success") {
                $('#groups_management_data .scroll_col').html(data);
                $("#groups_management").show();
                switchOnCollapse('groups_management_collapse');
            } else {
                alert("Request error.");
            }
        }, "html");
        return false;
    }
</script>