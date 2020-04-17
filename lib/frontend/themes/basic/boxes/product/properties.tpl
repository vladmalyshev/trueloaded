<div class="product-properties">
  {if $products_data.manufacturers_name && $settings.show_manufacturer != 'no'}
  <ul class="properties-table">
    <li class="propertiesName"><strong class="propertiesName-strong">{$smarty.const.TEXT_MANUFACTURER}</strong></li>
    <li class="propertiesValue">
      {if $products_data.manufacturers_link}
      <a href="{$products_data.manufacturers_link}" title="{$products_data.manufacturers_name|escape:'html'}"><span itemprop="brand">{$products_data.manufacturers_name}</span></a>
      {else}
      <span class="propertiesBrand" itemprop="brand">{$products_data.manufacturers_name}</span>
      {/if}
    </li>
  </ul>
  {/if}

  {if $settings.show_sku != 'no'}
  <ul class="properties-table js_prop-block{if !$products_data.products_model} js-hide{/if}">
    <li class="propertiesName"><strong class="propertiesName-strong">{$smarty.const.TEXT_MODEL}</strong></li>
    <li class="propertiesValue js_prop-products_model" itemprop="sku">{$products_data.products_model}</li>
  </ul>
  {/if}

  {if $products_data.products_ean && $settings.show_ean != 'no'}
    <ul class="properties-table{if !$products_data.products_ean} js-hide{/if}">
      <li class="propertiesName js_prop-block"><strong class="propertiesName-strong">{$smarty.const.TEXT_EAN}</strong></li>
      <li class="propertiesValue js_prop-products_ean" itemprop="gtin8">{$products_data.products_ean}</li>
    </ul>
  {/if}
  {if $products_data.products_isbn && $settings.show_isbn != 'no'}
    <ul class="properties-table{if !$products_data.products_isbn} js-hide{/if}">
      <li class="propertiesName js_prop-block"><strong class="propertiesName-strong">{$smarty.const.TEXT_ISBN}</strong></li>
      <li class="propertiesValue js_prop-products_isbn" itemprop="isbn">{$products_data.products_isbn}</li>
    </ul>
  {/if}
  {if $products_data.products_upc && $settings.show_upc != 'no'}
    <ul class="properties-table{if !$products_data.products_upc} js-hide{/if}">
      <li class="propertiesName js_prop-block"><strong class="propertiesName-strong">{$smarty.const.TEXT_UPC}</strong></li>
      <li class="propertiesValue js_prop-products_upc">{$products_data.products_upc}</li>
    </ul>
  {/if}
{if {$properties_tree_array|@count} > 0}

  <div itemprop="additionalProperty" itemscope itemtype="http://schema.org/PropertyValue">
{foreach $properties_tree_array as $key => $property}
  <ul id="property-{$property['properties_id']}" class="property-ul {$property['properties_type']}" itemprop="value" itemscope itemtype="http://schema.org/PropertyValue">
    <li class="level-{count(explode('.', $property['throughoutID']))} {$property['properties_type']}">
      <strong class="propertiesName-strong">
          {if !empty($property['properties_image'])}<img src="{$app->request->baseUrl}/images/{$property['properties_image']}" alt="{$property['properties_name']}" width="48px;">{/if}
        <span{if !empty($property['properties_color'])} style="color: {$property['properties_color']};"{/if} class="propertiesName-span" itemprop="name">{$property['properties_name']}</span>
      </strong>
    </li>
    <li class="level-{count(explode('.', $property['throughoutID']))} {$property['properties_type']}">
    {if {$property['values']|@count} > 0}
      {foreach $property['values'] as $value_id => $value}
        <div class="sel_pr_values">
          {if !empty($property['images'][$value_id])}<img src="{$app->request->baseUrl}/images/{$property['images'][$value_id]}" alt="{$value}" width="48px;">{/if}<span{if !empty($property['colors'][$value_id])} style="color: {$property['colors'][$value_id]};"{/if} class="propertiesValue-span" id="value-{$value_id}" itemprop="value">{$value}{if isset($property['extra_values'][$value_id])} {$property['extra_values'][$value_id]}{/if}</span>
        </div>
      {/foreach}
    {/if}
    </li>
  </ul>
{/foreach}
  </div>

{/if}
</div>
