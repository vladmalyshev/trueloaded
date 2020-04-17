<form name="forbidden" action="{$app->urlManager->createUrl('product-designer/forbidden_save')}?forbidden_id=1" method="post" onsubmit="return forbiddenSave({$forbidden_id});">
    <div class="or_box_head">{if $forbidden_id == 0}Add new{else}Edit{/if} Template</div>
    <div class="main_row">
        <div class="main_title"></div>
        <div class="main_value">
            <input name="name" value="{$word}" class="form-control" type="text">
        </div>
    </div>
    <div class="btn-toolbar btn-toolbar-order">
        <input value="{if $forbidden_id == 0}{$smarty.const.IMAGE_NEW}{else}{$smarty.const.IMAGE_UPDATE}{/if}" class="btn btn-no-margin" type="submit">
        <input value="{$smarty.const.IMAGE_CANCEL}" class="btn btn-no-margin btn-cancel" onclick="resetStatement({$forbidden_id})" type="button">
    </div>
</form>
