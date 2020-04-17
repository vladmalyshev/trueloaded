<form name="item" action="{$app->urlManager->createUrl('product-designer/item_save')}?item_id=1" method="post" onsubmit="return itemSave({$oItem->id});">
    <div class="or_box_head">{if $oItem->id == 0}Add new{else}Edit{/if} Item</div>
    <div class="main_row">
        <div class="main_title">Item Name:</div>
        <div class="main_value">
            <input name="name" value="{$oItem->name}" class="form-control" type="text">
        </div>

        <div class="main_title">Item Placeholder:</div>
        <div class="main_value">
            <input name="placeholder" value="{$oItem->placeholder}" class="form-control" type="text">
        </div>
        
        <div class="main_title">Item type:</div>
        <div class="main_value">
            <select name="type">
                {foreach \common\extensions\ProductDesigner\models\ProductDesignerItem::$type as $key=>$type}
                    <option {if $oItem->type == $key}selected="selected"{/if} value="{$key}">{$type}</option>
                {/foreach}
            </select>
        </div>
            
        <div class="main_title">Item field name:</div>
        <div class="main_value">
            <input name="field_name" value="{$oItem->field_name}" class="form-control" type="text">
        </div>
        
        <div class="main_title">Item default value:</div>
        <div class="main_value">
            <input name="default_value" value="{$oItem->default_value}" class="form-control" type="text">
        </div>
        
        <div class="main_title">Item font color:</div>
        <div class="main_value">
            <select name="font_color">
                {foreach \common\extensions\ProductDesigner\models\ProductDesignerItem::$color as $key=>$color}
                    <option {if $oItem->font_color == $key}selected="selected"{/if} value="{$key}">{$color}</option>
                {/foreach}
            </select>
        </div>
        
        <div class="main_title">Item font size (px):</div>
        <div class="main_value">
            <input name="font_size" value="{if $oItem->id == 0}16{else}{$oItem->font_size}{/if}" class="form-control" type="text">
        </div>
        
        <div class="main_title">Item padding top (px):</div>
        <div class="main_value">
            <input name="padding_top" value="{if $oItem->id == 0}0{else}{$oItem->padding_top}{/if}" class="form-control" type="text">
        </div>
        
        <div class="main_title">Item padding bottom (px):</div>
        <div class="main_value">
            <input name="padding_bot" value="{if $oItem->id == 0}0{else}{$oItem->padding_bot}{/if}" class="form-control" type="text">
        </div>

        <div class="main_title">Uppercase text (only for input-text):</div>
        <div class="main_value">
            <input name="uppercase" {if $oItem->id == 0}{else}checked="checked"{/if} value="1" class="check_bot_switch_on_off" type="checkbox">
        </div>

        <div class="main_title">Max symbols (only for input-text):</div>
        <div class="main_value">
            <input name="max_symbols" value="{if $oItem->id == 0}{else}{$oItem->max_symbols}{/if}" class="form-control" type="text">
        </div>
        
        <div class="main_title">+Cost:</div>
        <div class="main_value">
            <input name="price" value="{if $oItem->id == 0}0.00{else}{$oItem->price}{/if}" class="form-control" type="text">
        </div>

        <div class="main_title">Item background color:</div>
        <div class="main_value">
            <select name="bgcolor">
                <option value="">--none--</option>
                {foreach \common\extensions\ProductDesigner\models\ProductDesignerItem::$color as $key=>$color}
                    <option {if $oItem->bgcolor == $color}selected="selected"{/if} value="{$color}">{$color}</option>
                {/foreach}
            </select>
        </div>
    </div>
    <div class="btn-toolbar btn-toolbar-order">
        <input value="{if $oItem->id == 0}{$smarty.const.IMAGE_NEW}{else}{$smarty.const.IMAGE_UPDATE}{/if}" class="btn btn-no-margin" type="submit">
        <input value="{$smarty.const.IMAGE_CANCEL}" class="btn btn-cancel" onclick="resetStatement({$oItem->id})" type="button">
    </div>
</form>
    
<script type="text/javascript">
    function itemSave(id) {
        $.post("{$app->urlManager->createUrl('product-designer/item_save')}?item_id=" + id, $('form[name=item]').serialize(), function (data, status) {
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
    $(".check_bot_switch_on_off").bootstrapSwitch(
        {
            onText: "ON",
            offText: "OFF",
            handleWidth: '20px',
            labelWidth: '24px'
        }
    );
</script>