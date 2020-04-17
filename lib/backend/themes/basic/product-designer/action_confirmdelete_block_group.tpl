<form name="group" action="{$app->urlManager->createUrl('product-designer/group_delete')}?group_id={$oGroup->id}" method="post" id="group_delete" onsubmit="return groupDelete({$oGroup->id});">
    <div class="or_box_head">{$smarty.const.PRODUCTDESIGNER_TEXT_HEADING_DELETE_GROUP}</div>
    {$smarty.const.PRODUCTDESIGNER_TEXT_DELETE_INTRO_GROUP}
    <br><br><b>{$oGroup->name}</b>
    <div class="btn-toolbar btn-toolbar-order">
        <button type="submit" class="btn btn-primary btn-no-margin">{$smarty.const.IMAGE_CONFIRM}</button>
        <button class="btn btn-cancel" onclick="return resetStatement({$oGroup->id})">{$smarty.const.IMAGE_CANCEL}</button>
    </div>
</form>
        
<script type="text/javascript">
  function groupDelete() {
      $.post("{$app->urlManager->createUrl('product-designer/group_delete')}?group_id={$oGroup->id}", $('#group_delete').serialize(), function (data, status) {
        if (status == "success") {
          //$('.alert #message_plce').html('');
          //$('.alert').show().removeClass('alert-error alert-success alert-warning').addClass(data['messageType']).find('#message_plce').append(data['message']);
          if (data == 'reset') {
            resetStatement();
          } else {
            $('#suppliers_management_data .scroll_col').html(data);
            $("#suppliers_management").show();
          }
          switchOnCollapse('suppliers_list_collapse');
        } else {
          alert("Request error.");
        }
      }, "html");
    return false;
  }
</script>