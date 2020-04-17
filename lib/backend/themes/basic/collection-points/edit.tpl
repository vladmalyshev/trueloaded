{use class="yii\helpers\Html"}
<form name="stock_indication" action="collection-points/save" method="post">
{if $collection_points_id}    
    <div class="or_box_head">{$smarty.const.TEXT_INFO_HEADING_EDIT_COLLECTION_POINTS}</div>
{else}
    <div class="or_box_head">{$smarty.const.TEXT_INFO_HEADING_NEW_COLLECTION_POINTS}</div>
{/if}
    <div class="col_desc">{$smarty.const.TEXT_INFO_EDIT_INTRO}</div>
    <div class="check_linear">
        <label>
            <span>{$smarty.const.TEXT_COLLECTION_TEXT}</span> 
            <input type="text" name="collection_points_text" value="{$odata->collection_points_text}">
        </label>
        <label>
            <span>{$smarty.const.TEXT_WAREHOUSE}</span> 
            {Html::dropDownList('warehouses_address_book_id', $odata->warehouses_address_book_id, $addresses)}
        </label>
    </div>
    <div class="btn-toolbar btn-toolbar-order">
        <input type="button" value="Update" class="btn btn-no-margin" onclick="itemSave({$collection_points_id})"><input type="button" value="Cancel" class="btn btn-cancel" onclick="resetStatement()">
    </div>
    <input type="hidden" name="collection_points_id" value="{$collection_points_id}">
</form>