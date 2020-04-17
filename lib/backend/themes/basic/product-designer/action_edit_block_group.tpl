<form name="group" action="{$app->urlManager->createUrl('product-designer/group_save')}?group_id=1" method="post" onsubmit="return groupSave({$oGroup->id});">
    <div class="or_box_head">{if $oGroup->id == 0}Add new{else}Edit{/if} Group</div>
    <div class="main_row">
        <div class="main_title">{$smarty.const.PRODUCTDESIGNER_TEXT_GROUPS_NAME}:</div>
        <div class="main_value">
            <input name="name" value="{$oGroup->name}" class="form-control" type="text">
        </div>
    </div>
    <div class="btn-toolbar btn-toolbar-order">
        <input value="{if $oGroup->id == 0}{$smarty.const.IMAGE_NEW}{else}{$smarty.const.IMAGE_UPDATE}{/if}" class="btn btn-no-margin" type="submit">
        <input value="{$smarty.const.IMAGE_CANCEL}" class="btn btn-cancel" onclick="resetStatement({$oGroup->id})" type="button">
    </div>
</form>
    
<script type="text/javascript">
    function groupSave(id) {
        $.post("{$app->urlManager->createUrl('product-designer/group_save')}?group_id=" + id, $('form[name=group]').serialize(), function (data, status) {
          if (status == "success") {
            //$('#suppliers_management_data').html(data);
            //$("#suppliers_management").show();
            $('.alert #message_plce').html('');
            $('.alert').show().removeClass('alert-error alert-success alert-warning').addClass(data['messageType']).find('#message_plce').append(data['message']);
            resetStatement(id);
            switchOffCollapse('suppliers_list_collapse');
          } else {
            alert("Request error.");
          }
        }, "json");
        return false;
    }
</script>