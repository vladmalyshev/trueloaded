<div class="radio-attributes">
    {if !empty($item.image)}<img src="{$app->request->baseUrl}/images/{$item.image}" alt="{$item.title|escape:'html'}" width="48px;">{/if}
    {if !empty($item.color)}<span style="color: {$item.color};">{/if}
    {$item.title}
    {if !empty($item.color)}</span>{/if}
    {*<input type="radio" name="{$item.name}" value="0"{if $item.selected == ''} checked{/if}{if !Yii::$app->request->get('list_b2b')} onchange="update_attributes(this.form);"{/if}><label>{$smarty.const.PLEASE_SELECT}</label>*}
    {foreach $item.options as $option}
        <label>
            <input type="radio" name="{$item.name}" value="{$option.id}"{if $option.id==$item.selected} checked{/if}{if {strlen($option.params)} > 0} {$option.params}{/if}{if !Yii::$app->request->get('list_b2b')} onchange="update_attributes(this.form);"{/if}>
            {if !empty($option.image)}<img src="{$app->request->baseUrl}/images/{$option.image}" alt="{$option.text|escape:'html'}"  width="48px;">{/if}
            <span{if !empty($option.color)} style="color: {$option.color};"{/if}>{$option.text}</span>
        </label>
    {/foreach}
</div>
