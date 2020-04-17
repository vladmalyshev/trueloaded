<form name="type" action="{$app->urlManager->createUrl('product-designer/type-save')}?item_id=1" method="post" onsubmit="return typeSave();">
    <div class="or_box_head">Add new item type</div>
    <div class="main_row">
        <p>
            <small>{$smarty.const.PRODUCTDESIGNER_TEXT_NOTE2TYPE}</small>
        </p>
        <div class="main_title">Type Name:</div>
        <div class="main_value">
            <input name="name" value="" class="form-control" type="text">
        </div>
        
    </div>
    <div class="btn-toolbar btn-toolbar-order">
        <input value="{if $oItem->id == 0}{$smarty.const.IMAGE_NEW}{else}{$smarty.const.IMAGE_UPDATE}{/if}" class="btn btn-no-margin" type="submit">
        <input value="{$smarty.const.IMAGE_CANCEL}" class="btn btn-cancel" onclick="resetStatement({$oItem->id})" type="button">
    </div>
</form>
    
<script type="text/javascript">
    function typeSave() {
        $.post("{$app->urlManager->createUrl('product-designer/type-save')}", $('form[name=type]').serialize(), function (data, status) {
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