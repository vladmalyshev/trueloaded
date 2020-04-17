<div class="or_box_head">Copy Attributes to another product</div>
<form name="products" action="" method="post" id="products_attr_copy" onSubmit="return copyProductAttr();">
    <div class="col_desc">Copying Attributes from #{$pInfo->products_id}</div>
    <div class="col_desc">{$pInfo->products_name}</div>
    <div class="col_desc">Copying Attributes to #</div>        
    <div class="col_desc">{tep_draw_input_field('copy_to_products_id', $copy_to_products_id, 'size="3"')}</div>
    <div class="col_desc">Delete ALL Attributes before copying {tep_draw_checkbox_field('copy_attributes_delete_first', '1', $copy_attributes_delete_first, 'size="2"')}</div>
    <div class="col_desc">Skip Duplicated Attributes {tep_draw_checkbox_field('copy_attributes_duplicates_skipped', '1')}</div>
    <div class="btn-toolbar btn-toolbar-order">
        <button class="btn btn-move btn-no-margin">{$smarty.const.IMAGE_MOVE}</button><button class="btn btn-cancel" onClick="return resetStatement()">{$smarty.const.IMAGE_CANCEL}</button>
        <input type="hidden" name="products_id" value="{$pInfo->products_id}">
        <input type="hidden" name="products_name" value="{$pInfo->products_name}">
    </div>
</form>