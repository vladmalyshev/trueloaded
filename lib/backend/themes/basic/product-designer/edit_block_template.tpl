{if $oTemplate}
 <div class="or_box_head">{$oTemplate->name}</div>
 <div class="btn-toolbar btn-toolbar-order">
    <a href="{$app->urlManager->createUrl('product-designer/template_relations')}?template_id={$oTemplate->id}" class="btn btn-primary btn-no-margin">{$smarty.const.PRODUCTDESIGNER_BUILD_BUTTON}</a>
    <button class="btn btn-edit btn-no-margin" onclick="templateEdit({$oTemplate->id})">{$smarty.const.IMAGE_EDIT}</button>
    <button class="btn btn-delete" onclick="templateDeleteConfirm({$oTemplate->id})">{$smarty.const.IMAGE_DELETE}</button>
 </div>
 
 <script type="text/javascript">
  function templateDeleteConfirm(id) {
    $.post("{$app->urlManager->createUrl('product-designer/template_confirmdelete')}", { 'template_id': id }, function (data, status) {
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