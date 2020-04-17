<form name="template" action="{$app->urlManager->createUrl('product-designer/template_delete')}?template_id={$oTemplate->id}" method="post" id="template_delete" onsubmit="return templateDelete({$oTemplate->id});">
    <div class="or_box_head">{$smarty.const.PRODUCTDESIGNER_TEXT_HEADING_DELETE}</div>
    {$smarty.const.PRODUCTDESIGNER_TEXT_DELETE_INTRO}
    <br><br><b>{$oTemplate->name}</b>
    <div class="btn-toolbar btn-toolbar-order">
        <button type="submit" class="btn btn-primary btn-no-margin">{$smarty.const.IMAGE_CONFIRM}</button>
        <button class="btn btn-cancel" onclick="return resetStatement({$oTemplate->id})">{$smarty.const.IMAGE_CANCEL}</button>
    </div>
</form>
        
<script type="text/javascript">
  function templateDelete() {
      $.post("{$app->urlManager->createUrl('product-designer/template_delete')}?template_id={$oTemplate->id}", $('#template_delete').serialize(), function (data, status) {
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