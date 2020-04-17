<div class="shipping-estimator">
  {\frontend\design\Info::addBoxToCss('form')}
  <div class="heading-2">{$smarty.const.SHIPPING_OPTIONS}</div>

  {if not $is_logged_customer}
  <div class="info">{sprintf($smarty.const.PLEASE_LOG_IN, tep_href_link('account/login', '', 'SSL'))}</div>
  {/if}


  <div class="left-area">
    {if $is_logged_customer}
    <div class="heading-3">{$smarty.const.SHIPPING_ADDRESS}</div>
    <div class="addresses" id="shipping-addresses">
      {foreach $addresses_array as $address_item }
      <div class="address-item active">
        <label>
          <input class="js-ship-estimate" type="radio" name="estimate[sendto]" value="{$address_item.id}" {if $address_item.id==$addresses_selected_value} checked="checked"{/if}>
          <span>{$address_item.text}</span>
        </label>
      </div>
      {/foreach}
    </div>
    {else}
    <div class="heading-3">{$smarty.const.ESTIMATE_SHIPPING}</div>

    <div class="estimate-shipping form-inputs">

      <div class="col-full">
        <label>
          <span>{field_label const="COUNTRY" required_text=""}</span>
          <select name="estimate[country_id]">
            {foreach $countries as $country}
              <option value="{$country.countries_id}"{if $country.countries_id == $estimate_country} selected{/if}>{$country.countries_name}</option>
            {/foreach}
          </select>
        </label>
      </div>
      <div class="col-left">
        <label>
          <span>{field_label const="ENTRY_POST_CODE" required_text=""}</span>
          <input type="text" name="estimate[post_code]" value="{$estimate_postcode|escape:'html'}"/>
        </label>
      </div>
      <div class="col-right">
        <span>&nbsp;</span><br><span class="btn js-ship-estimate">{$smarty.const.RECALCULATE}</span>
      </div>

    </div>
    {/if}

  </div>
  <div class="right-area">

    <div class="heading-3"><div class="right-text"><strong>{$smarty.const.WEIGHT}</strong> {$cart_weight}Kgs</div>{$smarty.const.SHIPPING_METHOD}</div>

    <div class="shipping-method">
      {foreach $samples as $shipping_sample_item}
        <div class="item">
          <div class="title">{$shipping_sample_item.module}</div>
          {if $shipping_sample_item.error}
            {*<div class="error">{$shipping_sample_item.error}</div>*}
          {else}
            {foreach $shipping_sample_item.methods as $shipping_sample_item_method}
              <label class="row">
                {if $samples_radio_buttons>0}
                  <div class="input"><input class="js-ship-estimate" value="{$shipping_sample_item_method.code}" {if $shipping_sample_item_method.selected}checked="checked"{/if} type="radio" name="estimate[shipping]"/></div>
                {else}
                  <input value="{$shipping_sample_item_method.code}" type="hidden" name="estimate[shipping]"/>
                {/if}
                <div class="cost">{$shipping_sample_item_method.cost_f}</div>
                <div class="sub-title">{$shipping_sample_item_method.title}</div>
              </label>
            {/foreach}
          {/if}
        </div>
      {/foreach}

    </div>

  </div>
</div>
