
<div class="" id="payment_method">
    <div class="payment-method">

        {foreach $selection as $i}
            <div class="item payment_item payment_class_{$i.id}"  {if $i.hide_row} style="display: none"{/if}>
                {if isset($i.methods)}
                    {foreach $i.methods as $m}
                        <div class="item-radio">
                            <label>
                                <input type="radio" name="payment" value="{$m.id}"{if $i.hide_input} style="display: none"{/if}{if $m.checked} checked{/if}/>
                                <span>{$m.module}</span>
                            </label>
                        </div>
                    {/foreach}
                {else}
                    <div class="item-radio">
                        <label>
                            <input type="radio" name="payment" value="{$i.id}"{if $i.hide_input} style="display: none"{/if}{if $i.checked} checked{/if}/>
                            <span>{$i.module}</span>
                        </label>
                    </div>
                {/if}
                {foreach $i.fields as $j}
                    <div class="sub-item">
                        <label>
                            <span>{$j.title}</span>
                            {$j.field}
                        </label>
                    </div>
                {/foreach}
            </div>
        {/foreach}
    </div>
</div>