{include file="{Yii::getAlias('@backend/themes/basic')}/assets/tabs.tpl"}
{use class="yii\helpers\Html"}
{use class="common\helpers\Suppliers"}

{if isset($inventory_filter_list) && $inventory_filter_list|@count > 0 }
<div>
{foreach $inventory_filter_list as $option}
  <div style="float:left;padding:5px;">
    <label>{$option.name}:</label>
    {Html::dropDownList('inventory_filter['|cat:$option.id|cat:']', $option.selected, $option.values, ['multiple' => 'multiple', 'data-role' => 'multiselect', 'style' => 'width:150px;'])}
  </div>
{/foreach}
  <div style="float:left;">
    <span class="btn btn-primary" onclick="updateInventoryBox();">{$smarty.const.TEXT_SEARCH}</span>
    <span class="btn" onclick="return resetInventoryFilter();">{$smarty.const.TEXT_RESET}</span>
  </div>
</div>
{/if}

<table class="table table-bordered inventory-table" id="id-inventory-table">
  <thead>
    <tr>
      <th>{$smarty.const.TEXT_IMG}</th>
      <th>{$smarty.const.TEXT_COMBINATION}</th>
      <th colspan="2" class="inventory-price-title">{$smarty.const.TEXT_PRICE}</th>
      <th>&nbsp;</th>
      <th align="center">{$smarty.const.TEXT_NON_EXISTEN}</th>
    </tr>
  </thead>
  <tbody>

{foreach $inventories as $ikey=>$inventory}
{$upridSuffix = str_replace(['{', '}'], ['-', '-'], $inventory.uprid)}
  <tr class="js-inventory-row">
      {if \common\helpers\Acl::checkExtension('InventortyImages', 'productBlock')}
	{\common\extensions\InventortyImages\InventortyImages::productBlock($inventory)}
      {else}
      <td class="dis_module">
      <div id="AdminSettns" class="int-upload img-ast">
        <select class="divselktr divselktr-inventory" disabled />
      </div>
    </td>
      {/if}
    <td>
      {foreach $inventory['options'] as $option}
        {$option['label']}: <strong>{$option['value']}</strong>,
      {/foreach}

      <div class="popup-box-wrap-page popup-box-wrap-page-1" style="display: none" id="id-{$upridSuffix}">
        <div class="around-pop-up-page"></div>
        <div class="popup-box-page">
          <div class="pop-up-close-page"></div>
          <div class="pop-up-content-page">
            <div class="popup-heading">
              {$smarty.const.SET_UP_DETAILS}&nbsp;-&nbsp;{$inventory.variant_name}
            </div>
            <div class="popup-content">
    <div class="widget-content">
              <div class="tabbable tabbable-custom">
                <ul class="nav nav-tabs">
                  <li class="inventory-additional-price active"><a href="#tab_{$upridSuffix}-1" data-toggle="tab"><span>{$smarty.const.TEXT_PRICE}</span></a></li>
                  <li><a href="#tab_{$upridSuffix}-3" id="invStockTab{$upridSuffix}" data-uprid="{$upridSuffix}" data-toggle="tab"><span>{$smarty.const.TEXT_STOCK}</span></a></li>
                  <li><a href="#tab_{$upridSuffix}-4" data-toggle="tab"><span>{$smarty.const.IMAGE_DETAILS}</span></a></li>
                  <li><a href="#tab_{$upridSuffix}-5" id="invSupplierTab{$upridSuffix}" data-uprid="{$upridSuffix}" data-toggle="tab"><span>{$smarty.const.TEXT_SUPPLIER_COST}</span></a></li>
                </ul>
                <div class="tab-content tab-content-popup">
                  <div class="tab-pane tab-prices active" id="tab_{$upridSuffix}-1">

                      <div class="tax-cl inventory-price-data" style="padding: 0 9px">
                          <label>{$smarty.const.TEXT_PRODUCTS_TAX_CLASS} <input type="checkbox" style="display: none" {if !is_null($inventory.inventory_tax_class_id)} checked{/if} onclick="(function(checkBox){ var $sel = $(checkBox).parent().parent().find('.js-inventory-tax-class'); if (checkBox.checked) { $sel.removeAttr('disabled'); }else{ $sel.val('{$inventory.products_tax_class_id}'); $sel.trigger('change'); $sel.attr('disabled','disabled'); } })(this)"><i class="icon-pencil color-hilite"></i></label>
                          {if is_null($inventory.inventory_tax_class_id)}
                          {Html::dropDownList('inventory_tax_class_id_'|cat:$inventory.uprid|escape, $inventory.products_tax_class_id, $app->controller->view->tax_classes, ['onchange'=>'updateGrossVisible(\''|cat:$inventory.uprid|cat:'\')', 'data-rel-id'=>$upridSuffix, 'class'=>'form-control js-inventory-tax-class', 'disabled'=>'disabled'])}
                          {else}
                          {Html::dropDownList('inventory_tax_class_id_'|cat:$inventory.uprid|escape, $inventory.inventory_tax_class_id, $app->controller->view->tax_classes, ['onchange'=>'updateGrossVisible(\''|cat:$inventory.uprid|cat:'\')', 'data-rel-id'=>$upridSuffix, 'class'=>'form-control js-inventory-tax-class'])}
                          {/if}
                      </div>

  {if isset($app->controller->view->price_tabs) && $app->controller->view->price_tabs|@count > 0 }
    {$tabparams = $app->controller->view->price_tabparams}
    {$tabparams[count($tabparams)-1]['callback'] = 'inventoryPriceBlock'}
    {$id_prefix = "invPrice{$upridSuffix}"}
    {call mTab tabs=$app->controller->view->price_tabs tabparams=$tabparams  fieldsData=$inventory['price_tabs_data']  id_prefix = $id_prefix}

  {else}
    {call inventoryPriceBlock data=$inventory['price_tabs_data'] id_prefix = 'invPrice'}

  {/if}
                  </div>

{function inventoryPriceBlock}
{* $data: [ name => val], $fieldSuffix: '[1][0]'  $idSuffix: '-1-0' *}
{$idSuffix="-`$upridSuffix``$idSuffix`"}
{$fieldSuffix="`$inventory.uprid``$fieldSuffix`"}
    <div id="group_price_container{$idSuffix}" class="js_inventory_group_price js_group_price inventory-price-data" data-base_price="{$data['base_price']|escape}" data-group_discount="{$data['tabdata']['groups_discount']}" data-currencies-id="{$data['currencies_id']}" data-uprid_suffix="{$upridSuffix}">
{* workaround for switchers: group on/off, additional/full price (fullprice default is -2) *}
{if $smarty.const.CUSTOMERS_GROUPS_ENABLE != 'True' || $data['groups_id']==0}
  {if {$data['products_group_price']<0} }
    {$data['products_group_price']=0}
    {$data['products_group_price_gross']=0}
  {/if}
  {if {$data['products_group_special_price']<0} }
    {$data['products_group_special_price']=0}
    {$data['products_group_special_price_gross']=0}
  {/if}
{/if}
{if {$data['groups_id']}>0 }
  {if !isset($data['products_group_price']) || $data['products_group_price']==''}
    {$data['products_group_price']=-2}
  {/if}
        <div class="our-pr-line after">
          {*<div class="switch-toggle switch-3 switch-candy">*}
            <label for="iopt{$idSuffix}_m2"><input type="radio" class="price-options" id="iopt{$idSuffix}_m2" value="-2" {if {round($data['products_group_price'])}==-2}checked{/if} data-idSuffix="{$idSuffix}"/>{$smarty.const.TEXT_PRICE_SWITCH_MAIN_PRICE}</label>
            <label for="iopt{$idSuffix}_m1"><input type="radio" class="price-options" id="iopt{$idSuffix}_m1" value="1" {if {round($data['products_group_price'])}>=0}checked{/if} data-idSuffix="{$idSuffix}"/>{sprintf($smarty.const.TEXT_PRICE_SWITCH_OWN_PRICE, $data['tabdata']['title'])}</label>
            <label for="iopt{$idSuffix}_m0"><input type="radio" class="price-options" id="iopt{$idSuffix}_m0" value="-1" {if {round($data['products_group_price'])}==-1}checked{/if} data-idSuffix="{$idSuffix}"/>{sprintf($smarty.const.TEXT_PRICE_SWITCH_DISABLE, $data['tabdata']['title'])}</label>
          {*</div>*}
        </div>
{else}
  {if !$products_price_full}
      <div class="our-pr-line after">
        <label for="invPricePrefix{$idSuffix}">{$smarty.const.TEXT_PRICE_PREFIX}</label>
        <select id="invPricePrefix{$idSuffix}" name="inventorypriceprefix_{$fieldSuffix|escape}" class="form-control{if ($smarty.const.CUSTOMERS_GROUPS_ENABLE != 'True' || $data['groups_id']==0) && ($app->controller->view->useMarketPrices != true || $default_currency['id']==$data['currencies_id'])} default_currency{/if}">
          <option value="+" {if $data['price_prefix']=='+'}selected{/if}>+</option>
          <option value="-" {if $data['price_prefix']=='-'}selected{/if}>-</option>
        </select>
      </div>
  {/if}
{/if}
      <div id="div_wrap_hide{$idSuffix}" {if {round($data['products_group_price'])}==-1}style="display:none;"{/if}>
<!-- main price -->
        <div class="our-pr-line after">
          <div>
            <label>{$smarty.const.TEXT_NET_PRICE}</label>
            <input id="products_group_price{$idSuffix}" name="products_group_price_{$fieldSuffix|escape}" value='{$data['products_group_price']|escape}' onKeyUp="updateGrossPrice(this);" data-roundTo="{$data['round_to']}" class="form-control{if ($smarty.const.CUSTOMERS_GROUPS_ENABLE != 'True' || $data['groups_id']==0) && ($app->controller->view->useMarketPrices != true || $default_currency['id']==$data['currencies_id'])} default_currency{/if}" {if {round($data['products_group_price'])}==-2}style="display:none;"{/if}/>
{if {$data['groups_id']}>0 }
            <span id="span_products_group_price{$idSuffix}" class="form-control-span"{if {round($data['products_group_price'])}>=0}style="display:none;"{/if}>{$currencies->formatById($data['base_price']*((100-$data['tabdata']['groups_discount'])/100), false, $data['currencies_id'])|escape}</span>
{/if}
          </div>
            <div>
                <div>
                    <label>{$smarty.const.TEXT_GROSS_PRICE}</label>
                    <input id="products_group_price_gross{$idSuffix}" value='{$data['products_group_price_gross']|escape}' onKeyUp="updateNetPrice(this);" class="form-control{if ($smarty.const.CUSTOMERS_GROUPS_ENABLE != 'True' || $data['groups_id']==0) && ($app->controller->view->useMarketPrices != true || $default_currency['id']==$data['currencies_id'])} default_currency{/if}"{if {round($data['products_group_price'])}==-2}style="display:none;"{/if}/>
                    {if {$data['groups_id']}>0 }
                        <span id="span_products_group_price_gross{$idSuffix}" class="form-control-span"{if {round($data['products_group_price'])}>=0}style="display:none;"{/if}>{$currencies->formatById($data['base_price_gross']*((100-$data['tabdata']['groups_discount'])/100), false, $data['currencies_id'])|escape}</span>
                    {/if}
                </div>
            </div>
        </div>
            {if ($smarty.const.CUSTOMERS_GROUPS_ENABLE != 'True' || $data['groups_id']==0) && ($app->controller->view->useMarketPrices != true || $default_currency['id']==$data['currencies_id'])}
          <div class="our-pr-line dfullcheck after">
                {* supplier price is caclulated for default currency only*}
                <div class="supplier-price-cost disable-btn is-not-bundle">
                    <a href="javascript:void(0)" class="btn" onclick="return chooseSupplierPrice('products_group_price{$idSuffix}')" {if {round($data['products_group_price'])}==-2}style="display:none;"{/if}>{$smarty.const.TEXT_PRICE_COST}</a>
                    <div class="pull-right">
                        <label>
                            {$smarty.const.TEXT_PRICE_BASED_ON_SUPPLIER_AUTO}
                            <input type="checkbox" value="1" id="supplier_auto_price{$idSuffix}" name="supplier_auto_price_{$fieldSuffix|escape}" class="check_supplier_price_mode" {if ((is_null($data['supplier_price_manual']) and SUPPLIER_UPDATE_PRICE_MODE=='Auto') or (!is_null($data['supplier_price_manual']) and $data['supplier_price_manual']==0))}  checked="checked"{/if} />
                        </label>
                    </div>
                </div>
          </div>
            {/if}
<!-- q-ty discount -->
        <div class="our-pr-line after our-pr-line-check-box dfullcheck">
          <div>
            <label>{$smarty.const.TEXT_QUANTITY_DISCOUNT}</label>
            {* always - else imposible to set up per group without discount to all *}
              {*{$dataToSwitch=$idSuffix}  inventory *}
              <input type="checkbox" value="1" name="qty_discount_status{$fieldSuffix|escape}" data-toswitch="prod_qty_discount{$idSuffix}" class="check_qty_discount_prod" id="check_qty_discount_prod{$idSuffix}" {if $data['qty_discount_status']} checked="checked" {/if} />
          </div>
        </div>
        <div id="hide_wrap_price_qty_discount{$idSuffix}" class="prod_qty_discount{$idSuffix}" {if !$data['qty_discount_status']}style="display:none;"{/if}>
          <div id="wrap_price_qty_discount{$idSuffix}" class="wrap-quant-discount">
              {foreach $data['qty_discounts'] as $qty => $prices}
                {call inventoryQtyDiscountRow }
              {/foreach}
          </div>
          <div class="quant-discount-line after div_qty_discount_inv" {if isset($data['qty_discounts']) && $data['qty_discounts']|@count > 0} style="display:none;" {/if}>
            {$smarty.const.PLEASE_SET_UP_DISCOUNT_QUANTITIES}
          </div>
        </div>
      </div>
    </div>
{/function}

{function  inventoryQtyDiscountRow}{strip}
              <div class="quant-discount-line after div_qty_discount_prod">
                <div>
                  <label>{$smarty.const.TEXT_PRODUCTS_QUANTITY_INFO}</label>
                  <span class="form-control-span">{$qty}</span>
                </div>
                <div>
                  <label>{$smarty.const.TEXT_NET}</label>
                  <input id="discount_price{$idSuffix}_{$prices@iteration}" name="discount_price{$fieldSuffix|escape}[{$qty|escape}]" value="{$prices['price']}" onKeyUp="updateGrossPrice(this);" data-roundTo="{$data['round_to']}" class="form-control"/>
                </div>
                <div>
                  <label>{$smarty.const.TEXT_GROSS}</label>
                  <input id="discount_price_gross{$idSuffix}_{$prices@iteration}" value="{$prices['price_gross']}" onKeyUp="updateNetPrice(this);" class="form-control"/>
                </div>
                <span class="rem-quan-line"></span>
              </div>
{/strip}{/function}


                  <div class="tab-pane" id="tab_{$upridSuffix}-3" data-ikey="{$ikey}" >
                      {if $TabAccess->isSubProduct()}
                          {include file="stock-tab-subproduct.tpl"}
                      {else}
                          {include file="stock-tab.tpl"}
                      {/if}
                  </div>

                {if \common\helpers\Acl::checkExtension('AttributesDetails', 'productBlock')}
                 {\common\extensions\AttributesDetails\AttributesDetails::productBlock($inventory)}
                {else}
                  <div class="tab-pane dis_module" id="tab_{$upridSuffix}-4">
                    <div class="t-row">
                      <div class="t-col-2">
                        <label>{$smarty.const.TEXT_MODEL_SKU}</label>
                        <input class="form-control" type="text" disabled>
                      </div>
                      <div class="t-col-2">
                        <label>{$smarty.const.TEXT_WEIGHT}</label>
                        <input class="form-control" type="text" disabled>
                      </div>
                    </div>
                    <div class="t-row">
                      <div class="t-col-2">
                        <label>{$smarty.const.TEXT_UPC}</label>
                        <input class="form-control" type="text" disabled>
                      </div>
                      <div class="t-col-2">
                        <label>{$smarty.const.TEXT_EAN}</label>
                        <input class="form-control" type="text" disabled>
                      </div>
                    </div>
                    <div class="t-row">
                      <div class="t-col-2">
                        <label>{$smarty.const.TEXT_ASIN}</label>
                        <input class="form-control" type="text" disabled>
                      </div>
                      <div class="t-col-2">
                        <label>{$smarty.const.TEXT_ISBN}</label>
                        <input class="form-control" type="text" disabled>
                      </div>
                    </div>
                  </div>
                {/if}


                  <div class="tab-pane" id="tab_{$upridSuffix}-5">

                    <div class="widget-content{if not $TabAccess->allowSuppliersData()} disabled_block{/if}">
                        <div id="suppliers-placeholder{$upridSuffix}">
{if isset($inventory['suppliers']) && $inventory['suppliers']|@count > 0}
    {foreach $inventory['suppliers'] as $suppliers_id => $supplier}
                      {include file="supplierinventory.tpl" sInfo=$supplier uprid=$inventory.uprid}
    {/foreach}
{/if}
                        </div>
                      <div class="ed-sup-btn-box">
                        <a href="{Yii::$app->urlManager->createUrl(['categories/supplier-select', 'uprid' => $inventory.uprid])}" class="btn select_supplier_inv">{$smarty.const.TEXT_SELECT_ADD_SUPPLIER}</a>
                      </div>
                    </div>

                  </div>

                </div>
              </div>

            </div>
            </div>
            <div class="popup-buttons">
              <span class="btn btn-primary btn-save2" data-upridsuffix="-{$upridSuffix}">{$smarty.const.IMAGE_UPDATE}</span>
              <span class="btn btn-cancel">{$smarty.const.IMAGE_CANCEL}</span>
            </div>
          </div>
        </div>
      </div>
    </td>
    <td>
      {$smarty.const.TEXT_NET}<br>
      <span class="inventory-price-net-{$upridSuffix}" id="inv_list_price-{$upridSuffix}">{$inventory.net_price_formatted}</span>
    </td>
    <td>
      {$smarty.const.TEXT_GROSS}<br>
      <span class="inventory-price-gross-{$upridSuffix}" id="inv_list_price_gross-{$upridSuffix}">{$inventory.gross_price_formatted}</span>
    </td>
    <td><a href="#id-{$upridSuffix}" class="btn inventory-popup-link">{$smarty.const.SET_UP_DETAILS}</a></td>
    <td align="center"><label>{$inventory['inventoryexistent']}</label></td>
  </tr>

{/foreach}

  </tbody>
</table>

{if \common\helpers\Acl::checkExtension('InventortyImages', 'productBlock3')}
    {\common\extensions\InventortyImages\InventortyImages::productBlock3($inventories)}
{else}
<script type="text/tltpl" id="new_image_inventory">
{foreach $inventories as $ikey=>$inventory}
  <div><label><input type="checkbox" disabled class="uniform"/> {$inventory.variant_name}</label></div>
{/foreach}
</script>
{/if}
<script type="text/javascript">
{foreach $inventories as $ikey=>$inventory}
var total_stock_{$ikey} = {$inventory['products_quantity']};
var allocated_stock_{$ikey} = [];
{/foreach}
/*$(function(){


  updateGross();
});*/

function resetInventoryFilter() {
  $("select[name^='inventory_filter']").multipleSelect('uncheckAll');
  updateInventoryBox();
}

$(document).ready(function() {

  $("select[name^='inventory_filter']").multipleSelect({
    multiple: true,
    filter: true,
  });

  changeSPIStatus = function(target, status) {

    $.each($(target).parents('.js-supplier-product').find('input, select'), function(i, el){
        $(el).attr('readonly', (status?false:'readonly'));
    });
  }

  initBTiSattus = function ($items){
    $($items).bootstrapSwitch({
      onText: "{$smarty.const.SW_ON}",
      offText: "{$smarty.const.SW_OFF}",
      handleWidth: '20px',
      labelWidth: '24px',
      onSwitchChange: function (e, status) {
        changeSPIStatus(e.target, status);
        if ($('.popup-content:visible [name^="suppliers_id_"]:not([readonly])').size() == 0){
            changeSPIStatus(e.target, true);
            $(this).bootstrapSwitch('state', true);
        }
      }
  })
  }

//  initBTiSattus($('.supplier-product-status'));

  function invSupplierTabsShown(clicked='') {
    var el = $(this).attr('href');
    if (typeof(el) === 'undefined' && clicked !== '') {
      el = clicked;
    }

    // init new visible bootstrapSwitch
    tab = $(el).not(".inited");
    if (tab.length) {
      tab.addClass('inited');

      //$('.supplier-product-status', tab)
      initBTiSattus($('.supplier-product-status', tab));
    }
  }

var allocated_stock_info  = [];
{foreach $inventories as $ikey=>$inventory}
    {foreach $inventory.platformStockList as $platform}
    allocated_stock_{$ikey}[{$platform.id}] = {$platform.qty};

    if (allocated_stock_info['{$ikey}_{$platform.id}']) {
    } else {
      allocated_stock_info['{$ikey}_{$platform.id}']= {
        'value': {$platform.qty},
        'max': {$inventory['products_quantity']}
      };
    }

    {/foreach}
{/foreach}

  function initSliderRange( iKey, platformId) {

    $( '#slider-range-' + iKey + '-' + platformId ).slider({
            range: 'min',
            value: allocated_stock_info[iKey + '_' + platformId].value,
            min: 0,
            max: allocated_stock_info[iKey + '_' + platformId].max,
            slide: function( event, ui ) {
              var iKey = $(this).attr('data-ikey');
              var platformId = $(this).attr('data-platform-id');
              var totalStock = $(this).attr('data-total-stock');
              var uprid = $(this).attr('data-uprid');

                eval('allocated_stock_' + iKey + '[' + platformId + '] = ' + ui.value +';');
                //allocated_stock_{$ikey}[{$platform.id}] = ui.value;
                $( '#slider-range-qty-' + iKey + '-' + platformId ).text( ui.value );
                var iStockTotal = 0; // allocated
                var $aQtySpans = $( 'span[id^="slider-range-qty-' + iKey + '-"]' );

                if ($aQtySpans.length) {
                  for (var i=0;i<$aQtySpans.length;i++) {
                    qty = 0;

                    try {
                      qty = parseInt($($aQtySpans[i]).text());
                    } catch (e) { }
                    iStockTotal += qty;
                  }
                }

                $qtyTotal = $('#slider-range-qty-total-' + iKey);
                $qtyTotal.text(iStockTotal + ' from ' + totalStock);
                if ( (totalStock - iStockTotal) < 0 ) {
                    $qtyTotal.css('color', 'red');
                } else {
                    $qtyTotal.css('color', 'green');
                }
                $('input[name="platform_to_qty_' + uprid + '_' + platformId +'"]').val(ui.value);
            }
    });
    $('#slider-range-qty-' + iKey + '-' + platformId ).text($('#slider-range-' + iKey + '-' + platformId).slider('value'));
  }

  function invStockTabsShown(clicked='') {

    var el = $(this).attr('href');
    if (typeof(el) === 'undefined' && clicked !== '') {
      el = clicked;
    }

    // init new visible bootstrapSwitch
    tab = $(el).not(".inited");
    if (tab.length) {
      tab.addClass('inited');
    var iKey = $(tab).attr('data-ikey');

    $( 'div[id^="slider-range-' + iKey + '-"]' , tab).each(function () {
      var iKey = $(this).attr('data-ikey');
      var platformId = $(this).attr('data-platform-id');
      initSliderRange(iKey, platformId);
    });

    }
  }

  $('a[id^="invSupplierTab"][data-toggle="tab"]').on('shown.bs.tab', invSupplierTabsShown);

  $('a[id^="invStockTab"][data-toggle="tab"]').on('shown.bs.tab', invStockTabsShown);

  $('ul[id^="invPrice"] a[data-toggle="tab"]').on('shown.bs.tab', invPriceTabsShown);
  //edit product - disable q-ty input on the product level if inventory is available
{*  $('.edp-qty-t').show();
    $('.edp-qty-update').hide();
{foreach $inventories as $ikey=>$inventory}
{foreachelse}
    $('.edp-qty-t').hide();
    $('.edp-qty-update').show();
{/foreach}*}
    {if isset($inventories) && $inventories|count>0}
      $('.edp-qty-t').show();
      $('.edp-qty-update').hide();
    {else}
      $('.edp-qty-t').hide();
      $('.edp-qty-update').show();
    {/if}

  $('.js_inventory_group_price input.price-options').off('click').on('click', priceOptionsClick);

  $('#attribute_search').keyup(function(){
    var search_value = $(this).val();
    var tb = document.querySelector('.inventory-table');
    $('.found-result').html('');
    var $i = 0;
    if (tb){
        $.each(tb.children[1].rows, function (i, e){
            $(tb.children[1].rows[i]).removeClass("odd green");
            if (search_value.length > 0 && e.innerHTML.match(new RegExp(search_value, "i"))){
                $(tb.children[1].rows[i]).addClass("odd green");
                $i++;
            }
        })
        if (search_value.length){
            $('.found-result').html('{$smarty.const.TEXT_FOUND} ' + $i + '{$smarty.const.TEXT_FOUND_RESULTS} ' + ($i>0?'<a href="'+window.location.origin + window.location.pathname + window.location.search +'#id-inventory-table">{$smarty.CONST.TEXT_GO}</a>':''))
        }
    }
  })
{*
/* deprecated */
/*
  $('.js_inventory_group_price').on('change_state', function(event, state) {
    var $block = $(this);
    var $all_input = $block.find('[name^="inventoryprice_"],[name^="inventorygrossprice_"]');
    var base_ref = 'input[name="inventoryprice_' + $block.attr('data-uprid') + '[0]"]';
    var $main_input = $block.find('.js_inventory_price_input');
    //
    var base_val = parseFloat($(base_ref).val()) || 0;
    var new_val = ((100-parseFloat($block.attr('data-group_discount')))/100*base_val);
    //
    $(base_ref).on('change',function() {
      $block.find('[name^="iopt_"]').filter('[value="-2"]').trigger('click');
    });

    var $dep_block = $block.closest('.tab-pane').find('.qty-discount-wrap');
    if ( parseFloat(state) == -1 ) {
      $all_input.removeAttr('readonly');
      $all_input.removeAttr('disabled');
      $main_input.val('-1');
      $block.find('.js_inventory_price_block').hide();
      $dep_block.hide();
    } else if(parseFloat(state) == -2) {
      if ( $dep_block.is(':hidden') ) $dep_block.show();
      $all_input.removeAttr('readonly');
      $all_input.removeAttr('disabled');
      $main_input.val(new_val);
      $main_input.trigger('keyup');
      $all_input.attr({ readonly:'readonly',disabled:'disabled' });
      $block.find('.js_inventory_price_block').show();
    } else {
      if ( $dep_block.is(':hidden') ) $dep_block.show();
      $all_input.removeAttr('readonly');
      $all_input.removeAttr('disabled');
      if ( parseFloat($main_input.val()) <= 0 ) {
        $main_input.val(new_val);
        $main_input.trigger('keyup');
      }
      $block.find('.js_inventory_price_block').show();
    }
  });

*/

  /*$('.js_inventory_group_price [name^="iopt_"]').on('click',function() {
    $(this).parents('.js_inventory_group_price').trigger('change_state',[$(this).val()]);
  });
*/
 /* when popup is displayed // init on load
  $('.js_inventory_group_price').each(function() {
    var $main_input = $(this).find('.js_inventory_price_input');
    var switch_name_locate = 'iopt_';
    var price = parseFloat($main_input.val());
    if (price == -1) {
      $(this).find('[name^="'+switch_name_locate+'"]').filter('[value="-1"]').trigger('click');
    } else if (price == -2) {
      $(this).find('[name^="'+switch_name_locate+'"]').filter('[value="-2"]').trigger('click');
    } else {
      $(this).find('[name^="'+switch_name_locate+'"]').filter('[value="1"]').trigger('click');
    }
    //$(this).trigger('change_state',[]);
  });
*/
///deleted
/*
  $('.js_inventory_group_fullprice').on('change_state', function(event, state) {
    var $block = $(this);
    var $all_input = $block.find('[name^="inventoryfullprice_"],[name^="inventorygrossfullprice_"]');
    var base_ref = 'input[name="inventoryfullprice_' + $block.attr('data-uprid') + '[0]"]';
    var $main_input = $block.find('.js_inventory_price_input');
    //
    var base_val = parseFloat($(base_ref).val()) || 0;
    var new_val = ((100-parseFloat($block.attr('data-group_discount')))/100*base_val);
    //
    $(base_ref).on('change',function() {
      $block.find('[name^="ifiopt_"]').filter('[value="-2"]').trigger('click');
    });

    var $dep_block = $block.closest('.tab-pane').find('.qty-discount-wrap');
    if ( parseFloat(state) == -1 ) {
      $all_input.removeAttr('readonly');
      $all_input.removeAttr('disabled');
      $main_input.val('-1');
      $block.find('.js_inventory_price_block').hide();
      $dep_block.hide();
    } else if(parseFloat(state) == -2) {
      if ( $dep_block.is(':hidden') ) $dep_block.show();
      $all_input.removeAttr('readonly');
      $all_input.removeAttr('disabled');
      $main_input.val(new_val);
      $main_input.trigger('keyup');
      $all_input.attr({ readonly:'readonly',disabled:'disabled' });
      $block.find('.js_inventory_price_block').show();
    } else {
      if ( $dep_block.is(':hidden') ) $dep_block.show();
      $all_input.removeAttr('readonly');
      $all_input.removeAttr('disabled');
      if ( parseFloat($main_input.val()) <= 0 ) {
        $main_input.val(new_val);
        $main_input.trigger('keyup');
      }
      $block.find('.js_inventory_price_block').show();
    }
  });
*/
//deprecated - full deleted
/*
  $('.js_inventory_group_fullprice [name^="ifiopt_"]').on('click',function() {
    $(this).parents('.js_inventory_group_fullprice').trigger('change_state',[$(this).val()]);
  });
*/
 /* when popup is displayed   // init on load
  $('.js_inventory_group_fullprice').each(function() {
    var $main_input = $(this).find('.js_inventory_price_input');
    var switch_name_locate = 'ifiopt_';
    var price = parseFloat($main_input.val());
    if (price == -1) {
      $(this).find('[name^="'+switch_name_locate+'"]').filter('[value="-1"]').trigger('click');
    } else if (price == -2) {
      $(this).find('[name^="'+switch_name_locate+'"]').filter('[value="-2"]').trigger('click');
    } else {
      $(this).find('[name^="'+switch_name_locate+'"]').filter('[value="1"]').trigger('click');
    }
    //$(this).trigger('change_state',[]);
  });
*/
 *}
  $('.right-link').popUp({ 'box_class':'popupCredithistory' });

  $('.select_supplier_inv').popUp({
      box: "<div class='popup-box-wrap'><div class='around-pop-up'></div><div class='popup-box popupEditCat'><div class='pop-up-close'></div><div class='popup-heading cat-head'>{$smarty.const.TEXT_SELECT_ADD_SUPPLIER}</div><div class='pop-up-content'><div class='preloader'></div></div></div></div>"
  });

  $('.inventory-stock-options').on('click',function() {
      var uprid = $(this).data('ikey');
      var option = $(this).val();
      if (option == '0') {
          $('#inventory_stock_by_platforms_' + uprid + '').hide();
          $('#inventory_platform_to_warehouse_' + uprid + '').hide();
      }
      if (option == '1') {
          $('#inventory_stock_by_platforms_' + uprid + '').show();
          $('#inventory_platform_to_warehouse_' + uprid + '').hide();
      }
      if (option == '2') {
          $('#inventory_stock_by_platforms_' + uprid + '').hide();
          $('#inventory_platform_to_warehouse_' + uprid + '').show();
      }
  });

});

function inventory_quantity_update(uprid) {
    var params = [];
    params.push({ name: 'uprid', value: uprid });
    params.push({ name: 'inventoryqtyupdate_' + uprid, value: $('[name="products_quantity_update"]').val() });
    params.push({ name: 'inventoryqtyupdateprefix_' + uprid, value: $('[name="products_quantity_update_prefix"]:checked').val() });
    params.push({ name: 'warehouse_id', value: $('[name="warehouse_id"]').val() });
    params.push({ name: 'w_suppliers_id', value: $('[name="w_suppliers_id"]').val() });
    params.push({ name: 'stock_comments', value: $('[name="stock_comments"]').val() });

    var loc = [];
    $('[name="box_location[]"]').each(function() {
        loc.push($(this).val());
    });
    params.push({ name: 'box_location', value: loc });

    $('#location').find('input').each(function() {
        params.push({ name: $(this).attr('name'), value: $(this).val() });
    });


    $.post("{Yii::$app->urlManager->createUrl('categories/product-quantity-update')}", $.param(params), function(data, status){
      if (status == "success") {
        if (data.products_quantity != undefined) {
          //$('[name="inventoryqtyupdate_' + uprid + '"]').val('');
          $('[name="inventoryqty_' + uprid + '"]').val(data.products_quantity);
          $('div[name="inventoryqty_' + uprid + '_info"]').html(data.products_quantity);
        }
        if (data.allocated_quantity != undefined) {
          $('[name="allocated_quantity_' + uprid + '"]').val(data.allocated_quantity);
          $('div[name="allocated_quantity_' + uprid + '_info"]').html(data.allocated_quantity);
        }
        if (data.temporary_quantity != undefined) {
          $('[name="temporary_quantity_' + uprid + '"]').val(data.temporary_quantity);
          $('div[name="temporary_quantity_' + uprid + '_info"]').html(data.temporary_quantity);
        }
        if (data.warehouse_quantity != undefined) {
          $('[name="warehouse_quantity_' + uprid + '"]').val(data.warehouse_quantity);
          $('div[name="warehouse_quantity_' + uprid + '_info"]').html(data.warehouse_quantity);
        }
        if (data.ordered_quantity != undefined) {
          $('[name="ordered_quantity_' + uprid + '"]').val(data.ordered_quantity);
          $('div[name="ordered_quantity_' + uprid + '_info"]').html(data.ordered_quantity);
        }
        if (data.suppliers_quantity != undefined) {
          $('[name="suppliers_quantity_' + uprid + '"]').val(data.suppliers_quantity);
          $('div[name="suppliers_quantity_' + uprid + '_info"]').html(data.suppliers_quantity);
        }
        $('.popup-box-wrap:last').remove();
      } else {
        alert("Request error.");
      }
    },"json");
}
</script>
