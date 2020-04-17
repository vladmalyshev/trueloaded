{use class="common\helpers\Html"}
{\backend\assets\BDTPAsset::register($this)|void}
{*
2do
- updateGross/Net price check - groups_is_tax_applicable
- check apply_groups_discount_to_specials on specials ==-2
*}
<div class="edp-pc-box is-product-bundle after">
  {if $pInfo->parent_products_id}
      <div class="row product-main-detail-top-switchers">
        <div class="status-left"><span>{$smarty.const.TEXT_SUB_PRODUCT_WITH_PRICE}</span> <input type="checkbox" {if $pInfo->products_id_price!=$pInfo->parent_products_id}checked="checked"{/if} data-on="{$pInfo->products_id}" data-off="{$pInfo->parent_products_id}" value="1" class="check_on_off_subprice"></div>
      </div>
  {/if}
  <div class="cbox-left">
<div class="edp-our-price-box-to-remove product-price-data">
  <div class="widget widget-full box box-no-shadow">
    <div class="widget-header"><h4>{$smarty.const.TEXT_OUR_PRICE}:</h4></div>
    <div class="widget-content price-and-cost-content">
        
      <div class="tax-cl">
        <label>{$smarty.const.TEXT_PRODUCTS_TAX_CLASS}</label>
          {Html::dropDownList('products_tax_class_id', $pInfo->products_tax_class_id, $app->controller->view->tax_classes, ['onchange'=>'updateGrossVisible(); $(\'.js-inventory-tax-class[disabled]\').val($(this).val());',  'class'=>'form-control', 'disabled' => $hideSuppliersPart  ])}
          {Html::hiddenInput('specials_id', $pInfo->specials_id)}
      </div>

  {if empty($price_tab_callback)}
    {$price_tab_callback = 'productPriceBlock'}
  {/if}

  {if {$app->controller->view->price_tabs|@count} > 0 }
{* 2improve if tabs order is changed you must update the following "main" group condition:
if $smarty.const.CUSTOMERS_GROUPS_ENABLE != 'True' || substr($idSuffix, -2)=='_0'
*}
    {$tabparams = $app->controller->view->price_tabparams}
    {$tabparams[count($tabparams)-1]['callback'] = $price_tab_callback}
    {$id_prefix = 'mainPrice'}

    {call mTab tabs=$app->controller->view->price_tabs tabparams=$tabparams  fieldsData=$app->controller->view->price_tabs_data  id_prefix = $id_prefix}

  {else}
    {call $price_tab_callback data=$app->controller->view->price_tabs_data  id_prefix = 'mainPrice'}
  {/if}
    </div>
  </div>
</div>

{function productPriceBlock }
{* $data: [ name => val], $fieldSuffix: '[1][0]'  $idSuffix: '-1-0' *}
    <div id="group_price_container{$idSuffix}" class="js_group_price" data-base_price="{$data['base_price']|escape}" data-group_discount="{$data['tabdata']['groups_discount']}" data-currencies-id="{$data['currencies_id']}" data-base_special_price="{$data['base_specials_price']|escape}" >
{* workaround for switchers: group on/off *}
{if $smarty.const.CUSTOMERS_GROUPS_ENABLE != 'True'}
  {if {$data['products_group_price']<0} }
    {$data['products_group_price']=0}
    {$data['products_group_price_gross']=0}
  {/if}
  {if {$data['products_group_special_price']<0} }
    {$data['products_group_special_price']=0}
    {$data['products_group_special_price_gross']=0}
  {/if}
{/if}
{if !$app->controller->view->useMarketPrices }
  {$data['currencies_id']=$default_currency['id']}
{/if}

{if {$data['groups_id']}>0 }
  {if !isset($data['products_group_price']) || $data['products_group_price']==''}
    {$data['products_group_price']=-2}
  {/if}
        <div class="our-pr-line after">
          {*<div class="switch-toggle switch-3 switch-candy">*}
            <label for="popt{$idSuffix}_m2"><input type="radio" class="price-options" id="popt{$idSuffix}_m2" value="-2" {if {round($data['products_group_price'])}==-2}checked{/if} data-idSuffix="{$idSuffix}"/>{$smarty.const.TEXT_PRICE_SWITCH_MAIN_PRICE}</label>
            <label for="popt{$idSuffix}_m1"><input type="radio" class="price-options" id="popt{$idSuffix}_m1" value="1" {if {round($data['products_group_price'])}>=0}checked{/if} data-idSuffix="{$idSuffix}"/>{sprintf($smarty.const.TEXT_PRICE_SWITCH_OWN_PRICE, $data['tabdata']['title'])}</label>
            {*<label for="popt{$idSuffix}_m0"><input type="radio" class="price-options" id="popt{$idSuffix}_m0" value="-1" {if {round($data['products_group_price'])}==-1}checked{/if} data-idSuffix="{$idSuffix}"/>{sprintf($smarty.const.TEXT_PRICE_SWITCH_DISABLE, $data['tabdata']['title'])}</label>*}
          {*</div>*}
        </div>
{/if}
      <div id="div_wrap_hide{$idSuffix}" {if {round($data['products_group_price'])}==-1}style="display:none;"{/if}>
<!-- main price -->
        <div class="our-pr-line after">
          <div>
            <label>{if PRICE_WITH_BACK_TAX == 'True'}{$smarty.const.TEXT_GROSS_PRICE}{else}{$smarty.const.TEXT_NET_PRICE}{/if}</label>
            <input id="products_group_price{$idSuffix}" name="products_group_price{$fieldSuffix|escape}" value='{$data['products_group_price']|escape}' onKeyUp="updateGrossPrice(this);" data-roundTo="{$data['round_to']}" data-precision="{$smarty.const.MAX_CURRENCY_EDIT_PRECISION}" data-currency="{$data['currencies_id']}" class="form-control{if ($smarty.const.CUSTOMERS_GROUPS_ENABLE != 'True' || $data['groups_id']==0) && ($app->controller->view->useMarketPrices != true || $default_currency['id']==$data['currencies_id'])} default_price {/if} mask-money" {if {round($data['products_group_price'])}==-2}style="display:none;"{/if}/>
{if {$data['groups_id']}>0 }
            <span id="span_products_group_price{$idSuffix}" class="form-control-span"{if {round($data['products_group_price'])}>=0}style="display:none;"{/if}>{$currencies->formatById($data['base_price']*((100-$data['tabdata']['groups_discount'])/100), false, $data['currencies_id'])|escape}</span>
{/if}
          </div>
          <div {if PRICE_WITH_BACK_TAX == 'True'}style="display: none;"{/if}>
            <label>{$smarty.const.TEXT_GROSS_PRICE}</label>
            <input id="products_group_price_gross{$idSuffix}" value='{$data['products_group_price_gross']|escape}' onKeyUp="updateNetPrice(this);" data-currency="{$data['currencies_id']}" class="form-control mask-money"{if {round($data['products_group_price'])}==-2}style="display:none;"{/if}/>
              {if {$data['groups_id']}>0 }
                <span id="span_products_group_price_gross{$idSuffix}" class="form-control-span"{if {round($data['products_group_price'])}>=0}style="display:none;"{/if}>{$currencies->formatById($data['base_price_gross']*((100-$data['tabdata']['groups_discount'])/100), false, $data['currencies_id'])|escape}</span>
              {/if}
          </div>
        </div>
          {if ($smarty.const.CUSTOMERS_GROUPS_ENABLE != 'True' || $data['groups_id']==0) && ($app->controller->view->useMarketPrices != true || $default_currency['id']==$data['currencies_id'])}
              {* supplier price is caclulated for default currency only*}
        <div class="our-pr-line dfullcheck after">
            <div class="supplier-price-cost disable-btn is-not-bundle">
              <a href="javascript:void(0)" class="btn" id="products_group_price{$idSuffix}_btn" onclick="return chooseSupplierPrice('products_group_price{$idSuffix}')" {if {round($data['products_group_price'])}==-2}style="display:none;"{/if}{if ((is_null($data['supplier_price_manual']) and SUPPLIER_UPDATE_PRICE_MODE=='Auto') or (!is_null($data['supplier_price_manual']) and $data['supplier_price_manual']==0))} disabled="disabled"{/if}>{$smarty.const.TEXT_PRICE_COST}</a>
              <div class="pull-right">
                <label>
                  {$smarty.const.TEXT_PRICE_BASED_ON_SUPPLIER_AUTO}
                  <input type="checkbox" value="1" id="supplier_auto_price{$idSuffix}" name="supplier_auto_price{$fieldSuffix|escape}" auto-price="products_group_price{$idSuffix}" class="check_sale_prod" {if ((is_null($data['supplier_price_manual']) and SUPPLIER_UPDATE_PRICE_MODE=='Auto') or (!is_null($data['supplier_price_manual']) and $data['supplier_price_manual']==0))}  checked="checked"{/if} />
                </label>
              </div>
            </div>
        </div>
          {/if}

        <!-- specials/sales -->
        <div class="our-pr-line after our-pr-line-check-box dfullcheck">
          <div class="{if ($default_currency['id']!=$data['currencies_id']) }market_sales_switch{/if}" {if ($default_currency['id']!=$data['currencies_id']) }style="display:none;"{/if}>
            {if $smarty.const.CUSTOMERS_GROUPS_ENABLE != 'True' || $data['groups_id']==0 }
              {if $smarty.const.CUSTOMERS_GROUPS_ENABLE != 'True' }
                {$dataToSwitch=$idSuffix}
              {else}
                {$dataToSwitch=substr($idSuffix, 0, -2)}
              {/if}
              <label>{$smarty.const.TEXT_ENABLE_SALE}</label>
              <input type="checkbox" value="1" id="special_status{$idSuffix}" data-toswitch="{if ($default_currency['id']==$data['currencies_id'])}market_sales_switch,{/if}div_sale_prod{$dataToSwitch}" name="special_status{$fieldSuffix|escape}" class="check_sale_prod" {if {$data['sales_status'] > 0}} checked="checked" {/if} data-defaults-set="special_price{$idSuffix},special_price_gross{$idSuffix}" data-defaults-on="0" data-defaults-off="-1"/>
            {/if}

{if !isset($data['products_group_special_price']) || $data['products_group_special_price']==''}
  {if {$data['groups_id']}>0 }
    {$data['products_group_special_price']='-2'}
  {else}
    {$data['products_group_special_price']='0'}
  {/if}
  {$showSalesDiv=0}
{else}
  {$showSalesDiv=1}
{/if}
{if $data['groups_id']>0 }
        <div class="our-pr-line after div_sale_prod div_sale_prod{$idSuffix}" {if ($showSalesDiv==0)}style="display:none;"{/if}>
          <label>{$smarty.const.TEXT_ENABLE_SALE}</label>
          {*<div class="switch-toggle switch-3 switch-candy">*}
            <label for="popt{$idSuffix}_s2"><input type="radio" class="price-options" id="popt{$idSuffix}_s2" value="-2" {if $data['products_group_special_price']=='-2'}checked{/if} data-idSuffix="{$idSuffix}"/>{$smarty.const.TEXT_PRICE_SWITCH_MAIN_PRICE}</label>
            <label for="popt{$idSuffix}_s1"><input type="radio" class="price-options" id="popt{$idSuffix}_s1" value="1" {if {round($data['products_group_special_price'])}>=0}checked{/if} data-idSuffix="{$idSuffix}"/>{sprintf($smarty.const.TEXT_PRICE_SWITCH_OWN_PRICE, $data['tabdata']['title'])}</label>
            <label for="popt{$idSuffix}_s0"><input type="radio" class="price-options" id="popt{$idSuffix}_s0" value="-1" {if $data['products_group_special_price']=='-1'}checked{/if} data-idSuffix="{$idSuffix}"/>{sprintf($smarty.const.TEXT_PRICE_SWITCH_DISABLE, $data['tabdata']['title'])}</label>
          {*</div>*}
        </div>
{/if}
          </div>
        </div>
        <div class="{if ($default_currency['id']!=$data['currencies_id']) }market_sales_switch{/if}" {if ($default_currency['id']!=$data['currencies_id'] && $data['sales_status']!=1) }style="display:none;"{/if}>
        <div id="div_sale_prod{$idSuffix}" class="sale-prod-line-block after div_sale_prod div_sale_prod{$idSuffix}" {if ($showSalesDiv==0 || $data['products_group_special_price']==-1)}style="display:none;"{/if}>
          <div class="_sale-prod-line our-pr-line">
          {if ($smarty.const.CUSTOMERS_GROUPS_ENABLE != 'True' || $data['groups_id']==0) && ($app->controller->view->useMarketPrices != true || $default_currency['id']==$data['currencies_id'])}
            <div class="_disable-btn">
              <label>{$smarty.const.TEXT_START_DATE}</label>
              <input id="special_start_date{$idSuffix}" name="special_start_date{$fieldSuffix|escape}" value='{\common\helpers\Date::datepicker_date($data['start_date'])}' class="datetimepicker form-control"/>
            </div>
            <div class="_disable-btn">
              <label>{$smarty.const.TEXT_EXPIRY_DATE}</label>
              <input id="special_expires_date{$idSuffix}" name="special_expires_date{$fieldSuffix|escape}" value='{\common\helpers\Date::datepicker_date($data['expires_date'])}' class="datetimepicker form-control form-control-small"/>
            </div>
          {/if}
          </div>
          <div class="_sale-prod-line our-pr-line">
          <div>
            <label class="sale-info">{$smarty.const.TEXT_SALE}:</label>
            <input id="special_price{$idSuffix}" data-idsuffix="{$idSuffix}" name="special_price{$fieldSuffix|escape}" value='{if $data['products_group_special_price']>0.001}{$data['products_group_special_price']|escape}{/if}' onKeyUp="updateGrossPrice(this);" data-roundTo="{$data['round_to']}" class="form-control mask-money" {if $data['groups_id']>0 && round($data['products_group_special_price'])==-2}style="display:none;"{/if}/>
{if $data['groups_id']>0 }
            <span id="span_special_price{$idSuffix}" class="form-control-span"{if round($data['products_group_specials_price'])>=0}style="display:none;"{/if}>{$currencies->formatById($data['base_specials_price']*((100-$data['tabdata']['groups_discount'])/100), false, $data['currencies_id'])|escape}</span>
{/if}
          </div>
          <div>
            <label class="sale-info">{$smarty.const.TEXT_SALE_GROSS}:</label>
            <input id="special_price_gross{$idSuffix}" data-idsuffix="{$idSuffix}" value='{if $data['products_group_special_price_gross']>0}{$data['products_group_special_price_gross']|escape}{/if}' onKeyUp="updateNetPrice(this);" class="form-control mask-money" {if $data['groups_id']>0 && round($data['products_group_special_price'])==-2}style="display:none;"{/if}/>
{if $data['groups_id']>0 }
            <span id="span_special_price_gross{$idSuffix}" class="form-control-span" {if {round($data['products_group_specials_price'])}>=0}style="display:none;"{/if}>{$currencies->formatById($data['base_specials_price_gross']*((100-$data['tabdata']['groups_discount'])/100), false, $data['currencies_id'])|escape}</span>
{/if}
          </div>
          </div>
        </div>
        </div>
<!-- gift wrap  -->
        <div class="our-pr-line after our-pr-line-check-box dfullcheck">
          <div>
            <label>{$smarty.const.TEXT_GIVE_WRAP}</label>
              <input type="checkbox" value="1"  id="gift_wrap{$idSuffix}" name="gift_wrap{$fieldSuffix|escape}" class="check_gift_wrap" {if {$data['gift_wrap_id'] > 0}} checked="checked" {/if} />
          </div>
        </div>
        <div class="our-pr-line after div_gift_wrap" id="div_gift_wrap{$idSuffix}" {if not {$data['gift_wrap_id'] > 0}} style="display:none;" {/if}>
          <div>
            <label>{$smarty.const.TEXT_NET_PRICE}</label>
            <input id="gift_wrap_price{$idSuffix}" name="gift_wrap_price{$fieldSuffix|escape}" value='{$data['gift_wrap_price']|escape}' onKeyUp="updateGrossPrice(this);" data-roundTo="{$data['round_to']}" class="form-control"/>
          </div>
          <div class="disable-btn">
            <label>{$smarty.const.TEXT_GROSS_PRICE}</label>
            <input id="gift_wrap_price_gross{$idSuffix}" value='{$data['gift_wrap_price_gross']|escape}' onKeyUp="updateNetPrice(this);" class="form-control"/>
          </div>
        </div>
<!-- shipping surcharge  -->
        <div class="our-pr-line after our-pr-line-check-box dfullcheck">
          <div>
            <label>{$smarty.const.TEXT_SHIPPING_SURCHARGE}</label>
              <input type="checkbox" value="1"  id="shipping_surcharge{$idSuffix}" name="shipping_surcharge{$fieldSuffix|escape}" class="check_shipping_surcharge" {if {$data['shipping_surcharge_price'] > 0}} checked="checked" {/if} />
          </div>
        </div>
        <div class="our-pr-line after div_shipping_surcharge" id="div_shipping_surcharge{$idSuffix}" {if not {$data['shipping_surcharge_price'] > 0}} style="display:none;" {/if}>
          <div>
            <label>{$smarty.const.TEXT_NET_PRICE}</label>
            <input id="shipping_surcharge_price{$idSuffix}" name="shipping_surcharge_price{$fieldSuffix|escape}" value='{$data['shipping_surcharge_price']|escape}' onKeyUp="updateGrossPrice(this);" data-roundTo="{$data['round_to']}" class="form-control"/>
          </div>
          <div class="disable-btn">
            <label>{$smarty.const.TEXT_GROSS_PRICE}</label>
            <input id="shipping_surcharge_price_gross{$idSuffix}" value='{$data['shipping_surcharge_price_gross']|escape}' onKeyUp="updateNetPrice(this);" class="form-control"/>
          </div>
        </div>
<!-- bonus points -->
        <div class="our-pr-line after our-pr-line-check-box dfullcheck">
          <div>
            <label class="points_prod">{$smarty.const.TEXT_ENABLE_POINTSE}</label>
              <input type="checkbox" value="1"  id="bonus_points{$idSuffix}" name="bonus_points_status{$fieldSuffix|escape}" class="check_points_prod" {if {max($data['bonus_points_price'], $data['bonus_points_cost']) > 0}} checked="checked" {/if} />
          </div>
        </div>
        <div class="our-pr-line after div_points_prod points_prod" id="div_bonus_points{$idSuffix}" {if not {max($data['bonus_points_price'], $data['bonus_points_cost']) > 0}} style="display:none;" {/if}>
          <div>
            <label>{$smarty.const.TEXT_BONUS_POINT}</label>
            <input id="bonus_points_price{$idSuffix}" name="bonus_points_price{$fieldSuffix|escape}" value='{$data['bonus_points_price']|escape}' class="form-control"/>
          </div>
          <div class="disable-btn" {if \common\helpers\Points::getCurrencyCoefficient(0) !== false} style="display: none;" {/if}>
            <label>{$smarty.const.TEXT_POINTS_COST}</label>
            <input id="bonus_points_cost{$idSuffix}" name="bonus_points_cost{$fieldSuffix|escape}" value='{$data['bonus_points_cost']|escape}' class="form-control"/>
          </div>
        </div>
<!-- q-ty discount -->
        <div class="our-pr-line after our-pr-line-check-box dfullcheck">
          <div>
            <label>{$smarty.const.TEXT_QUANTITY_DISCOUNT}</label>
            {* always - else imposible to set up per group without discount to all if $smarty.const.CUSTOMERS_GROUPS_ENABLE != 'True' || substr($idSuffix, -2)=='_0'*}
              {*{$dataToSwitch=$idSuffix}  inventory *}
              <input type="checkbox" value="1" name="qty_discount_status{$fieldSuffix|escape}" data-toswitch="prod_qty_discount{$idSuffix}" class="check_qty_discount_prod" id="check_qty_discount_prod{$idSuffix}" {if isset($data['qty_discounts']) && $data['qty_discounts']|@count > 0} checked="checked" {/if} />
            {*/if*}
          </div>
        </div>
        <div id="hide_wrap_price_qty_discount{$idSuffix}" class="prod_qty_discount{$idSuffix}" {if !isset($data['qty_discounts']) || $data['qty_discounts']|@count==0 }style="display:none;"{/if}>
          <div id="wrap_price_qty_discount{$idSuffix}" class="wrap-quant-discount">
              {foreach $data['qty_discounts'] as $qty => $prices}
                {call productQtyDiscountRow }
              {/foreach}
          </div>
          <div class="quant-discount-btn div_qty_discount_prod">
            <span class="btn btn-add-more" id="prod_qty_discount{$idSuffix}" data-idSuffix="{$idSuffix}" data-fieldSuffix="{$fieldSuffix|escape}" data-callback="addQtyDiscountRow">{$smarty.const.TEXT_AND_MORE}</span>
          </div>
        </div>
      </div>
    </div>
    <!-- disable any promo/discounts-->
    {if ($smarty.const.CUSTOMERS_GROUPS_ENABLE != 'True' || $data['groups_id']==0) && ($app->controller->view->useMarketPrices != true || $default_currency['id']==$data['currencies_id'])}
    <div class="our-pr-line after our-pr-line-check-box dfullcheck">
      <div>
        <label>{$smarty.const.TEXT_DISABLE_ANY_PROMO_DISCOUNTS}</label>
          <input type="checkbox" value="1"  id="disable_discount" name="disable_discount" class="check_disable_discount" {if $pInfo->disable_discount > 0} checked="checked" {/if} />
      </div>
    </div>
    {/if}
{/function}

{function  productQtyDiscountRow}{strip}
              <div class="quant-discount-line after div_qty_discount_prod">
                <div>
                  <label>{$smarty.const.TEXT_PRODUCTS_QUANTITY_INFO}</label>
                  <input id="discount_qty{$idSuffix}_{$prices@iteration}" name="discount_qty{$fieldSuffix|escape}[{$price@index}]" value="{$qty}" {if \common\helpers\Acl::checkExtension('Inventory', 'allowed') && (PRODUCTS_INVENTORY == 'True')}onchange="updateInventoryBox(this);"{/if} class="form-control"/>
                </div><div>
                  <label>{$smarty.const.TEXT_NET}</label>
                  <input id="discount_price{$idSuffix}_{$prices@iteration}" name="discount_price{$fieldSuffix|escape}[{$price@index}]" value="{$prices['price']}" onKeyUp="updateGrossPrice(this);" data-roundTo="{$data['round_to']}" class="form-control"/>
                </div><div>
                  <label>{$smarty.const.TEXT_GROSS}</label>
                  <input id="discount_price_gross{$idSuffix}_{$prices@iteration}" value="{$prices['price_gross']}" onKeyUp="updateNetPrice(this);" class="form-control"/>
                </div>
                <span class="rem-quan-line"></span>
              </div>
{/strip}{/function}


  </div>
{if !$hideSuppliersPart && $TabAccess->allowSuppliersData()}
  <div class="cbox-right is-not-bundle">
  <div class="tax-cl">
        <label>{$smarty.const.TEXT_RRP}:</label>
          {Html::input('text', 'products_price_rrp', $pInfo->products_price_rrp, ['class'=>'form-control mask-money'])}
      </div>
    <div class="widget box box-no-shadow {if $pInfo->parent_products_id}disabled_block{/if}" style="margin-bottom: 0;">
      <div class="widget-header"><h4>{$smarty.const.TEXT_SUPPLIER_COST}</h4>
        <div class="edp-line">
                <span class="edp-qty-t" style="display:none;">{$smarty.const.TEXT_APPLICABLE}</b></span>
            </div>
      </div>
      <div class="widget-content edp-qty-update">
        {include file="supplierproduct-list.tpl"}
        <div class="ed-sup-btn-box">
          <a href="{Yii::$app->urlManager->createUrl(['categories/supplier-select', 'uprid' => $pInfo->products_id])}" class="btn select_supplier">{$smarty.const.TEXT_SELECT_ADD_SUPPLIER}</a>
        </div>
      </div>
    </div>
  </div>
{/if}
{if !$hideSuppliersPart && $pInfo->parent_products_id==0}
{if \common\helpers\Acl::rule(['TABLE_HEADING_PRODUCTS', 'IMAGE_EDIT', 'TAB_BUNDLES'])}
  <div class="cbox-right is-bundle">
    <div class="widget box box-no-shadow" style="margin-bottom: 0;">
      <div class="widget-header"><h4>{$smarty.const.TAB_BUNDLES}</h4></div>
      <div class="widget-content" id="bundles-placeholder">
        {if \common\helpers\Acl::checkExtension('ProductBundles', 'productBlock')}
          {\common\extensions\ProductBundles\ProductBundles::productBlock($pInfo)}
        {else}   
          {include 'productedit/bundles.tpl'}
        {/if}
      </div>
    </div>
  </div>
{/if}
{/if}
</div>

<script type="text/javascript">
var bsPriceParams = {
                      onSwitchChange: function (element, argument) {
                        var t = $(this).attr('data-toswitch');
                        if (typeof(t) != 'undefined') { //all divs, css class of which is starting with t
                          tmp = t.split(",");
                          if (tmp.length>1) {
                            sel = '[class*="' + tmp.join('"], [class*="') + '"]';
                          }else {
                            sel = '[class*="' + t +'"]';
                          }
                        } else {
                          sel = '#div_' + $(this).attr('id');
                        }
                        var setDefaults = $(this).attr('data-defaults-set');
                        if (argument) {
                          $(sel).show();
                          if (typeof(setDefaults) != 'undefined') {
                            var defval=$(this).attr('data-defaults-on');
                          }
                        } else {
                          $(sel).hide();
                          if (typeof(setDefaults) != 'undefined') {
                            var defval=$(this).attr('data-defaults-off');
                          }
                        }
                        if (typeof(setDefaults) != 'undefined' && typeof(defval) != 'undefined') {
                          $(setDefaults.split(',')).each(function() {
                            $('#'+this).val(defval);
                          });
                        }
                        var autoPrice = $(this).attr('auto-price');
                        if (typeof(autoPrice) != 'undefined') {
                            if (argument) {
                                $('#' + autoPrice +'_btn').attr('disabled', 'disabled');
                                chooseSupplierAutoPrice(autoPrice);
                            } else {
                                $('#' + autoPrice +'_btn').removeAttr('disabled');
                            }
                        }
                        return true;
                      },
                      onText: "{$smarty.const.SW_ON}",
                      offText: "{$smarty.const.SW_OFF}",
                      handleWidth: '20px',
                      labelWidth: '24px'
                    };

                
                    
          $(document).ready(function() {
            {$idSuffix=''}
            {if $smarty.const.CUSTOMERS_GROUPS_ENABLE == 'True'}{$idSuffix="`$idSuffix`_0"}{/if}
            {if $app->controller->view->useMarketPrices }{$idSuffix="`$idSuffix`_0"}{/if} {*2check*}
            $('#special_start_date{$idSuffix}').datetimepicker({
                format: 'DD MMM YYYY h:mm A'
            });
            $('#special_expires_date{$idSuffix}').datetimepicker({
              format: 'DD MMM YYYY h:mm A',
              useCurrent: false
            });
            $('#special_start_date{$idSuffix}').on("dp.change", function (e) {
                $('#special_expires_date{$idSuffix}').data("DateTimePicker").minDate(e.date);
            });
            $('#special_expires_date{$idSuffix}').on("dp.change", function (e) {
                $('#special_start_date{$idSuffix}').data("DateTimePicker").maxDate(e.date);
            });

            $('.btn-add-more').click(addQtyDiscountRow);
            $('.rem-quan-line').click(function() {
              $(this).parent().remove();
            //2do  updateInventoryBox();
            });
            /// if group == 0 update all
            $('input.default_price[name^="products_group_price"]').on('change', updateGrossVisible);

            //$('a[href="#tab_1_3"]').on('shown.bs.tab', function() {
              $('.check_sale_prod, .check_points_prod, .check_qty_discount_prod, .check_supplier_price_mode:visible, .check_gift_wrap, .check_shipping_surcharge, .check_disable_discount').bootstrapSwitch(bsPriceParams);
            //});
            
            /// late/lazy update gross price (only on visible tabs)
            $('ul[id^={$id_prefix}] a[data-toggle="tab"]').on('shown.bs.tab', function () {
              // 2do update gross price inputs
              updateVisibleGrossInputs($($(this).attr('href')));
              // init new visible bootstrapSwitch
              tab = $($(this).attr('href')).not(".inited");
              if (tab.length) {
                tab.addClass('inited');

                $('.check_sale_prod:visible, .check_points_prod:visible, .check_qty_discount_prod:visible, .check_gift_wrap:visible, .check_shipping_surcharge:visible, .check_disable_discount:visible', tab).bootstrapSwitch(bsPriceParams);
              }
            });

            $('.select_supplier').popUp({
              box: "<div class='popup-box-wrap'><div class='around-pop-up'></div><div class='popup-box popupEditCat'><div class='pop-up-close'></div><div class='popup-heading cat-head'>{$smarty.const.TEXT_SELECT_ADD_SUPPLIER}</div><div class='pop-up-content'><div class='preloader'></div></div></div></div>"
            });

            $('input.price-options').off('click').on('click', priceOptionsClick);

            $('.js_group_price').on('change_state',function(event, state){
              //deprecated
              //!!! was recalculate prices (gross/net) for each group js_group_price => div includes base or special prices (switcher + inputs)
              //for particular group+currency  ;)
              //
              var $block = $(this);
              var $all_input = $block.find('[name^="products_groups_prices_"]');
              var base_ref = '#base_price';
              if ( $all_input.length==0 ) {
                $all_input = $block.find('[name^="special_groups_prices_"]');
                base_ref = '#base_sale_price';
              }
              var $main_input = $block.find('.js_price_input');
              //
              var base_val = parseFloat($(base_ref).val()) || 0;
              if ( base_ref=='#base_sale_price' && $(base_ref).val().indexOf('%')!==-1 ) {
                var main_price = $('#base_price').val(),
                        base_percent = parseFloat($(base_ref).val().substring(0,$(base_ref).val().indexOf('%')));
                base_val = main_price - ((base_percent/100)*main_price);
              }
              var new_val = ((100-parseFloat($block.attr('data-group_discount')))/100*base_val);

              //dependant block (specials, points, q-ty discount
              var $dep_block = $block.closest('.tab-pane').find('.js_price_dep');

              if (base_ref == '#base_sale_price') $dep_block = $([]);
              if ( parseFloat(state)==-1 ) {
                $all_input.removeAttr('readonly');
                $all_input.removeAttr('disabled');
                $main_input.val('-1');
                $block.find('.js_price_block').hide();
                $dep_block.hide();
              }else if(parseFloat(state)==-2){
                if ( $dep_block.is(':hidden') ) $dep_block.show();
                $all_input.removeAttr('readonly');
                $all_input.removeAttr('disabled');
                $main_input.val(new_val);
                $main_input.trigger('keyup');
                $all_input.attr({ readonly:'readonly',disabled:'disabled' });
                $block.find('.js_price_block').show();
              }else{
                if ( $dep_block.is(':hidden') ) $dep_block.show();
                $all_input.removeAttr('readonly');
                $all_input.removeAttr('disabled');
                if ( parseFloat($main_input.val())<=0 ) {
                  $main_input.val(new_val);
                  $main_input.trigger('keyup');
                }
                $block.find('.js_price_block').show();
              }
            });

            $('.js_group_price [name^="popt_"]').on('click',function(){
              $(this).parents('.js_group_price').trigger('change_state',[$(this).val()]);
              if ( parseFloat($(this).val()) ==-1) {
                $('.js_group_price').find('[name^="s'+this.name+'"]').filter('[value="-1"]').trigger('click');
              }
            });
            $('.js_group_price [name^="spopt_"]').on('click',function(){
              $(this).parents('.js_group_price').trigger('change_state',[$(this).val()]);
            });
            // init on load - moved to server part
            /*
            $('.js_group_price').each(function(){
              var $main_input = $(this).find('.js_price_input');
              var switch_name_locate = ($main_input.length>0 && $main_input[0].name.indexOf('special_groups_prices_')===0)?'spopt_':'popt_';
              var price = parseFloat($main_input.val());
              if (price==-1) {
                $(this).find('[name^="'+switch_name_locate+'"]').filter('[value="-1"]').trigger('click');
              }else if (price==-2) {
                $(this).find('[name^="'+switch_name_locate+'"]').filter('[value="-2"]').trigger('click');
              }else {
                $(this).find('[name^="'+switch_name_locate+'"]').filter('[value="1"]').trigger('click');
              }
              //$(this).trigger('change_state',[]);
            });*/
            $('#base_price').on('change',function(){
              //update group prices (discount based)
              $('.js_group_price [name^="popt_"]').filter('[value="-2"]').trigger('click');
              //update inventory prices
              updateAllPrices();
            });
            $('#base_sale_price').on('change',function(){
              //update group sale/special prices (discount based)
              $('.js_group_price [name^="spopt_"]').filter('[value="-2"]').trigger('click');
            });     
          });
        </script>
<script type="text/javascript">
  //===== Price and Cost START =====//
  var tax_rates = new Array();
  {if {$app->controller->view->tax_classes|@count} > 0}
  {foreach $app->controller->view->tax_classes as $tax_class_id => $tax_class}
  tax_rates[{$tax_class_id}] = {\common\helpers\Tax::get_tax_rate_value($tax_class_id)};
  {/foreach}
  {/if}

  function doRound(x, places) {
    return Math.round(x * Math.pow(10, places)) / Math.pow(10, places);
  }

  function getTaxRate(uprid) {
    if ( uprid ) {
        if ( uprid.name ) {
            var matchUprid = new RegExp('(\\d[^_\\[]+)');
            var parseInputUprid = matchUprid.exec(uprid.name);
            if (parseInputUprid.length > 0) {
                uprid = parseInputUprid[1];
            }
        }
        var $taxRateSelector = $('.js-inventory-tax-class').filter('[name$="'+uprid+'"]');
        if ( $taxRateSelector.length==1 ) {
            $taxRateSelector = $($taxRateSelector[0]);
            if ( typeof tax_rates[$taxRateSelector.val()] !== 'undefined' ){
                return tax_rates[$taxRateSelector.val()];
            }
            return 0;
        }
    }
    var selected_value = document.forms['product_edit'].products_tax_class_id.selectedIndex;
    var parameterVal = document.forms['product_edit'].products_tax_class_id[selected_value].value;

    if ( (parameterVal > 0) && (tax_rates[parameterVal] > 0) ) {
      return tax_rates[parameterVal];
    } else {
      return 0;
    }
  }

  function percentFormat(num) {
      num = (''+num).replace(/\.?0+$/g,'');
      if ( num==='' ) num = '0';
      return num;
  }

  function currencyFormat(num, id=0) {

    if (!(parseInt(id)>0)) {
      id={$default_currency['id']|json_encode};
    }


    var sep_th_a = { {$default_currency['id']|json_encode}:{$default_currency['thousands_point']|json_encode}{foreach $app->controller->view->currenciesTabs as $c},{$c['id']|json_encode}:{$c['thousands_point']|json_encode}{/foreach} };
    var sep_dec_a = { {$default_currency['id']|json_encode}:{$default_currency['decimal_point']|json_encode}{foreach $app->controller->view->currenciesTabs as $c},{$c['id']|json_encode}:{$c['decimal_point']|json_encode}{/foreach} };
    var symbol_right_a = { {$default_currency['id']|json_encode}:{$default_currency['symbol_right']|json_encode}{foreach $app->controller->view->currenciesTabs as $c},{$c['id']|json_encode}:{$c['symbol_right']|json_encode}{/foreach} };
    var symbol_left_a = { {$default_currency['id']|json_encode}:{$default_currency['symbol_left']|json_encode}{foreach $app->controller->view->currenciesTabs as $c},{$c['id']|json_encode}:{$c['symbol_left']|json_encode}{/foreach} };
    var decimal_places_a = { {$default_currency['id']|json_encode}:{$default_currency['decimal_places']|json_encode}{foreach $app->controller->view->currenciesTabs as $c},{$c['id']|json_encode}:{$c['decimal_places']|json_encode}{/foreach} };

    var sep_th = sep_th_a[id];
    var sep_dec = sep_dec_a[id];
    var symbol_right = symbol_right_a[id];
    var symbol_left = symbol_left_a[id];
    var decimal_places = decimal_places_a[id];
    var sign = '';
    if (num < 0) {
      num = Math.abs(num);
      sign = '-';
    }
    num = Math.round(num * Math.pow(10, decimal_places*1)) / Math.pow(10, decimal_places*1); // round
    var s = new String(num);
    p=s.indexOf('.');
    n=s.indexOf(',');
    var j = Math.floor(num);
    var s1 = new String(j);
    if (p>0 || n>0) {
      if (p>0) {
        s = s.replace('.', sep_dec);
      } else {
        s = s.replace(',', sep_dec);
      }
    }
    var j2 = Math.floor(num * 10);
    if (j == num) {
      s = s + sep_dec + '0000';
    } else if (j2 == num * 10) {
      s = s + '000';
    }
    var l = s1.length;
    var n = Math.floor((l-1)/3);
    while (n >= 1) {
      s = s.substring(0, s.indexOf(sep_dec)-(3*n)) + sep_th + s.substring(s.indexOf(sep_dec)-(3*n), s.length);
      n--;
    }
    s = s.substring(0, s.indexOf(sep_dec) + decimal_places * 1 + 1);
    s = sign + symbol_left + s + symbol_right;
    return s;
  }

  {if $app->controller->view->useMarketPrices == true}
  function updateGross() {
    return;
    var taxRate = getTaxRate();
    {foreach $app->controller->view->currenciesTabs as $currId => $currTitle}
    var grossValue = document.forms['product_edit'].products_price_{$currId}.value;
    if (taxRate > 0) {
      grossValue = grossValue * ((taxRate / 100) + 1);
    }
    document.forms['product_edit'].products_price_gross_{$currId}.value = doRound(grossValue, 6);
    {/foreach}
  }
  function updateNet() {
    var taxRate = getTaxRate();
    {foreach $app->controller->view->currenciesTabs as $currId => $currTitle}
    var netValue = document.forms['product_edit'].products_price_gross_{$currId}.value;
    if (taxRate > 0) {
      netValue = netValue / ((taxRate / 100) + 1);
    }
    document.forms['product_edit'].products_price_{$currId}.value = doRound(netValue, 6);
    {/foreach}
  }
  {else}
  function updateGross() {
    return;
    var taxRate = getTaxRate();
    var grossValue = document.forms['product_edit'].products_price.value;

    if (taxRate > 0) {
      grossValue = grossValue * ((taxRate / 100) + 1);
    }

    document.forms['product_edit'].products_price_gross.value = doRound(grossValue, 6);
  }
  function updateAllPrices() {
    var taxRate = getTaxRate();
    var grossValue = document.forms['product_edit'].products_price.value;

    if (taxRate > 0) {
      grossValue = grossValue * ((taxRate / 100) + 1);
    }
    var arrValue = [];
    $('[name="discount_price[]"]').each(function(i, e) {
      arrValue[i] = e.value;
      if (taxRate > 0) {
        arrValue[i] = arrValue[i] * ((taxRate / 100) + 1);
      }
    });
    $('[name="discount_price_gross[]"]').each(function(i, e) {
      e.value = doRound(arrValue[i], 6);
    });

    var arrValue = [];
    $('[name^="inventoryprice_"]').each(function(i, e) {
      arrValue[i] = e.value;
      if (taxRate > 0) {
        arrValue[i] = arrValue[i] * ((getTaxRate(e) / 100) + 1);
      }
    });
    $('[name^="inventorygrossprice_"]').each(function(i, e) {
      e.value = doRound(arrValue[i], 6);
    });

    var arrValue = [];
    $('[name^="inventoryfullprice_"]').each(function(i, e) {
      arrValue[i] = e.value;
      if (taxRate > 0) {
        arrValue[i] = arrValue[i] * ((getTaxRate(e) / 100) + 1);
      }
    });
    $('[name^="inventorygrossfullprice_"]').each(function(i, e) {
      e.value = doRound(arrValue[i], 6);
    });

    var arrValue = [];
    $('[name^="pack_unit_full_prices"]').each(function(i, e) {
      arrValue[i] = e.value;
      if (taxRate > 0 && e.value != '') {
        arrValue[i] = arrValue[i] * ((taxRate / 100) + 1);
      }
    });
    $('[name^="pack_unit_full_gross_prices"]').each(function(i, e) {
      if (arrValue[i] == '') {
        e.value = arrValue[i];
      } else {
        e.value = doRound(arrValue[i], 6);
      }
    });

    var arrValue = [];
    $('[name^="packaging_full_prices"]').each(function(i, e) {
      arrValue[i] = e.value;
      if (taxRate > 0 && e.value != '') {
        arrValue[i] = arrValue[i] * ((getTaxRate(e) / 100) + 1);
      }
    });
    $('[name^="packaging_full_gross_prices"]').each(function(i, e) {
      if (arrValue[i] == '') {
        e.value = arrValue[i];
      } else {
        e.value = doRound(arrValue[i], 6);
      }
    });

    var arrValue = [];
    $('[name^="inventory_discount_price_"]').each(function(i, e) {
      arrValue[i] = e.value;
      if (taxRate > 0) {
        arrValue[i] = arrValue[i] * ((getTaxRate(e) / 100) + 1);
      }
    });
    $('[name^="inventory_discount_gross_price_"]').each(function(i, e) {
      e.value = doRound(arrValue[i], 6);
    });

    var arrValue = [];
    $('[name^="inventory_discount_full_price_"]').each(function(i, e) {
      arrValue[i] = e.value;
      if (taxRate > 0) {
        arrValue[i] = arrValue[i] * ((getTaxRate(e) / 100) + 1);
      }
    });
    $('[name^="inventory_discount_full_gross_price_"]').each(function(i, e) {
      e.value = doRound(arrValue[i], 6);
    });

    {if {$app->controller->view->groups|@count} > 0}
    {foreach $app->controller->view->groups as $groups_id => $group}

    var fieldValue = document.forms['product_edit'].elements['products_groups_prices_{$groups_id}'].value
    if (fieldValue == -1) {
      document.forms['product_edit'].elements['products_groups_prices_gross_{$groups_id}'].value = doRound(fieldValue, 6);
    } else {
      {if \common\helpers\Acl::checkExtension('BusinessToBusiness', 'productBlock')}
          {\common\extensions\BusinessToBusiness\BusinessToBusiness::productBlock($group)}
      {else}
      if (taxRate > 0) {
        fieldValue = fieldValue * ((taxRate / 100) + 1);
      }
      {/if}
      document.forms['product_edit'].elements['products_groups_prices_gross_{$groups_id}'].value = doRound(fieldValue, 6);
    }

    var arrValue = [];
    $('[name="discount_price_{$groups_id}[]"]').each(function(i, e) {
      arrValue[i] = e.value;
      if (taxRate > 0) {
        arrValue[i] = arrValue[i] * ((taxRate / 100) + 1);
      }
    });
    $('[name="discount_price_gross_{$groups_id}[]"]').each(function(i, e) {
      e.value = doRound(arrValue[i], 6);
    });

    {/foreach}
    {/if}

  }

  function updateNet() {
    var taxRate = getTaxRate();
    var netValue = document.forms['product_edit'].products_price_gross.value;

    if (taxRate > 0) {
      netValue = netValue / ((taxRate / 100) + 1);
    }

    document.forms['product_edit'].products_price.value = doRound(netValue, 6);

    var arrValue = [];
    $('[name="discount_price_gross[]"]').each(function(i, e) {
      arrValue[i] = e.value;
      if (taxRate > 0) {
        arrValue[i] = arrValue[i] / ((taxRate / 100) + 1);
      }
    });
    $('[name="discount_price[]"]').each(function(i, e) {
      e.value = doRound(arrValue[i], 6);
    });

    var arrValue = [];
    $('[name^="inventorygrossprice_"]').each(function(i, e) {
      arrValue[i] = e.value;
      if (taxRate > 0) {
        arrValue[i] = arrValue[i] / ((getTaxRate(e) / 100) + 1);
      }
    });
    $('[name^="inventoryprice_"]').each(function(i, e) {
      e.value = doRound(arrValue[i], 6);
    });

    var arrValue = [];
    $('[name^="inventorygrossfullprice_"]').each(function(i, e) {
      arrValue[i] = e.value;
      if (taxRate > 0) {
        arrValue[i] = arrValue[i] / ((getTaxRate(e) / 100) + 1);
      }
    });
    $('[name^="inventoryfullprice_"]').each(function(i, e) {
      e.value = doRound(arrValue[i], 6);
    });

    var arrValue = [];
    $('[name^="pack_unit_full_gross_prices"]').each(function(i, e) {
      arrValue[i] = e.value;
      if (taxRate > 0 && e.value != '') {
        arrValue[i] = arrValue[i] / ((taxRate / 100) + 1);
      }
    });
    $('[name^="pack_unit_full_prices"]').each(function(i, e) {
      if (arrValue[i] == '') {
        e.value = arrValue[i];
      } else {
        e.value = doRound(arrValue[i], 6);
      }
    });

    var arrValue = [];
    $('[name^="packaging_full_gross_prices"]').each(function(i, e) {
      arrValue[i] = e.value;
      if (taxRate > 0 && e.value != '') {
        arrValue[i] = arrValue[i] / ((taxRate / 100) + 1);
      }
    });
    $('[name^="packaging_full_prices"]').each(function(i, e) {
      if (arrValue[i] == '') {
        e.value = arrValue[i];
      } else {
        e.value = doRound(arrValue[i], 6);
      }
    });

    var arrValue = [];
    $('[name^="inventory_discount_gross_price_"]').each(function(i, e) {
      arrValue[i] = e.value;
      if (taxRate > 0) {
        arrValue[i] = arrValue[i] / ((getTaxRate(e) / 100) + 1);
      }
    });
    $('[name^="inventory_discount_price_"]').each(function(i, e) {
      e.value = doRound(arrValue[i], 6);
    });

    var arrValue = [];
    $('[name^="inventory_discount_full_gross_price_"]').each(function(i, e) {
      arrValue[i] = e.value;
      if (taxRate > 0) {
        arrValue[i] = arrValue[i] / ((getTaxRate(e) / 100) + 1);
      }
    });
    $('[name^="inventory_discount_full_price_"]').each(function(i, e) {
      e.value = doRound(arrValue[i], 6);
    });

    {if {$app->controller->view->groups|@count} > 0}
    {foreach $app->controller->view->groups as $groups_id => $group}

    var fieldValue = document.forms['product_edit'].elements['products_groups_prices_gross_{$groups_id}'].value
    if (fieldValue == -1) {
      document.forms['product_edit'].elements['products_groups_prices_{$groups_id}'].value = doRound(fieldValue, 6);
    } else {
      {if {$group['groups_is_tax_applicable']} > 0}
      if (taxRate > 0) {
        fieldValue = fieldValue / ((taxRate / 100) + 1);
      }
      {/if}
      document.forms['product_edit'].elements['products_groups_prices_{$groups_id}'].value = doRound(fieldValue, 6);
    }

    var arrValue = [];
    $('[name="discount_price_gross_{$groups_id}[]"]').each(function(i, e) {
      arrValue[i] = e.value;
      if (taxRate > 0) {
        arrValue[i] = arrValue[i] / ((taxRate / 100) + 1);
      }
    });
    $('[name="discount_price_{$groups_id}[]"]').each(function(i, e) {
      e.value = doRound(arrValue[i], 6);
    });

    {/foreach}
    {/if}

  }
  {/if}

  



  function addQtyDiscountRow() {
    var idSuffix = $(this).attr('data-idSuffix');
    var fieldSuffix = $(this).attr('data-fieldSuffix');
    var num;
    tmp = $('#wrap_price_qty_discount' + idSuffix + ' .quant-discount-line:last input:first').attr('id');
    if (typeof tmp !== 'undefined') {
      num = 1+parseInt(tmp.split('_').pop());
    } else {
      num = 0;
    }


    {call productQtyDiscountRow assign="js_row" data=false fieldSuffix='_fieldSuffix' idSuffix='_idSuffix'}
    var product_discount_row = '{$js_row|escape:quotes}';
    $('#wrap_price_qty_discount' + idSuffix).append(
          product_discount_row.replace(/_idSuffix_/g, idSuffix + '_' + num).replace(/_fieldSuffix\[\]/g, fieldSuffix + '[' + num + ']')
      ).show();
    $('#wrap_price_qty_discount' + idSuffix + ' .rem-quan-line').off('click').click(function() {
      ($(this).parent()).remove();
    });
    return false;
  }

  function updateGrossPrice(el) {
    var taxRate = getTaxRate(el);
    var roundTo = 6;
    //$(el).focus();
    if($(el).attr('data-roundTo')) {
      roundTo = parseInt($(el).attr('data-roundTo'));
    }
    var targetId = el.id.replace('_price', '_price_gross');

    /* process % in special price first */
    if (el.value.slice(-1)=='%'){
      var id_suffix = $(el).attr('data-idsuffix');
      if (typeof id_suffix != 'undefined') {
        base_suffix = id_suffix.replace(/\d+$/, 0);
        base_price = parseFloat( unformatMaskField('#products_group_price' + base_suffix) ) || $('#group_price_container' + id_suffix).attr('data-base_price');
        el.value = doRound(base_price * (1-parseFloat(el.value.slice(0, -1))/100), roundTo);
      }
    }
    ////////

    var grossValue = parseFloat(el.value.replace(/[^(\d+)\.(\d+)]/g, '')) || 0; // net value by default
    if (grossValue==-2) { // generally - kostyl'
      grossValue = 0;
    }
    if (taxRate > 0) {
      grossValue = grossValue * ((taxRate / 100) + 1);
    }
    $('#' + targetId).val(doRound(grossValue, roundTo)).blur();
  }

  function updateNetPrice(el) {
    var taxRate = getTaxRate(el);
    var targetId = el.id.replace('_price_gross', '_price');
    var roundTo = 6;
    /* process % in special price first */
    if (el.value.slice(-1)=='%'){
      var id_suffix = $(el).attr('data-idsuffix');
      if (typeof id_suffix != 'undefined') {
        base_suffix = id_suffix.replace(/\d+$/, 0);
        base_price = parseFloat($('#products_group_price_gross' + base_suffix).val()) || $('#group_price_container' + id_suffix).attr('data-base_price_gross');
        el.value = doRound(base_price * (1-parseFloat(el.value.slice(0, -1))/100), roundTo);
      }
    }
    ////////
    var netValue = el.value; // gross value by default
    if (taxRate > 0) {
      netValue = netValue / ((taxRate / 100) + 1);
    }
    $('#' + targetId).val(doRound(netValue, roundTo)).blur();
  }

  function updateGrossVisible(uprid) {
    /// update all visible gross price (on change tax class)
    /// inputs (visible) + lists (all)
    if ( !uprid ) {
        updateVisibleGrossInputs();

        $('#suppliers-placeholder{(int)$pInfo->products_id} .js-supplier-product').trigger('change');
    }

    ///lists: 1) attributes, inventory
    var fullPrice = $('#full_add_price').val(),
        mainTaxRate = getTaxRate(),
        taxRate = getTaxRate(uprid);

    $('a.inventory-popup-link').each(function (){
      var walkUprid = $(this).attr('href').replace(/^[^-]+/, '');
      updateInvListPrices(fullPrice, walkUprid, taxRate);

      if ( uprid && ('-'+uprid.replace(/\D/g,'-'))!=walkUprid ) return;
      $('#id'+walkUprid).find('input[name^="products_group_price_"]').each(function(){
          updateGrossPrice(this, taxRate);
      });
    });

  }

  function updateVisibleGrossInputs(el) {
    /// el - currency-group tab
    if (typeof el !== 'undefined') {
      $('input.price-options:checked:visible', $(el)).each(function() {
        $(this).click();
      });
      $(el).find('input[id*=_price]:visible').not('[id*=_price_gross]').keyup();
    } else {
     $('input.price-options:checked:visible').each(function() {
        $(this).click();
      });
     $('input[id*=_price]:visible').not('[id*=_price_gross]').keyup();
    }
  }
  function priceOptionsClick() {
    /// 1) recalculate related net price
    /// hide/show price related block (specials, wrap, surchase, point
    /// init bootstrapSwitch
    // no name - switch by JS
    var id = $(this).attr('id');
    $('input.price-options[id^="' + id.replace(/\d$/, '') + '"]').not('[id="' + id + '"]').prop("checked", false);
    var mainPriceSwitched = id.match(/_m\d$/); //not special
    var isInventory = id.match(/^iop/);
    var val = $(this).val(),
      id_suffix = $(this).attr('data-idsuffix'),
      base_suffix = id_suffix.replace(/\d+$/, 0);

    if ( parseFloat(val)==-1) {
      
      if (mainPriceSwitched) {
        $('#div_wrap_hide' + id_suffix).hide();
        $('#products_group_price' + id_suffix).val(-1);
      } else {
        $('#div_sale_prod' + id_suffix).hide();
        $('#special_price' + id_suffix).val(-1);
      }

    } else if ( parseFloat(val)==-2 ) {
      if (mainPriceSwitched) {
        /// save correct order in arrays!!!!
        toshow = ['span_products_group_price', 'span_products_group_price_gross', 'div_wrap_hide'];
        tohide = ['products_group_price', 'products_group_price_gross'];
      } else {
        toshow = ['span_special_price', 'span_special_price_gross', 'div_sale_prod'];
        tohide = ['special_price', 'special_price_gross'];
      }

      /// 1) recalculate related net price
      if (mainPriceSwitched) {
      // either from input or from
        base_price = parseFloat( unformatMaskField('#products_group_price' + base_suffix) ) || $('#group_price_container' + id_suffix).attr('data-base_price');
      } else {
        base_price = parseFloat($('#special_price' + base_suffix).val());
        if (base_price<=0) {
          base_price = $('#group_price_container' + id_suffix).attr('data-base_special_price');
        }
      }
      discount = 1 - parseFloat($('#group_price_container' + id_suffix).attr('data-group_discount'))/100;
      curr_id = $('#group_price_container' + id_suffix).attr('data-currencies-id');

      $('#' + tohide[0] + id_suffix).val(base_price*discount);
      $('#' + tohide[0] + id_suffix).keyup();// I'm lazy - calculate gross price

      if ($(this).parents('.option-percent-price').length==0) {
          $('#' + toshow[0] + id_suffix).text(currencyFormat(doRound(base_price * discount, 6), curr_id));
          $('#' + toshow[1] + id_suffix).text(currencyFormat(unformatMaskField('#' + tohide[1] + id_suffix), curr_id));
      }else{
          $('#' + toshow[0] + id_suffix).text(percentFormat(doRound(base_price, 6)));
          $('#' + toshow[1] + id_suffix).text(percentFormat(doRound(base_price, 6)));
      }

      $('#' + tohide[0] + id_suffix).val('-2');

      for (i=0; i<toshow.length; i++) $('#' + toshow[i] + id_suffix).show();
      for (i=0; i<tohide.length; i++) $('#' + tohide[i] + id_suffix).hide();

      tab = $('#div_wrap_hide' + id_suffix).not(".inited");
      if (mainPriceSwitched && tab.length) {
        tab.addClass('inited');

        $('.check_sale_prod:visible, .check_points_prod:visible, .check_supplier_price_mode:visible, .check_qty_discount_prod:visible, .check_gift_wrap:visible, .check_shipping_surcharge:visible', tab).bootstrapSwitch(bsPriceParams);
      }

    } else {
      if (mainPriceSwitched) {
        /// save correct order in arrays!!!!
        tohide = ['span_products_group_price', 'span_products_group_price_gross'];
        toshow = ['products_group_price', 'products_group_price_gross', 'div_wrap_hide'];
      } else {
        tohide = ['span_special_price', 'span_special_price_gross'];
        toshow = ['special_price', 'special_price_gross', 'div_sale_prod'];
      }
      for (i=0; i<toshow.length; i++) $('#' + toshow[i] + id_suffix).show();
      for (i=0; i<tohide.length; i++) $('#' + tohide[i] + id_suffix).hide();

      if (parseFloat($('#' + toshow[0] + id_suffix).val())<0) {
        $('#' + toshow[0] + id_suffix).val(0);
        $('#' + toshow[1] + id_suffix).val(0);
      }

      tab = $('#div_wrap_hide' + id_suffix).not(".inited");
      if (mainPriceSwitched && tab.length) {
        tab.addClass('inited');

        $('.check_sale_prod:visible, .check_points_prod:visible, .check_supplier_price_mode:visible, .check_qty_discount_prod:visible, .check_gift_wrap:visible, .check_shipping_surcharge:visible', tab).bootstrapSwitch({
          onSwitchChange: function (element, argument) {
            var t = $(this).attr('data-toswitch');
            if (typeof(t) != 'undefined') { //all divs, css class of which is starting with t
              sel = '[class*="' + t +'"]';
            } else {
              sel = '#div_' + $(this).attr('id');
            }
            if (argument) {
              $(sel).show();
            } else {
              $(sel).hide();
            }
            return true;
          },
          onText: "{$smarty.const.SW_ON}",
          offText: "{$smarty.const.SW_OFF}",
          handleWidth: '20px',
          labelWidth: '24px'
        });
      }
    }

  }
  
  function invPriceTabsShown(clicked='') {
    var el = $(this).attr('href');
    if (typeof(el) === 'undefined' && clicked !== '') {
      el = clicked;
    }
    updateVisibleGrossInputs($(el));

    // init new visible bootstrapSwitch
    tab = $(el).not(".inited");
    if (tab.length) {
      tab.addClass('inited');

      $('.check_qty_discount_prod:visible, .check_supplier_price_mode:visible, .attr_file_switch:visible', tab).bootstrapSwitch({
        onSwitchChange: function (element, argument) {
          var t = $(this).attr('data-toswitch');
          var tcss = $(this).attr('data-togglecss');
          if (typeof(tcss) != 'undefined') { // toggle option
            $('.' + tcss).toggle();
          } else {
            if (typeof(t) != 'undefined') { //all divs, css class of which is starting with t
              sel = '[class*="' + t +'"]';
            } else {
              sel = '#div_' + $(this).attr('id');
            }
            if (argument) {
              $(sel).show();
            } else {
              $(sel).hide();
            }
          }
          return true;
        },
        onText: "{$smarty.const.SW_ON}",
        offText: "{$smarty.const.SW_OFF}",
        handleWidth: '20px',
        labelWidth: '24px'
      });
    }
  }
/* updates net and gross prices in assigned attributes and inventory blocks (span, currency formatted)
* if tax rate is specified then only gross price is calculated and updated
*/
  function updateInvListPrices(fullPrice='', upridSuffix='', taxRate='') {
    if (fullPrice!=0 && fullPrice!=1) {
      fullPrice = $('#full_add_price').val();
    }
    if (upridSuffix!='') {
      if (fullPrice=='1') {
        pricePrefix = '';
      } else {
        pricePrefix = $('select.default_currency[id^="invPricePrefix' + upridSuffix + '"]').val() || '';
      }
      if ( pricePrefix.indexOf('%')!==-1 ){
          priceNet = percentFormat($('input.default_currency[id^="products_group_price' + upridSuffix + '"]:first').val());
          priceGross = priceNet;
      }else {
          priceNet = currencyFormat($('input.default_currency[id^="products_group_price' + upridSuffix + '"]:first').val());
          if (taxRate == '') {
              priceGross = currencyFormat($('input.default_currency[id^="products_group_price_gross' + upridSuffix + '"]:first').val());
          } else {
              priceGross = currencyFormat($('input.default_currency[id^="products_group_price' + upridSuffix + '"]:first').val() * ((taxRate / 100) + 1));
          }
      }
      if (taxRate=='') {
        $('#inv_list_price' + upridSuffix).text(pricePrefix + priceNet);
        $('#attr_list_price' + upridSuffix).text(pricePrefix + priceNet);
      }
      $('#inv_list_price_gross' + upridSuffix).text(pricePrefix +  priceGross);
      $('#attr_list_price_gross' + upridSuffix).text(pricePrefix +  priceGross);
    }
  }


  function attrInventoryDetailsClick() {
    var popup = $($(this).attr('href'));
    //save all vals for cancel button functionality
    var _vals = {};
    popup.find("input").each(function() {
      if (this.type == 'text' && !this.disabled && typeof(this.name) !== 'undefined' && this.name != '') {
        if ( this.name.substr(-2,2) == '[]') {
          if (typeof _vals[this.name] !== 'object') {
            _vals[this.name] = new Array();
          }
          _vals[this.name].push(this.value);
        } else {
         _vals[this.name] = this.value;
        }
      }
      if (this.type == 'checkbox' && !this.disabled && typeof(this.name) !== 'undefined' && this.name != '') {
        _vals[this.name] = this.checked;
      }
    });
    //saved

    popup.find('.js-supplier-product').trigger('change');

    popup.show();
    //init visible elements.
    invPriceTabsShown(popup);
    if ( typeof getCountSuppliersPricesInv === 'function') getCountSuppliersPricesInv(popup);

    $('#content, .content-container').css({ 'position': 'relative', 'z-index': '100'});
    $('.w-or-prev-next > .tabbable').css({ 'z-index': '5'});

    var height = function(){
      var h = $(window).height() - $('.popup-heading', popup).height() - $('.popup-buttons', popup).height() - 120;
      $('.popup-content', popup).css('max-height', h);
    };
    height();
    $(window).on('resize', height);
//////// cancel button //////////
    $('.pop-up-close-page, .btn-cancel', popup).off('click').on('click', function(){
      //Cancel button - Reset changes
      popup.find("input").each(function() {
        if (!$(this).is('[readonly]') && typeof(this.name) !== 'undefined' && this.name != '') {
          if (this.type == 'text') {
            if(_vals[this.name] !== 'undefined') {
              if (typeof _vals[this.name]  === 'object') { // array
                this.value = _vals[this.name].shift();
              } else {
                this.value = _vals[this.name];//this.defaultValue;
              }
            } else {
              this.value = this.defaultValue;
            }
          }
          if (this.type == 'checkbox') {
            if(_vals[this.name] !== undefined) {
              try {
                if ($(this).parent().is('div.bootstrap-switch-container'))
                    $(this).bootstrapSwitch('state', _vals[this.name]);
              } catch (err) { }
              this.checked = _vals[this.name];
            }
          }
        }
      });
      
      $('.js_inventory_group_price', popup).each(function() {
        $(this).removeClass("inited");
      });

      popup.hide();
      $(window).off('resize', height);
      $('#content, .content-container').css({ 'position': '', 'z-index': ''});
      $('.w-or-prev-next > .tabbable').css({ 'z-index': ''});
    });
//// save ////
    $('.btn-save2', popup).off('click').on('click', function(){
      //update default currency "main" (0) group  prices in lists
     
      fullPrice = $('#full_add_price').val();
      uprid=$(this).attr('data-upridsuffix');
      updateInvListPrices(fullPrice, uprid);

      popup.hide();
      $(window).off('resize', height);
      $('#content, .content-container').css({ 'position': '', 'z-index': ''});
      $('.w-or-prev-next > .tabbable').css({ 'z-index': ''});
    });

    $('.js_inventory_group_price input.price-options').off('click').on('click', priceOptionsClick);
    return false;
  }
  $(document).on('click','.inventory-popup-link',attrInventoryDetailsClick);

  window.supplierExtraPopup = function(button) {
      var $dataSource = $(button).parents('.js-edit-supplier-product-popup-container').find('.js-edit-supplier-product-popup');
      var $popupData = $dataSource.clone();
      if ($('body > #supplierProductDetailEdit').length===0){
          var _move = $('#supplierProductDetailEdit');
          $('body').append(_move.clone());
          _move.remove();
      }
      var $popupContent = $('#supplierProductDetailEdit');
      $popupContent.find('.popup-content').html($popupData);

      $popupContent.removeClass('hidden');
      var $contentCont = $('#content, .content-container');
      var cZKeep = $contentCont.css('z-index'),
          cPKeep = $contentCont.css('position');
      $contentCont.css({ 'position': 'relative', 'z-index': '100'});
      $('.w-or-prev-next > .tabbable').css({ 'z-index': '5'});
      $popupContent.find('.pop-up-close-page, .js-extra-close-button').off('click').on('click',function(){
          $(this).parents('.js-SupplierExtraDataPopup').addClass('hidden');
          $contentCont.css({ 'position': cZKeep, 'z-index': cPKeep});
      });
      $popupContent.find('.js-extra-update-button').off('click').on('click',function(){
          $('input, select', $popupData).each(function(){
              var $input = $(this);
              var $targetInput = $dataSource.find('[name="'+this.name+'"]');
              if ( this.type.toLowerCase()=='checkbox' ) {
                  if ($targetInput.get(0).checked != $input.get(0).checked){
                      $targetInput.trigger('click');
                  }
              }else{
                  var targetValue = $input.val();
                  if ( targetValue==='' && $input.hasClass('js-supplier-tax-rate') && typeof jQuery.fn.textInputNullableValue === 'function' ) {
                      targetValue = $input.textInputNullableValue();
                  }
                  $targetInput.val(targetValue);
                  $targetInput.trigger('change');
                  if ( $input.hasClass('js-supplier-tax-rate') && typeof jQuery.fn.textInputNullableValue === 'function' ) {
                      $targetInput.trigger('update-state');
                  }
              }
          });
          $(this).parents('.js-SupplierExtraDataPopup').addClass('hidden');
          $contentCont.css({ 'position': cZKeep, 'z-index': cPKeep});
      });
      
      $('div.stock-reorder-supplier input:checkbox')
        .off()
        .on('change', function() {
            //console.log(this);
            $(this).closest('div').find('input:text.form-control').attr('disabled', 'disabled');
            if ($(this).prop('checked') == true) {
                $(this).closest('div').find('input:text.form-control').removeAttr('disabled');
            }
        })
        .change();

      return false;
  }
  $(document).on('click','.js-supplier-detail-edit', function(event){ return supplierExtraPopup(event.target) });

  $(document).on('change', '.js-bind-ctrl', function(event){
      var $input = $(event.target);
      var labelValue = $input.val();
      if ( labelValue==='' && $input.hasClass('js-supplier-tax-rate') && typeof jQuery.fn.textInputNullableValue === 'function' ) {
          labelValue = $input.textInputNullableValue();
      }
      $input.parents('.js-bind-text').find('.js-bind-value').html( labelValue );
  });

  //===== Price and Cost END =====//
</script>
{if !$hideSuppliersPart}
<div id="supplierProductDetailEdit" class="hidden js-SupplierExtraDataPopup popup-box-wrap-page">
  <div class="around-pop-up-page"></div>
  <div class='popup-box-page'>
    <div class='pop-up-close-page'></div><div class='popup-heading cat-head'>{$smarty.const.TEXT_SUPPLIERS_PRODUCT_DETAILS}</div>
    <div class='pop-up-content-page'>
      <div class="popup-content bind-edit">
      </div>
      <div class="popup-buttons">
        <div class="btn-toolbar">
          <div class="pull-left">
            <button type="button" class="btn js-extra-close-button">{$smarty.const.TEXT_CLOSE}</button>
          </div>
          <div class="text-right">
            <button type="button" class="btn btn-primary js-extra-update-button">{$smarty.const.TEXT_UPDATE}</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
{/if}
