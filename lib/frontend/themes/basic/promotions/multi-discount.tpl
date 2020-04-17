{use class="\yii\helpers\Html"}
{if $details['assigned_items']}
    <div class="prom-multi-discount">
        <div class="heading">{$smarty.const.BUY_NEXT_PRODUCTS_GET_DISCOUNT}</div>
        <div class="list">
            {assign var="current_hash" value="0"}
            {assign var="iter_hash" value="0"}
            {foreach $details['assigned_items'] as $items}
                {if isset($items['product'])}

                    {if $iter_hash eq "0"}
                        {$count = $details['hash'][$items['product']->hash]|count}
                        <div  class="promo-group promo-group-{if $count < 5}{$count}{else}4{/if}">

                            {if $items['product']->hash && $current_hash neq $items['product']->hash}
                                {$iter_hash++|void}
                            {/if}

                            {if isset($items['product']->condition)}
                                <div class="promo-range">
                                    {if $items['product']->condition['amount_uf'] > 0}
                                        <div class="promo-min-amount"><span class="title">{$smarty.const.TEXT_MINIMAL_AMOUNT}:</span> <span class="value">{$items['product']->condition['amount']}</span></div>
                                    {/if}
                                    {if $items['product']->condition['quantity'] > 0}
                                        <div class="promo-prod-qty"><span class="title">{$smarty.const.TEXT_BY_QUANTITY}:</span> <span class="value">{$items['product']->condition['quantity']}</span></div>
                                    {/if}
                                    <div class="promo-discount">
                                        <sapn class="title">{$smarty.const.TEXT_DISCOUNT}:</sapn> <span class="value">{round($items['product']->condition['discount'])}%</span>
                                    </div>
                                </div>
                                <div class="products">
                            {/if}
                    {else}
                        {$iter_hash++|void}
                    {/if}


                        <div class="promo-product">
                            <div class="image">
                                <img src="{$items['product']->image}" alt="{$items['product']->name}">
                            </div>
                            <a href="{$items['product']->href}" class="name">{$items['product']->name}</a>
                        </div>

                    {if $iter_hash == $details['hash'][$items['product']->hash]|count}
                            </div>
                        </div>
                    {/if}


                    {if is_array($details['hash'][$items['product']->hash]) && $iter_hash == count($details['hash'][$items['product']->hash]) }
                        {$iter_hash=0}
                    {/if}
                {else}
                    <div class="promo-group promo-category">
                        <div class="promo-range">
                            {if isset($items['category']->condition)}
                                <div class="promo-min-amount">
                                    <span class="title">{$smarty.const.TEXT_MINIMAL_AMOUNT}:</span> <span class="value">{$items['category']->condition['amount']}</span>
                                </div>
                                <div class="promo-discount">
                                    <span class="title">{$smarty.const.TEXT_DISCOUNT}:</span> <span class="value">{round($items['category']->condition['discount'])}%</span>
                                </div>
                            {/if}
                        </div>
                        <div class="promo-category-item">
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
    </div>
{/if}