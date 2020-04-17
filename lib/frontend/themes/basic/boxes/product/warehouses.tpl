{\frontend\design\Info::addBoxToCss('warehouses-popup')}

<a href="#warehouses" class="warehouses-popup-link" data-class="warehouses-popup warehouses-popu-{if $warehousesStock|count < 5}{$warehousesStock|count}{else}4{/if}">{$smarty.const.CHECK_STORE_AVAILABILITY}</a>

<div id="warehouses" style="display: none">
  <div class="heading">
    <span>{$smarty.const.AVAILABLE_AT_WAREHOUSES}</span>
  </div>
  <div class="content">
    {foreach $warehousesStock as $ws }
        <div class="warehouse-item">
              <div class="warehouse row">
                <span class="warehouse-name">{$ws.name}</span>
              </div>
              {if $settings[0].show_address}
              <div class="warehouse-address row">
                <span>{$ws.address}</span>
              </div>
              {/if}
              {if $settings[0].show_time}
              <div class="warehouse-time row">
                <span>{$ws.time}</span>
              </div>
              {/if}
              {if $settings[0].show_qty}
                {if $settings[0].show_as_levels}
                  {if $settings[0].show_qty_level1 >= $ws.qty }
                    <div class="warehouse-qty-level-low">
                      <span>{$smarty.const.AVAILABLE_AT_WAREHOUSES_LEVEL_LOW}</span>
                    </div>
                  {elseif $settings[0].show_qty_level2 <= $ws.qty }
                    <div class="warehouse-qty-level-avg">
                      <span>{$smarty.const.AVAILABLE_AT_WAREHOUSES_LEVEL_MEDIUM}</span>
                    </div>
                  {else}
                    <div class="warehouse-qty-level-high">
                      <span>{$smarty.const.AVAILABLE_AT_WAREHOUSES_LEVEL_HIGH}</span>
                    </div>
                  {/if}
                {else}
                  {if intval($settings[0].show_qty_less) == 0 || intval($settings[0].show_qty_less) > $ws.quantity }
                      <div class="warehouse-qty-less">
                          <span>{$smarty.const.TEXT_AVAILABLE}:</span> <span>{$ws.quantity}</span>
                      </div>
                  {else}
                      <div class="warehouse-qty-less">
                        <span>{sprintf(AVAILABLE_AT_WAREHOUSES_QTY_HIGH, $settings[0].show_qty_less)}</span>
                      </div>
                  {/if}
                {/if}
              {/if}
        </div>
    {/foreach}
  </div>
</div>

<script>
    tl('{\frontend\design\Info::themeFile('/js/main.js')}', function(){
        $('.warehouses-popup-link').popUp()
    })
</script>