{*
This file is part of True Loaded.

@link http://www.holbi.co.uk
@copyright Copyright (c) 2005 Holbi Group LTD

For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
*}
{include file='../assets/tabs.tpl' scope="global"}
{use class="common\helpers\Html"}
{\backend\assets\BDTPAsset::register($this)|void}
{use class="backend\components\Currencies"}
{use class="Yii"}

{Currencies::widget()}
﻿<div class="specialTable">
  {Html::beginForm(['specials/submit'], 'post', ['name' => "product_edit", 'id' => "save_item_form", 'onsubmit' => "return saveItem();"])}
    {Html::hiddenInput('products_id', $pInfo->products_id)}
    <table cellspacing="0" cellpadding="0" width="100%">
      <tr><td class="label_name">{$smarty.const.TEXT_SPECIALS_PRODUCT}</td><td class="label_value_in">
            <a target="blank" href="{Yii::$app->urlManager->createUrl(['categories/productedit', 'pID' => $pInfo->products_id])}">{$backendProductDescription.products_name}</a>
            <br />
            <label>{if PRICE_WITH_BACK_TAX == 'True'}{$smarty.const.TEXT_GROSS_PRICE}{else}{$smarty.const.TEXT_NET_PRICE}{/if}</label> {$price}
            <div {if PRICE_WITH_BACK_TAX == 'True'}style="display: none;"{/if}>
              <label>{$smarty.const.TEXT_GROSS_PRICE}</label> {$priceGross}
            </div>

          </td></tr>
        <tr><td colspan="2" class="">
            <div class="tabbable-custom">
            <div class="tab-content special-edit-settings">


        {$price_tab_callback = 'productSaleOnlyPriceBlock'}
        {$hideSuppliersPart = 1}
        {$specials_id = $sInfo->specials_id}
        {include file='../categories/productedit/price.tpl'}
        </div>
        </div>
        </td></tr>


				<tr><td class="label_name"></td><td class="notest">{$smarty.const.TEXT_SPECIALS_PRICE_TIP}</td></tr>
    </table>
    <div class="btn-bar">
        <div class="btn-left"><a class="btn btn-cancel" href="{Yii::$app->urlManager->createUrl('specials')}">{$smarty.const.IMAGE_CANCEL}</a></div>
        <div class="btn-right"><input class="btn btn-primary" type="submit" value="{$smarty.const.IMAGE_SAVE}"></div>
    </div>

{Html::endForm()}
</div>
<script>
$(document).ready(function(){
  $(".check_on_off").bootstrapSwitch({
    onText: "{$smarty.const.SW_ON}",
    offText: "{$smarty.const.SW_OFF}",
    handleWidth: '20px',
    labelWidth: '24px'
  });


})

    function saveItem() {
        $.post("specials/validate", $('#save_item_form').serialize(), function (data, status) {
            if (status == "success") {
              if (data.valid == 1) {

                if (typeof unformatMaskMoney == 'function') {
                  unformatMaskMoney();
                }
                $.post("specials/submit", $('#save_item_form').serialize(), function (data, status) {
                  if (status == "success") {
                    var msg = "{$smarty.const.MESSAGE_SAVED|escape:'html'}";
                    if (data.result != 1) {
                      msg = '<div class="alert alert-danger">' + data.message + '</div>';
                    } else {
                      $('.mask-money').setMaskMoney();
                    }
                    bootbox.alert({
                      title: "{$smarty.const.BOX_CATALOG_SPECIALS|escape:'html'}",
                      message: msg,
                      size: 'small',
                    });
                  } else {
                      alert("Request error.");
                  }
                }, "json");
              } else {
                bootbox.dialog({
                    title: "{$smarty.const.TEXT_SPECIALS_INTERSECT|escape:'html'}",
                    message: '<div class="alert alert-warning">' + "{$smarty.const.TEXT_SPECIALS_INTERSECT_MESSAGE|escape:'html'}<br>" + data.list  + '</div>',
                    size: 'large',
                    buttons: {
                        ok: {
                            label: "{$smarty.const.TEXT_UPDATE_EXISTING|escape:'html'}",
                            className: 'btn-danger',
                            callback: function(){
                                if (typeof unformatMaskMoney == 'function') {
                                  unformatMaskMoney();
                                }
                                $.post("specials/submit", $('#save_item_form').serialize(), function (data, status) {
                                  if (status == "success") {
                                    var msg = "{$smarty.const.MESSAGE_SAVED|escape:'html'}";
                                    if (data.result != 1) {
                                      msg = '<span class="alert alert-danger">' + data.message + '</span>';
                                    } else {
                                      $('.mask-money').setMaskMoney();
                                    }
                                    bootbox.alert({
                                      title: "{$smarty.const.BOX_CATALOG_SPECIALS|escape:'html'}",
                                      message: msg,
                                      size: 'small',
                                    });
                                  } else {
                                      alert("Request error.");
                                  }
                                }, "json");
                            }
                        },
                        /*noclose: {
                            label: "{$smarty.const.TEXT_CANCEL|escape:'html'}",
                            className: 'btn-warning',
                            callback: function(){
                                console.log('Custom button clicked');
                                return false;
                            }
                        },*/
                        cancel: {
                            label: "{$smarty.const.TEXT_CANCEL|escape:'html'}",
                            className: 'btn-info',
                            callback: function(){
                                //console.log('Custom OK clicked');
                            }
                        }
                    }
                });

              }
            } else {
                alert("Request error.");
            }
        }, "json");

        return false;
    }
</script>

{function productSaleOnlyPriceBlock}
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

      <div id="div_wrap_hide{$idSuffix}" {if {round($data['products_group_price'])}==-1}style="display:none;"{/if}>

        <!-- specials/sales -->
        <div class="our-pr-line after our-pr-line-check-box dfullcheck">
          <div class="{if ($default_currency['id']!=$data['currencies_id']) }market_sales_switch{/if}" {if ($default_currency['id']!=$data['currencies_id']) }style="display:none;"{/if}>
            {if $smarty.const.CUSTOMERS_GROUPS_ENABLE != 'True' || $data['groups_id']==0 }
              {if $smarty.const.CUSTOMERS_GROUPS_ENABLE != 'True' }
                {$dataToSwitch=$idSuffix}
              {else}
                {$dataToSwitch=substr($idSuffix, 0, -2)}
              {/if}
              {if $sInfo->specials_id>0 }
                {if $data['sales_status'] > 0}
                  <label>{$smarty.const.TEXT_ACTIVE}</label>
                {else}
                  <label>{$smarty.const.TEXT_INACTIVE}</label>
                {/if}
              {else}
                <label>{$smarty.const.TEXT_ENABLE_SALE}</label>
                <input type="checkbox" value="1" id="special_status{$idSuffix}" data-toswitch="{if ($default_currency['id']==$data['currencies_id'])}market_sales_switch,{/if}div_sale_prod{$dataToSwitch}" name="special_status{$fieldSuffix|escape}" class="dis_check_sale_prod check_on_off" {if $data['sales_status'] > 0} checked="checked" {/if} data-defaults-set="special_price{$idSuffix},special_price_gross{$idSuffix}" data-defaults-on="0" data-defaults-off="-1"/ >
              {/if}
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
{$showSalesDiv=1}
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


      </div>
    </div>

{/function}
