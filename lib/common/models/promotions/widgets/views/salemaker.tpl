{use class="\yii\helpers\Html"}
{if $details['info']['categories']|count || $details['info']['manufacturers']|count}
    <div class="promo-salemaker">
        <div class="promo-conditions">
            {$details['info']['condition']}
        </div>

        {if $details['info']['categories']|count}
            <div class="promo-categories">
                <div class="heading">Buying products at next categories get discount</div>
                <div class="content">
                    {foreach $details['info']['categories'] as $info}
                        <div class="item">
                            <a href="{$info['link']}" class="image">{Html::img($info['image'],['width'=>50, 'height'=> 50])}</a>
                            <a href="{$info['link']}" class="name">{$info['name']}</a>
                        </div>
                    {/foreach}
                </div>
            </div>
        {/if}

        {if $details['info']['manufacturers']|count}
            <div class="promo-manufacturers">
                <div class="heading">Buying products of the next brands get discount</div>
                <div class="content">
                    {foreach $details['info']['manufacturers'] as $info}
                        <div class="item">
                            <a href="{$info['link']}" class="image">{Html::img($info['image'],['width'=>50, 'height'=> 50])}</a>
                            <a href="{$info['link']}" class="name">{$info['name']}</a>
                        </div>
                    {/foreach}
                </div>
            </div>
        {/if}
    </div>
{/if}