{use class="\yii\helpers\Html"}
{use class="frontend\design\Info"}
{if $details['assigned_items']}
    <div class="promo-cart-discount">
        {$condition=''}
        {foreach $details['assigned_items'] as $type => $assigned}
            {if $type eq 'master'}
                <div class="master">
                    <div class="heading">{$smarty.const.BUYING_SOME_PRODUCTS_FROM_CATEGORIES}</div>
                    <div class="content">
                        {foreach $assigned as $items}
                            {if isset($items['product'])}
                                <div class="item">
                                    <div class="promo-min-qty">
                                        <span class="title">{$smarty.const.MINIMAL_QUANTITY}:</span>
                                        <span class="value">{$items['product']->quantity}</span>
                                    </div>
                                    <div class="image">
                                        <img src="{$items['product']->image}" alt="{$items['product']->name}">
                                    </div>
                                    <a href="{$items['product']->href}" class="name">{$items['product']->name}</a>
                                </div>
                            {else}
                                <div class="item">
                                    <div class="promo-min-qty">
                                        <span class="title">{$smarty.const.MINIMAL_QUANTITY}:</span>
                                        <span class="value">{$items['category']->quantity}</span>
                                    </div>
                                    <div class="image">
                                    {if $items['category']->image != 'no'}
                                        {Html::img($items['category']->image, ['width'=> 200, 'height'=>200])}
                                    {else}
                                        {Html::img(Info::themeSetting('na_category', 'hide'), ['width'=> 200, 'height'=>200])}
                                    {/if}
                                    </div>
                                    <a href="{$items['category']->href}" class="name">{$items['category']->name}</a>
                                </div>
                            {/if}
                        {/foreach}
                    </div>
                </div>
            {elseif $type eq 'slave'}
                <div class="slave">
                    <div class="heading">{$smarty.const.YOU_CAN_BUY_WITH_DISCOUNT}</div>
                    <div class="content">
                        {foreach $assigned as $items}
                            {if isset($items['product'])}
                                <div class="item">
                                    <div class="image">
                                        <img src="{$items['product']->image}" alt="{$items['product']->name}">
                                    </div>
                                    <a href="{$items['product']->href}" class="name">{$items['product']->name}</a>
                                    {if !empty($items['product']->condition_string)}
                                        {$condition=$items['product']->condition_string}
                                    {/if}
                                </div>
                            {else}
                                <div class="item">
                                    <div class="image">
                                        {if $items['category']->image != 'no'}
                                            {Html::img($items['category']->image, ['width'=> 200, 'height'=>200])}
                                        {else}
                                            {Html::img(Info::themeSetting('na_category', 'hide'), ['width'=> 200, 'height'=>200])}
                                        {/if}
                                    </div>
                                    <a href="{$items['category']->href}" class="name">{$items['category']->name}</a>
                                    {if !empty($items['category']->condition_string)}
                                        {$condition=$items['category']->condition_string}
                                    {/if}
                                </div>
                            {/if}
                        {/foreach}
                    </div>
                    <div class="condition">
                    <div class="condition-holder">
                        <span class="title">{$smarty.const.TEXT_DISCOUNT}:</span>
                        <span class="value">{round($condition)}%</span></div>
                </div>
                </div>
            {/if}
        {/foreach}
    </div>

{/if}