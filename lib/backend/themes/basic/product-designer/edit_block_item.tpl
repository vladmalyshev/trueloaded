 {if $oItem}
 <div class="or_box_head">{$oItem->name}</div>
{* {foreach $oItem->getAttributes(null,['id', 'name']) as $name=>$val}
    <div class="row_or">
        <div>{$name}:</div>
        <div>{$val}</div>
    </div>
{/foreach}*}
 <div class="btn-toolbar btn-toolbar-order">
{*     <a href="{$app->urlManager->createUrl('product-designer/item_tune')}?item_id={$oItem->id}" class="btn btn-primary btn-no-margin">{$smarty.const.PRODUCTDESIGNER_TUNE_ITEM}</a>*}
    <button class="btn btn-edit btn-no-margin" onclick="itemEdit({$oItem->id})">{$smarty.const.IMAGE_EDIT}</button>
    <button class="btn btn-delete" onclick="itemDeleteConfirm({$oItem->id})">{$smarty.const.IMAGE_DELETE}</button>
 </div>
 
  <script type="text/javascript">
  function itemDeleteConfirm(id) {
    $.post("{$app->urlManager->createUrl('product-designer/item_confirmdelete')}", { 'item_id': id }, function (data, status) {
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