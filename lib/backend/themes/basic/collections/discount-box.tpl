{if $collections_products_count > 1}
<table width="100%" border="0">
  <tr>
    <td width=50%" style="vertical-align:top;">
      <div class="widget box box-no-shadow">
        <div class="widget-header"><label><input type="radio" name="collections_type" value="0"{if $collections_type == '0'} checked{/if}> {$smarty.const.TEXT_DISCOUNT_COLLECTION}</label></div>
        <div class="widget-content">
        {foreach $discount_collection_array as $discount_collection}
          <div class="edp-line">
            <label>{sprintf($smarty.const.TEXT_DISCOUNT_FOR_X_ITEMS, $discount_collection['products_count'])}</label> <input type="text" name="collections_discount[{$discount_collection['products_count']}]"  value="{$discount_collection['discount']}" class="form-control form-control-small">
          </div>
        {/foreach}
        </div>
      </div>
    </td>
    <td width=50%" style="vertical-align:top;">
      <div class="widget box box-no-shadow">
        <div class="widget-header"><label><input type="radio" name="collections_type" value="1"{if $collections_type == '1'} checked{/if}> {$smarty.const.TEXT_PRICE_COLLECTION}</label></div>
        <div class="widget-content">
        {foreach $price_collection_array as $i => $price_collection}
          <div class="edp-line">
            <label>{sprintf($smarty.const.TEXT_COLLECTION_X, ($i+1))}</label> {foreach $price_collection['products'] as $product}{$product['name']} ({$product['price_format']})<br>{/foreach}
          </div>
          <div class="edp-line">
              <label>{sprintf($smarty.const.TEXT_PRICE_FOR_COLLECTION_X, ($i+1))}</label> <input type="text" name="collections_price[{$price_collection['products_set']}]" value="{$price_collection['price']}" class="form-control form-control-small" placeholder="{$price_collection['price_format']}">
          </div>
        {/foreach}
        </div>
      </div>
    </td>
  </tr>
</table>
{/if}