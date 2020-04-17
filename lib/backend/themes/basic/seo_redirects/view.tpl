<div class="row_or_wrapp">
    <div class="or_box_head">
        {$smarty.const.HEADING_TITLE}    
    </div>
    {if $cInfo->seo_redirect_id}    
    <div class="row_or">
        <div class="label_name" style="text-align:left!important;">{$smarty.const.TABLE_HEADING_OLD_URL}:</div>
    </div>
    <div class="row_or">
        <div class="label_value" style="text-align:left!important;">{$cInfo->old_url}</div>
    </div>
    <div class="row_or">
        <div class="label_name" style="text-align:left!important;">{$smarty.const.TABLE_HEADING_NEW_URL}:</div>
    </div>
    <div class="row_or">
        <div class="label_value" style="text-align:left!important;">{$cInfo->new_url}</div>
    </div>
    {if $cInfo->check_details}
    <div class="row_or">
        <div class="label_name" style="text-align:left!important;">{$smarty.const.TABLE_HEADING_VALIDATED}:</div>
    </div>
    <div class="row_or">
        <div class="label_value" style="text-align:left!important;">{\common\helpers\Date::datetime_short($cInfo->last_checked)}</div>
    </div>
    <div class="row_or">
        <div class="label_value" style="text-align:left!important;">{$cInfo->check_details}</div>
    </div>
    {/if}
    {/if}
</div>
    {if $cInfo->seo_redirect_id}    
    <div class="btn-toolbar btn-toolbar-order">
			<button class="btn btn-edit btn-no-margin" onclick="edit('{$cInfo->seo_redirect_id}')">{$smarty.const.IMAGE_EDIT}</button><button class="btn btn-delete" onclick="deleteRedirect('{$cInfo->seo_redirect_id}')">{$smarty.const.IMAGE_DELETE}</button>
			<button class="btn btn-check  btn-no-margin" onclick="checkRedirect('{$cInfo->seo_redirect_id}')">{$smarty.const.IMAGE_VALIDATE}</button>
    </div>
    {/if}
