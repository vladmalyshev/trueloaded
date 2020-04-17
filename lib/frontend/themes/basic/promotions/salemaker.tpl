{use class="\yii\helpers\Html"}
{use class="frontend\design\Info"}
{if is_array($details['info']['categories']) && $details['info']['categories']|count || is_array($details['info']['manufacturers']) && $details['info']['manufacturers']|count}
    <div class="promo-salemaker">

        {if is_array($details['info']['categories']) && $details['info']['categories']|count}
            <div class="promo-categories">
                <div class="promo-conditions">
                    {$smarty.const.BUYING_PRODUCTS_AT_CATEGORIES}
                </div>
                <div class="discount">
                    <span class="title">{$smarty.const.TEXT_DISCOUNT}:</span>
                    <span class="value">{$details['info']['condition']}</span>
                </div>
                <div class="content">
                    {foreach $details['info']['categories'] as $info}
                        <div class="item">
                            {if $info['image']}
                                <a href="{$info['link']}" class="image">{Html::img($info['image'],['width'=>200, 'height'=> 200])}</a>
                            {else}
                                <a href="{$info['link']}" class="image">{Html::img(Info::themeSetting('na_category', 'hide'), ['width'=> 200, 'height'=>200])}</a>
                            {/if}

                            <a href="{$info['link']}" class="name">{$info['name']}</a>
                        </div>
                    {/foreach}
                </div>
            </div>
        {/if}

        {if is_array($details['info']['manufacturers']) && $details['info']['manufacturers']|count}
            <div class="promo-manufacturers">
                <div class="heading">{$smarty.const.BUYING_PRODUCTS_AT_BRANDS}</div>
                <div class="discount">
                    <span class="title">{$smarty.const.TEXT_DISCOUNT}:</span>
                    <span class="value">{$details['info']['condition']}</span>
                </div>
                <div class="content">
                    {foreach $details['info']['manufacturers'] as $info}
                        <div class="item">
                            {if $info['image']}
                                <a href="{$info['link']}" class="image">{Html::img($info['image'],['width'=>150, 'height'=> 150])}</a>
                            {else}
                                <a href="{$info['link']}" class="image">{Html::img(Info::themeSetting('na_category', 'hide'), ['width'=> 200, 'height'=>200])}</a>
                            {/if}
                            <a href="{$info['link']}" class="name">{$info['name']}</a>
                        </div>
                    {/foreach}
                </div>
            </div>
        {/if}
    </div>
{/if}
