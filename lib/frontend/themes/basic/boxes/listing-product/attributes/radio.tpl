<div class="radio-attributes">
    
   {* radio for please select???? <input type="radio" name="{$item.name}" value="0"{if $item.selected == ''} checked{/if}{if !Yii::$app->request->get('list_b2b')} onchange="update_attributes_list(this.form);"{/if}>*}{*<label>{$smarty.const.PLEASE_SELECT}</label> *}
    {if $smarty.const.PRODUCTS_ATTRIBUTES_SHOW_SELECT=='True'}{$smarty.const.SELECT} {/if}
    {$item.title}
    {foreach $item.options as $option}
        <label><input type="radio" name="{$options_prefix}{$item.name}" value="{$option.id}"{if $option.id==$item.selected} checked{/if}{if {strlen($option.params)} > 0} {$option.params}{/if}{if !Yii::$app->request->get('list_b2b')} onchange="update_attributes_list(this);"{/if}><span>{$option.text}</span></label>
    {/foreach}
</div>
