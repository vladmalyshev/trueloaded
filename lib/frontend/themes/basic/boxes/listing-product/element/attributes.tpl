{use class="Yii"}
{use class="frontend\design\Info"}

{if $product.product_has_attributes}

    {foreach $product.product_attributes_details.attributes_array as $iter => $item}
        {if $product.show_attributes_quantity && $item@last}

            {if count($item.options) > 0}
                <div class="mix-attributes multiattributes" data-id="{$item.id}">
                    {if $item.image}
                        <img src="{$app->request->baseUrl}/images/{$item.image}" alt="{$item.title|escape:'html'}" width="48px;">
                    {/if}
                    {if $item.color}
                    <span class="attribute-color" style="background-color: {$item.color};">&nbsp;</span>
                    {/if}
                    <div class="item-title">{$item.title}</div>
                    <div class="attribute-qty-blocks">
                    {foreach $item.options as $option}
                        {if $option['mix']}
                            <label class="attribute-qty-block" data-id="{$option.id}">

                                {if !empty($option.image)}
                                    <img src="{$app->request->baseUrl}/images/{$option.image}" alt="{$option.text|escape:'html'}"  width="48px;">
                                {/if}
                                <span{if !empty($option.color)} style="color: {$option.color};"{/if}>{$option.text}</span>

                                <div class="mult-qty-input">
                                    <div class="input">
                                        <input type="text"
                                               name="mix_qty[{$product.products_id|escape:'html'}][]"
                                               value="0"
                                               class="qty-inp"
                                               data-min = "0"
                                               data-max="{$quantity_max}"
                                               {if \common\helpers\Acl::checkExtension('OrderQuantityStep', 'setLimit')}
                                                   {\common\extensions\OrderQuantityStep\OrderQuantityStep::setLimit($order_quantity_data)}
                                               {/if} />
                                    </div>
                                </div>

                                {*\frontend\design\boxes\product\MultiQuantity::widget(['option' => $option])*}
                            </label>
                        {/if}
                    {/foreach}
                    </div>
                </div>
            {/if}

        {elseif $item['type'] == 'radio'}
            <div class="radio-attributes">
                {if $smarty.const.PRODUCTS_ATTRIBUTES_SHOW_SELECT=='True'}{$smarty.const.SELECT} {/if}
                <div class="item-title">{$item.title}</div>
                {foreach $item.options as $option}
                    <label>
                        <input type="radio"
                               name="{$options_prefix}{$item.name}"
                               value="{$option.id}"
                               {if $option.id==$item.selected} checked{/if}
                               {if strlen($option.params) > 0} {$option.params}{/if}>
                        <span>{$option.text}</span>
                    </label>
                {/foreach}
            </div>
        {else}
            <div class="select-attributes">
                <div class="item-title">{$item.title}</div>
                <select class="select"
                        name="{$options_prefix}{$item.name}"
                        data-required="{$smarty.const.PLEASE_SELECT} {$item.title}">
                    {if $smarty.const.PRODUCTS_ATTRIBUTES_SHOW_SELECT=='True'}
                        <option value="0">{$smarty.const.SELECT} {$item.title}</option>
                    {/if}
                    {foreach $item.options as $option}
                        <option value="{$option.id}"
                                {if $option.id==$item.selected} selected{/if}
                                {if strlen($option.params) > 0} {$option.params}{/if}>
                            {$option.text}
                        </option>
                    {/foreach}
                </select>
            </div>
        {/if}
    {/foreach}

{/if}