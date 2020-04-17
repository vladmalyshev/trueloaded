{if $oGroup}
 <div class="or_box_head">{$oGroup->name}</div>
 <div class="btn-toolbar btn-toolbar-order">
    <a href="{$app->urlManager->createUrl('product-designer/group_relations')}?group_id={$oGroup->id}" class="btn btn-primary btn-no-margin">{$smarty.const.PRODUCTDESIGNER_BUILD_BUTTON}</a>
    <button class="btn btn-edit btn-no-margin" onclick="groupEdit({$oGroup->id})">{$smarty.const.IMAGE_EDIT}</button>
    <button class="btn btn-delete" onclick="groupDeleteConfirm({$oGroup->id})">{$smarty.const.IMAGE_DELETE}</button>
 </div>
 
  <script type="text/javascript">
  function groupDeleteConfirm(id) {
    $.post("{$app->urlManager->createUrl('product-designer/group_confirmdelete')}", { 'group_id': id }, function (data, status) {
      if (status == "success") {
        $('#suppliers_management_data .scroll_col').html(data);
      } else {
        alert("Request error.");
      }
    }, "html");
    return false;
  }
</script>
{/if}