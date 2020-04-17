
<div>
  {foreach $quotes as $shipping_quote_item}
    <div class="item" {if isset($shipping_quote_item.hide_row) && $shipping_quote_item.hide_row}style="display: none;"{/if}>
      <div class="title">{$shipping_quote_item.module}</div>
      {if $shipping_quote_item.error}
        <div class="error">{$shipping_quote_item.error}</div>
      {else}
        {foreach $shipping_quote_item.methods as $shipping_quote_item_method}
          <label class="row">
            {if $quotes_radio_buttons>0}
              <div class="input"><input value="{$shipping_quote_item_method.code}" {if $shipping_quote_item_method.selected}checked="checked"{/if} type="radio" name="shipping"/></div>
            {else}
              <input value="{$shipping_quote_item_method.code}" type="hidden" name="shipping"/>
            {/if}
            <div class="cost">{$shipping_quote_item_method.cost_f}</div>
            <div class="sub-title">{$shipping_quote_item_method.title}</div>
          </label>
        {/foreach}
      {/if}
    </div>
  {/foreach}

</div>
