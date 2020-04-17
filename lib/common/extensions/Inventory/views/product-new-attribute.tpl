{*
This file is part of True Loaded.

@link http://www.holbi.co.uk
@copyright Copyright (c) 2005 Holbi Group LTD

For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
*}

{function newOptionValue }
  {foreach $options as $option}
{$option['products_options_id']=$products_options_id} {** mnogo raznogo koda mlya *}
  <tr role="row" class="js-option-value" data-option_value_id="{$option['products_options_values_id']}">
      <td class="sort-pointer"></td>
        {if \common\helpers\Acl::checkExtension('AttributesImages', 'productBlock')}
          {\common\extensions\AttributesImages\AttributesImages::productBlock($option, $option)}
        {else}
            <td class="img-ast dis_module">
                <div id="AdminSettns" class="int-upload">
                    <select class="divselktr divselktr-{$option['products_options_id']}" disabled />
                </div>
            </td>
        {/if}
      <td class="name-ast">
          {$option['products_options_values_name']}
          <input type="hidden" name="products_attributes_id[{$products_options_id}][{$option['products_options_values_id']}]" id="products_attributes_id_{$products_options_id}_{$option['products_options_values_id']}" value="{$option['products_attributes_id']}" />
          <input type="hidden" name="price_prefix[{$products_options_id}][{$option['products_options_values_id']}]" value="+">
      </td>
      <td class="set-ditails text-center"><input type="checkbox" class="js-option-default-group" data-option-group="{$option['products_options_id']}" name="default_option_value[{$option['products_options_id']}][{$option['products_options_values_id']}]" value="1" {if $option['default_option_value']}checked{/if}></td>
      <td class="ast-price ast-price-net one-attribute">
          {$smarty.const.TEXT_NET}<br>
          <span class="inventory-price-net-{$products_id}-{$products_options_id}-{$option['products_options_values_id']}" id="attr_list_price-{$products_id}-{$products_options_id}-{$option['products_options_values_id']}">{$option['price_prefix']}{$option['net_price_formatted']}</span>
      </td>
      <td class="ast-price ast-price-gross one-attribute">
          {$smarty.const.TEXT_GROSS}<br>
          <span class="inventory-price-gross-{$products_id}-{$products_options_id}-{$option['products_options_values_id']}" id="attr_list_price_gross-{$products_id}-{$products_options_id}-{$option['products_options_values_id']}">{$option['price_prefix']}{$option['gross_price_formatted']}</span>
      </td>
      <td class="set-ditails one-attribute">
          <a href="#id-{$products_id}-{$products_options_id}-{$option['products_options_values_id']}" class="btn inventory-popup-link">{$smarty.const.SET_UP_DETAILS}</a>
      </td>
      <td class="set-ditails-down more-attributes">
          {$smarty.const.SET_UP_INVENTORY_DETAILS_BELOW}
      </td>
      <td class="remove-ast" onclick="deleteSelectedAttribute(this)"></td>
  </tr>
  {/foreach}
  {if {!$isIncluded} }
  {if !defined('ADMIN_TOO_MANY_IMAGES') || (is_array($app->controller->view->images) && $app->controller->view->images|@count < intval(ADMIN_TOO_MANY_IMAGES))}
  <script type="text/javascript">
  $(document).ready(function() {
      $('.divselktr-{$option['products_options_id']}').multiselect({
          multiple: true,
          height: '205px',
          header: 'See the images in the rows below:',
          noneSelectedText: 'Select',
          selectedText: function(numChecked, numTotal, checkedItems){
            return numChecked + ' of ' + numTotal;
          },
          selectedList: false,
          show: ['blind', 200],
          hide: ['fade', 200],
          position: {
              my: 'left top',
              at: 'left bottom'
          }
      });
  });
  </script>
  {/if}
  {/if}
{/function}

{if {!isset($productNewAttributeIncluded)} || {$productNewAttributeIncluded!==true} }
  {call newOptionValue options=$options products_id=$products_id products_options_id=$products_options_id isIncluded=false }
{/if}