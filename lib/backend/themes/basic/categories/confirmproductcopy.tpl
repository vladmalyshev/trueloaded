<div class="or_box_head">{$smarty.const.TEXT_INFO_HEADING_COPY_TO}</div>
<form name="products" action="" method="post" id="products_copy" onSubmit="return copyProduct();">
    <div class="col_title">{$smarty.const.TEXT_INFO_COPY_TO_INTRO}</div>
    <div class="col_desc">{$smarty.const.TEXT_INFO_CURRENT_CATEGORIES}</div>
    <div class="col_desc">{\common\helpers\Categories::output_generated_category_path($pInfo->products_id, 'product')}</div>        
    <div class="col_desc">{$smarty.const.TEXT_CATEGORIES}</div>
    <div class="col_desc">{tep_draw_pull_down_menu('categories_id', \common\helpers\Categories::get_category_tree(), $pInfo->categories_id)}</div>
    <div class="col_desc">{$smarty.const.TEXT_HOW_TO_COPY}</div>
    <div class="col_desc">{tep_draw_radio_field('copy_as', 'link', true)} {$smarty.const.TEXT_COPY_AS_LINK}</div>
    <div class="col_desc">{tep_draw_radio_field('copy_as', 'duplicate')} {$smarty.const.TEXT_COPY_AS_DUPLICATE}</div>
{if \common\helpers\Attributes::has_product_attributes($pInfo->products_id, true)}    
    <div class="col_desc">{$smarty.const.TEXT_COPY_ATTRIBUTES_ONLY}</div>
    <div class="col_desc">{$smarty.const.TEXT_COPY_ATTRIBUTES}</div>
    <div class="col_desc">{tep_draw_radio_field('copy_attributes', 'copy_attributes_yes', true)} {$smarty.const.TEXT_COPY_ATTRIBUTES_YES}</div>
    <div class="col_desc">{tep_draw_radio_field('copy_attributes', 'copy_attributes_no')} {$smarty.const.TEXT_COPY_ATTRIBUTES_NO}</div>
    
{/if}
    <div class="btn-toolbar btn-toolbar-order">
        <button class="btn btn-move btn-no-margin">{$smarty.const.IMAGE_MOVE}</button><button class="btn btn-cancel" onClick="return resetStatement()">{$smarty.const.IMAGE_CANCEL}</button>
        <input type="hidden" name="products_id" value="{$pInfo->products_id}">
        <input type="hidden" name="from_categories_id" value="{$pInfo->categories_id}">
    </div>
</form>