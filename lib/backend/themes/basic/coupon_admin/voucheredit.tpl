{\backend\assets\MultiSelectAsset::register($this)|void}
<div id="voucher_management_data">
<form id="save_voucher_form" name="new_voucher" onSubmit="return saveVoucher();">
<div class="popupCategory">
    <div class="tabbable tabbable-custom">
        <ul class="nav nav-tabs top_tabs_ul">
					<li class="active"><a href="#tab_3" data-toggle="tab">{$smarty.const.TEXT_MAIN_DETAILS}</a></li>
          <li><a href="#tab_2" data-toggle="tab">{$smarty.const.TEXT_NAME_DESCRIPTION}</a></li>            
        </ul>
        <div class="tab-content">
            <div class="tab-pane topTabPane tabbable-custom-b active" id="tab_3">
                   <table cellspacing="0" cellpadding="0" width="100%">
                        <tr>
                                <td class="label_name" valign="top">{$smarty.const.COUPON_AMOUNT}
                                  <div class="info-hint"><div class="info-hint-box"><div class="info-hint-mustache"></div>{$smarty.const.COUPON_AMOUNT_HELP}</div></div>
                                </td>
                                <td class="label_value"><div class="coupon_group_div"><input type="text" name="coupon_amount" value="{$coupon['coupon_amount']}" class="form-control">{$coupon_currency}</div></td>
                        </tr>
						<tr>
                                <td class="label_name">{$smarty.const.AMOUNT_WITH_TAX}
								<div class="info-hint"><div class="info-hint-box"><div class="info-hint-mustache"></div>{$smarty.const.COUPON_AMOUNT_WITH_TAX_HELP}</div></div>
								</td>
                                <td class="label_value"><input type="checkbox" name="flag_with_tax" value="1" class="check_on_off" {if $coupon['flag_with_tax']} checked{/if}></td>
                        </tr>
                        <tr>
                                <td valign="top" class="label_name">{$smarty.const.COUPON_MIN_ORDER}
                                  <div class="info-hint"><div class="info-hint-box"><div class="info-hint-mustache"></div>{$smarty.const.COUPON_MIN_ORDER_HELP}</div></div>
                                </td>
                                <td class="label_value"><input type="text" name="coupon_minimum_order" value="{$coupon['coupon_minimum_order']}" class="form-control"></td>
                        </tr>											
                        <tr>
                                <td class="label_name">{$smarty.const.TEXT_FREE_SHIPPING}</td>
                                <td class="label_value"><input type="checkbox" name="coupon_free_ship" value="1" class="check_on_off" {if $coupon_free_ship} checked{/if}></td>
                        </tr>
                        <tr>
                                <td class="label_name">{$smarty.const.COUPON_USES_SHIPPING}</td>
                                <td class="label_value"><input type="checkbox" name="uses_per_shipping" value="1" class="check_on_off" {if $coupon['uses_per_shipping']} checked{/if}></td>
                        </tr>
                        <tr>
                                <td class="label_name">{$smarty.const.TEXT_PRODUCTS_TAX_CLASS}
								<div class="info-hint"><div class="info-hint-box"><div class="info-hint-mustache"></div>{$smarty.const.COUPON_CODE_HELP}</div></div>
								</td>
                                <td class="label_value coupon_group_div">{\backend\models\Configuration::tep_cfg_pull_down_tax_classes($coupon['tax_class_id'])}</td>
                        </tr>
                        <tr>
                                <td class="label_name">{$smarty.const.TEXT_FOR_RECOVERY}
                                <div class="info-hint"><div class="info-hint-box"><div class="info-hint-mustache"></div>{$smarty.const.FOR_RECOVERY_HELP}</div></div></td>
                                <td class="label_value"><input type="checkbox" name="coupon_for_recovery_email" value="1" class="check_on_off"{if $coupon_for_recovery_email} checked{/if}></td>
                        </tr>
                        <tr>
                                <td class="label_name">{$smarty.const.TEXT_DISABLE_FOR_SPECIAL}</td>
                                <td class="label_value"><input type="checkbox" name="disable_for_special" value="1" class="check_on_off"{if $coupon['disable_for_special']} checked{/if}></td>
                        </tr>
                        <tr>
                          <td valign="top" class="label_name">{$smarty.const.TEXT_RESTRICT_TO_CUSTOMERS}
                            <div class="info-hint"><div class="info-hint-box"><div class="info-hint-mustache"></div>{$smarty.const.TEXT_RESTRICT_TO_CUSTOMERS_HELP}</div></div>
                          </td>
                                <td class="label_value"><input type="text" name="restrict_to_customers" value="{$coupon['restrict_to_customers']}" class="form-control"></td>
                        </tr>
                        <tr>
                          <td valign="top" class="label_name">{$smarty.const.COUPON_CODE}
                            <div class="info-hint"><div class="info-hint-box"><div class="info-hint-mustache"></div>{$smarty.const.COUPON_CODE_HELP}</div></div>
                          </td>
                                <td class="label_value"><input type="text" name="coupon_code" value="{$coupon['coupon_code']}" class="form-control"></td>
                        </tr>
                        <tr>
                                <td valign="top" class="label_name">{$smarty.const.COUPON_USES_COUPON}
                                  <div class="info-hint"><div class="info-hint-box"><div class="info-hint-mustache"></div>{$smarty.const.COUPON_USES_COUPON_HELP}</div></div>
                                </td>
                                <td class="label_value"><input type="text" name="uses_per_coupon" value="{$coupon['uses_per_coupon']}" class="form-control"></td>
                        </tr>
                        <tr>
                                <td valign="top" class="label_name">{$smarty.const.COUPON_USES_USER}
                                  <div class="info-hint"><div class="info-hint-box"><div class="info-hint-mustache"></div>{$smarty.const.COUPON_USES_USER_HELP}</div></div>
                                </td>
                                <td class="label_value"><input type="text" name="uses_per_user" value="{$coupon['uses_per_user']}" class="form-control"></td>
                        </tr><tr>
                                <td valign="top" class="label_name">{$smarty.const.COUPON_PRODUCTS}
                                  <div class="info-hint"><div class="info-hint-box"><div class="info-hint-mustache"></div>{$smarty.const.COUPON_PRODUCTS_HELP}</div></div>
                                </td>
                                <td class="label_value"><div class="coupon_vew"><input type="text" name="restrict_to_products" value="{$coupon['restrict_to_products']}" class="form-control"><a href="{$app->urlManager->createUrl('coupon_admin/treeview')}" class="btn popup">{$smarty.const.IMAGE_VIEW}</a></div></td>
                        </tr><tr>
                                <td valign="top" class="label_name">{$smarty.const.COUPON_CATEGORIES}
                                  <div class="info-hint"><div class="info-hint-box"><div class="info-hint-mustache"></div>{$smarty.const.COUPON_CATEGORIES_HELP}</div></div>
                                </td>
                                <td class="label_value"><div class="coupon_vew"><input type="text" name="restrict_to_categories" value="{$coupon['restrict_to_categories']}" class="form-control"><a href="{$app->urlManager->createUrl('coupon_admin/treeview')}" class="btn popup">{$smarty.const.IMAGE_VIEW}</a></div></td>
                        </tr><tr>
                                <td valign="top" class="label_name">{$smarty.const.TEXT_EXCLUDE_PRODUCTS}
                                  <div class="info-hint"><div class="info-hint-box"><div class="info-hint-mustache"></div>{$smarty.const.TEXT_EXCLUDE_PRODUCTS_HELP}</div></div>
                                </td>
                                <td class="label_value"><div class="coupon_vew"><input type="text" name="exclude_products" value="{$coupon['exclude_products']}" class="form-control"><a href="{$app->urlManager->createUrl(['coupon_admin/treeview', 'input' =>'exclude'])}" class="btn popup">{$smarty.const.IMAGE_VIEW}</a></div></td>
                        </tr><tr>
                                <td valign="top" class="label_name">{$smarty.const.TEXT_EXCLUDE_CATEGORIES}
                                  <div class="info-hint"><div class="info-hint-box"><div class="info-hint-mustache"></div>{$smarty.const.TEXT_EXCLUDE_CATEGORIES_HELP}</div></div>
                                </td>
                                <td class="label_value"><div class="coupon_vew"><input type="text" name="exclude_categories" value="{$coupon['exclude_categories']}" class="form-control"><a href="{$app->urlManager->createUrl(['coupon_admin/treeview', 'input' =>'exclude'])}" class="btn popup">{$smarty.const.IMAGE_VIEW}</a></div></td>
                        </tr><tr>
                                <td valign="top" class="label_name">{$smarty.const.COUPON_COUNTRIES}
                                  <div class="info-hint"><div class="info-hint-box"><div class="info-hint-mustache"></div>{$smarty.const.COUPON_COUNTRIES_HELP}</div></div>
                                </td>
                                <td class="label_value">
                                {\yii\helpers\Html::dropDownList('restrict_to_countries[]', explode(",", $coupon['restrict_to_countries']), \common\helpers\Country::new_get_countries(), ['class' => '', 'multiple' => 'multiple', 'data-role' => 'multiselect'])}
                                </td>
                        </tr><tr>
                                <td valign="top" class="label_name">{$smarty.const.COUPON_STARTDATE}
                                  <div class="info-hint"><div class="info-hint-box"><div class="info-hint-mustache"></div>{$smarty.const.COUPON_STARTDATE_HELP}</div></div>
                                </td>
                                <td class="label_value"><input type="text" name="coupon_startdate" value="{$coupon_start_date}" class="form-control date-control startdate datepicker"></td>
                        </tr><tr>
                                <td valign="top" class="label_name">{$smarty.const.COUPON_FINISHDATE}
                                  <div class="info-hint"><div class="info-hint-box"><div class="info-hint-mustache"></div>{$smarty.const.COUPON_FINISHDATE_HELP}</div></div>
                                </td>
                                <td class="label_value"><input type="text" name="coupon_finishdate" value="{$coupon_expire_date}" class="form-control date-control enddate datepicker"></td>
                        </tr>
                    </table>                   
            </div>
            <div class="tab-pane topTabPane tabbable-custom " id="tab_2">
                {if count($languages) > 1}
              <ul class="nav nav-tabs under_tabs_ul">
                {foreach $languages as $lKey => $lItem}
                  <li{if $lKey == 0} class="active"{/if}><a href="#tab_{$lItem['code']}" data-toggle="tab">{$lItem['logo']}<span>{$lItem['name']}</span></a></li>
                {/foreach}
              </ul>
              {/if}
              <div class="tab-content {if count($languages) < 2}tab-content-no-lang{/if}">
                {foreach $languages as $lKey => $lItem}
                  <div class="tab-pane{if $lKey == 0} active{/if}" id="tab_{$lItem['code']}">
                    <table cellspacing="0" cellpadding="0" width="100%">
                      <tr>
                        <td class="label_name">{$smarty.const.COUPON_NAME}</td>
                        <td class="label_value"><input type="text" name="coupon_name[{$lItem['id']}]" value="{$coupon_name[$lItem['id']]}" class="form-control"></td>
                      </tr>
                      <tr>
                        <td class="label_name">{$smarty.const.COUPON_DESC}</td>
                        <td class="label_value"><textarea name="coupon_description[{$lItem['id']}]" cols="24" rows="3">{$coupon_desc[$lItem['id']]}</textarea></td>
                      </tr>
                    </table>
                  </div>
                {/foreach}
              </div>
            </div>
        </div>
    </div>
    <div class="btn-bar edit-btn-bar">
        <div class="btn-left"><a href="javascript:void(0)" class="btn btn-cancel-foot" onclick="return backStatement()">{$smarty.const.IMAGE_CANCEL}</a></div>
        <div class="btn-right"><button class="btn btn-primary">{$smarty.const.IMAGE_SAVE}</button></div>
    </div>
</div>
<input type="hidden" name="coupon_id" value="{$cid}" />
</form>
</div>
<script type="text/javascript">
var form_prepared = true;
function backStatement() {
    window.history.back();
    return false;
}
function saveVoucher() {
	checkSelectedTaxZone();
	if (form_prepared){
		$.post("{$app->urlManager->createUrl('coupon_admin/voucher-submit')}", $('#save_voucher_form').serialize(), function(data, status){
			if (status == "success") {
				$('#voucher_management_data').html(data);
			} else {
				alert("Request error.");
			}
		},"html");	
	}
    return false;
}

function checkSelectedTaxZone(){
	if ($('select[name=configuration_value]').val() == 0 && $('input[name=flag_with_tax]:checkbox').prop('checked')){
	   form_prepared = false;
	   bootbox.dialog({
        message: '<div class=""><label class="control-label">'+"{$smarty.const.TEXT_SELECT_TAX_CLASS}"+'</label></div>',
        title: "{$smarty.const.ICON_WARNING}",
          buttons: {
            cancel: {
              label: "{$smarty.const.TEXT_BTN_OK}",
              className: "btn-cancel",
              callback: function() {
				
              }
            }
          }
      });
	} else {
	 form_prepared = true;
	}
}
var _old_uses = 0;
$(document).ready(function(){
    $("select[data-role=multiselect]").multipleSelect({
        multiple: true,
        filter: true,
    });
    $( ".startdate.datepicker" ).datepicker({
        changeMonth: true,
        changeYear: true,
        showOtherMonths:true,
        autoSize: false,
		//minDate: '1',
        dateFormat: '{$smarty.const.DATE_FORMAT_DATEPICKER}',
        onClose: function( selectedDate ) {
          $( ".enddate.datepicker" ).datepicker( "option", "minDate", selectedDate );
       }			
    });

    $( ".enddate.datepicker" ).datepicker({
        changeMonth: true,
        changeYear: true,
        showOtherMonths:true,
        autoSize: false,
		//minDate: '1',
        dateFormat: '{$smarty.const.DATE_FORMAT_DATEPICKER}',
        onClose: function( selectedDate ) {
          $( ".startdate.datepicker" ).datepicker( "option", "maxDate", selectedDate );
       }		
    });
	
	_old_uses = $('input[name=uses_per_coupon]').val();
  $(".check_on_off").bootstrapSwitch(
    {
      onText: "{$smarty.const.SW_ON}",
      offText: "{$smarty.const.SW_OFF}",
      handleWidth: '20px',
      labelWidth: '24px',
      onSwitchChange: function (element, argument) {
        if (element.target.name == 'coupon_for_recovery_email'){
          if (argument) {
            $('input[name=uses_per_coupon]').val('');
          } else {
            $('input[name=uses_per_coupon]').val(_old_uses);
          }        
        } else if (element.target.name == 'flag_with_tax'){
			if (argument) {
				checkSelectedTaxZone();				
			}
		}
        return true;
      },
    }
  );
  
	$('.popup').popUp({		
      box: "<div class='popup-box-wrap'><div class='around-pop-up'></div><div class='popup-box'><div class='popup-heading cat-head'>{$smarty.const.TEXT_BANNER_NEW_GROUP}</div><div class='pop-up-content'><div class='preloader'></div></div></div></div>"
	});
  
	})
</script>