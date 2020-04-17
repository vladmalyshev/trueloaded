{use class="\yii\helpers\Html"}
{if $details['assigned_items']}
<div>
    <div>Buy the next products and get discount</div>
    <table border="1">
    {assign var="current_hash" value="0"}
    {assign var="iter_hash" value="0"}
    {foreach $details['assigned_items'] as $items}
        {if isset($items['product'])}
            {if !empty($items['product']->href)}
            <tr>                
                <td >
                {$items['product']->image}
                <a href="{$items['product']->href}">{$items['product']->name}</a>
                </td>                
                {if $iter_hash eq "0"}
                <td {if $items['product']->hash && $current_hash neq $items['product']->hash} rowspan="{$details['hash'][$items['product']->hash]|count}" {$current_hash=$items['product']->hash}{$iter_hash++|void}{/if}>
                    {if isset($items['product']->condition)}
                    <label>Condition:</label>
                    <div>
                    {if $items['product']->condition['amount_uf'] > 0}
                        Mininal amount : <span>{$items['product']->condition['amount']}</span>
                    {/if}
                    {if $items['product']->condition['quantity'] > 0}
                        Quantity : <span>{$items['product']->condition['quantity']}</span>
                    {/if}
                    </div>
                    <div>
                        Discount: <span>{$items['product']->condition['discount']}%</span>
                    </div>
                    {/if}
                </td>
                {else}
                {$iter_hash++|void}
                {/if}
            </tr>
            {if is_array($details['hash'][$items['product']->hash]) && $iter_hash == count($details['hash'][$items['product']->hash]) }{$iter_hash=0}{/if}
            {/if}
        {else}
            {if !empty($items['category']->href)}
            <tr>
                <td>
                From {Html::img($items['category']->image, ['width'=> 50, 'height'=>50])}
                <a href="{$items['category']->href}">{$items['category']->name}</a>
                </td>
                <td>
                    {if isset($items['category']->condition)}
                    <label>Condition:</label>
                    <div>
                        {if $items['category']->condition['amount_uf'] > 0}
                            Mininal amount : <span>{$items['category']->condition['amount']}</span>
                        {/if}
                        {if $items['category']->condition['quantity'] > 0}
                            Quantity : <span>{$items['category']->condition['quantity']}</span>
                        {/if}
                    </div>
                    <div>
                        Discount: <span>{$items['category']->condition['discount']}%</span>
                    </div>
                    {/if}
                </td>
            </tr>
            {/if}
        {/if} 
    {/foreach}    
    </table>
</div>
{/if}