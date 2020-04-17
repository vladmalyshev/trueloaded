<div class="or_box_head">{$affiliate_firstname} {$affiliate_lastname}</div>
<div class="btn-toolbar btn-toolbar-order">
    <a class="btn btn-edit btn-no-margin" href="{$app->urlManager->createUrl(['affiliate/edit', 'affiliate_id' => $affiliate_id])}">{$smarty.const.IMAGE_EDIT}</a><button class="btn btn-delete" onclick="deleteItemConfirm({$affiliate_id})">{$smarty.const.IMAGE_DELETE}</button>
    {if $platform_id == 0}
    <button class="btn btn-primary btn-process-order" onclick="itemActivate({$affiliate_id})">{$smarty.const.IMAGE_ACTIVATE}</button>
    {else}
    <a class="btn btn-primary btn-process-order" href="{$app->urlManager->createUrl(['platforms/edit', 'id' => $platform_id])}">{$smarty.const.IMAGE_VIEW}</a>
    {/if}
</div>