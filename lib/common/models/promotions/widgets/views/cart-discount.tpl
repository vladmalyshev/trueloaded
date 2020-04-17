{use class="\yii\helpers\Html"}
{if $details['assigned_items']}
    <div>
    {foreach $details['assigned_items'] as $type => $assigned}    
        {if $type eq 'master'}
            <div>Buying some products or products from categories</div>
            {foreach $assigned as $items}
                {if isset($items['product'])}
                    <div>
                        {$items['product']->image}
                        <a href="{$items['product']->href}">{$items['product']->name}</a>
                        <div>
                            <label>Minimal quantity: {$items['product']->quantity}</label>
                        </div>
                    </div>
                {else}
                    <div>
                        {Html::img($items['category']->image, ['width'=> 50, 'height'=>50])}
                        <a href="{$items['category']->href}">{$items['category']->name}</a>
                        <div>
                            <label>Minimal quantity: {$items['category']->quantity}</label>
                        </div>
                    </div>                    
                {/if}            
            {/foreach}
        {else if $type eq 'slave'}
            <br/>            
            <div>you can buy with discount</div>
            {foreach $assigned as $items}
                {if isset($items['product'])}
                    <div>
                        {$items['product']->image}
                        <a href="{$items['product']->href}">{$items['product']->name}</a>
                        {if !empty($items['product']->condition_string)}
                            {$items['product']->condition_string}
                        {/if}
                    </div>
                {else}
                    <div><a href="{$items['category']->href}">{$items['category']->name}</a></div>
                        {if !empty($items['category']->condition_string)}
                            {$items['category']->condition_string}
                        {/if}
                {/if}                
            {/foreach}
        {/if}    
    {/foreach}    
</div>
{/if}