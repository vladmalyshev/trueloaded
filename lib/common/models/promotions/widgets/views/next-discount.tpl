{use class="\yii\helpers\Html"}
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
                        {if !empty($items['product']->condition_string)}
                            <label>For each {$items['product']->quantity} {$items['product']->name} {$items['product']->condition_string}</label>
                        {/if}
                        </div>
                    <div>
                {else}
                    <div>
                        {Html::img($items['category']->image, ['width'=> 50, 'height'=>50])}
                        <a href="{$items['category']->href}">{$items['category']->name}</a>
                        <div>
                        {if !empty($items['category']->condition_string)}
                            <label>For each {$items['category']->quantity} product in this category {$items['category']->condition_string}</label>
                        {/if}
                        </div>
                    <div>                    
                {/if}
            {/foreach}
        {/if}    
    {/foreach}    
</div>
<hr/>