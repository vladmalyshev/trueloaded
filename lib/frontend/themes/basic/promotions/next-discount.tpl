{use class="\yii\helpers\Html"}
{use class="frontend\design\Info"}
<div class="promo-next-discount">
    {foreach $details['assigned_items'] as $type => $assigned}
        <div class="item">

            {if $type eq 'master'}
                <div class="heading">
                    {$smarty.const.BUYING_SOME_PRODUCTS_FROM_CATEGORIES}
                    <span class="promo-deduction">{round($details['promo_deduction'])}%</span>
                </div>
                <div class="content">
                    {foreach $assigned as $items}
                        {if isset($items['product'])}
                            <div class="item promo-is-product">
                                {if !empty($items['product']->condition_string)}
                                    <div class="promo-for-each">
                                        <span class="title">{$smarty.const.TEXT_FOR_EACH}</span>
                                        <span class="value">{$items['product']->quantity}</span>
                                    </div>
                                {/if}
                                <div class="item-holder">
                                    <div class="image">
                                    {if $items['product']->image}
                                        <img src="{$items['product']->image}" alt="">
                                    {/if}
                                    </div>
                                    <a href="{$items['product']->href}" class="name">{$items['product']->name}</a>
                                </div>
                            </div>
                        {else}
                            <div class="item promo-category">
                                {if !empty($items['category']->condition_string)}
                                    <div class="promo-for-each">
                                        <span class="title">{$smarty.const.TEXT_FOR_EACH}</span>
                                        <span class="value">{$items['category']->quantity}</span>
                                    </div>
                                {/if}
                                <div class="item-holder">
                                    <div class="image">
                                        {if $items['category']->image}
                                            {Html::img($items['category']->image, ['width'=> 200, 'height'=>200])}
                                        {else}
                                            {Html::img(Info::themeSetting('na_category', 'hide'), ['width'=> 200, 'height'=>200])}
                                        {/if}
                                    </div>
                                    <a href="{$items['category']->href}" class="name">{$items['category']->name}</a>
                                </div>
                            </div>
                        {/if}
                    {/foreach}
                </div>
            {/if}
        </div>
    {/foreach}
</div>