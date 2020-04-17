<form name="item" action=".{$app->urlManager->createUrl('product-designer/item_delete')}?item_id={$oItem->id}" method="post" id="item_delete" onsubmit="return itemDelete({$oItem->id});">
    <div class="or_box_head">{$smarty.const.PRODUCTDESIGNER_TEXT_INFO_HEADING_DELETE_ITEM}</div>
    {$smarty.const.PRODUCTDESIGNER_TEXT_INFO_DELETE_INTRO}
    <br><br><b>{$oItem->name}</b>
    <div class="btn-toolbar btn-toolbar-order">
        <button type="submit" class="btn btn-primary btn-no-margin">{$smarty.const.IMAGE_CONFIRM}</button>
        <button class="btn btn-cancel" onclick="return resetStatement({$oItem->id})">{$smarty.const.IMAGE_CANCEL}</button>
    </div>
</form>
        
<script type="text/javascript">
  function itemDelete() {
      $.post("{$app->urlManager->createUrl('product-designer/item_delete')}?item_id={$oItem->id}", $('#item_delete').serialize(), function (data, status) {
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