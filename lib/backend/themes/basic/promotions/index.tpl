
<!--=== Page Header ===-->
<div class="page-header">
    <div class="page-title">
        <h3>{$app->controller->view->headingTitle}</h3>
    </div>
</div>
<!-- /Page Header -->
{\backend\assets\BDPAsset::register($this)|void}
{use class="\yii\helpers\Html"}
{use class="\yii\helpers\Url"}
<div class="widget box box-wrapp-blue filter-wrapp widget-closed1 widget-fixed">
    <div class="widget-header filter-title">
        <h4>{$smarty.const.TEXT_FILTER}</h4>
        <div class="toolbar no-padding">
          <div class="btn-group">
            <span class="btn btn-xs widget-collapse"><i class="icon-angle-up"></i></span>
          </div>
        </div>
    </div>
    <div class="widget-content">        
        <form id="filterForm" name="filterForm" onsubmit="return applyFilter();">
            <div class="wrap_filters after {if $isMultiPlatform}wrap_filters_4{/if}">
                <div class="item_filter item_filter_1 choose_platform">
                    <div class="tl_filters_title">{$smarty.const.TEXT_COMMON_PLATFORM_TAB}</div>
                    <div class="f_td f_td_radio ftd_block">
                        <div>
                            {assign var = items value = \yii\helpers\ArrayHelper::map($platforms, 'id', 'text')}
                            {Html::dropDownList('platform_id', $paltform_id, $items, ['class' => 'form-control', 'onchange' => 'this.form.submit();'])}
                        </div>
                    </div>
                </div>
                <div class="item_filter item_filter_1">
                    <div class="tl_filters_title">{$smarty.const.TEXT_APPLY_PROMOTIONS}</div>
                    <div>
                        <div>
                            <input class="settings_check_on_off" type="radio" name="settings" value="preferred" {if $settings['to_preferred']['only_to_base'] || $settings['to_preferred']['only_to_inventory']}checked{/if} id="preferred"><label for="preferred">{$smarty.const.TEXT_PREFERRED}</label>
                            <div class="settings_preferred">
                                <input class="settings_check_on_off" type="radio" name="preferred" value="to_base" {if $settings['to_preferred']['only_to_base']}checked{/if} id="to_base"><label for="to_base">{$smarty.const.TEXT_APPLY_TO_BASE}</label>
                                <input class="settings_check_on_off" type="radio" name="preferred" value="to_inventory" {if $settings['to_preferred']['only_to_inventory']}checked{/if} id="to_inventory"><label for="to_inventory">{$smarty.const.TEXT_APPLY_TO_INVENTORY}</label>
                            </div>
                        </div>
                        <div>
                            <input class="settings_check_on_off" type="radio" name="settings" value="to_both" {if $settings['to_both']}checked{/if} id="both"><label for="both">{$smarty.const.TEXT_APPLY_TO_BOTH}</label>
                        </div>
                    </div>
                </div>
                <div class="item_filter item_filter_1">
                    <div class="tl_filters_title">Use Icon if special is active</div>
                    <div>
                       <input type="checkbox" name="icon_instead_class" class="icon_on_off icon" {if $smarty.const.PROMOTION_ICON_INSTEAD_SALE == 'true'}checked{/if}>
                    </div>
                    <div class="tl_filters_title">Use Product Properties in Promotions</div>
                    <div>
                       <input type="checkbox" name="property_in_promo" class="icon_on_off property" {if $smarty.const.PROPERTIES_IN_PROMOTIONS == 'true'}checked{/if}>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<style>
    .cat_name{
        padding-left: 5px;
    }
    .cat_name:before{   
        content: "";
    }
    .settings_preferred {
    padding-left: 15px;
    }
</style>
<div class="order-wrap">
<input type="hidden" id="row_id">
           <!--=== Page Content ===-->
				<div class="row order-box-list">
					<div class="col-md-12">
            <div class="widget-content">
              <div class="alert fade in" style="display:none;">
                  <i data-dismiss="alert" class="icon-remove close"></i>
                  <span id="message_plce"></span>
              </div>   	
			  
                <div class="btn-wr after btn-wr-top disable-btn">
                    <div>
                        <a href="javascript:void(0)" onclick="approveSelectedItems();" class="btn btn-no-margin">{$smarty.const.TEXT_ENABLE_SELECTED}</a><a href="javascript:void(0)" onclick="declineSelectedItems();" class="btn">{$smarty.const.TEXT_DISABLE_SELECTED}</a><a href="javascript:void(0)" onclick="deleteSelectedItems();" class="btn btn-del">{$smarty.const.TEXT_DELETE_SELECTED}</a>
                    </div>
                    <div>
                    </div>
                </div> 
                    <table class="table tabl-res table-striped table-selectable table-checkable table-hover table-responsive table-bordered datatable table-switch-on-off double-grid" checkable_list="0" data_ajax="promotions/list">
                            <thead>
                                    <tr>
                                        {foreach $app->controller->view->promotionsTable as $tableItem}
                                            <th{if $tableItem['not_important'] == 2} class="checkbox-column"{/if}{if $tableItem['not_important'] == 1} class="hidden-xs"{/if}>{$tableItem['title']}</th>
                                        {/foreach}
                                    </tr>
                            </thead>

                    </table>            

                </form>
            </div>
				
					</div>
				</div>
<script type="text/javascript">
function getTableSelectedIds() {
    var selected_messages_ids = [];
    var selected_messages_count = 0;
    $('input:checkbox:checked.uniform').each(function(j, cb) {
        var aaa = $(cb).closest('td').find('.cell_identify').val();
        if (typeof(aaa) != 'undefined') {
            selected_messages_ids[selected_messages_count] = aaa;
            selected_messages_count++;
        }
    });
    return selected_messages_ids;
}

function getTableSelectedCount() {
    var selected_messages_count = 0;
    $('input:checkbox:checked.uniform').each(function(j, cb) {
        var aaa = $(cb).closest('td').find('.cell_identify').val();
        if (typeof(aaa) != 'undefined') {
            selected_messages_count++;
        }
    });
    return selected_messages_count;
}

function approveSelectedItems() {
    if (getTableSelectedCount() > 0) {
        var selected_ids = getTableSelectedIds();
        $.post("{Yii::$app->urlManager->createUrl('countries/approve-selected')}", { 'selected_ids' : selected_ids }, function(data, status){
            if (status == "success") {
                resetStatement();
            } else {
                alert("Request error.");
            }
        },"html");
    }
    return false;
}

function declineSelectedItems() {
    if (getTableSelectedCount() > 0) {
        var selected_ids = getTableSelectedIds();
        $.post("{Yii::$app->urlManager->createUrl('countries/decline-selected')}", { 'selected_ids' : selected_ids }, function(data, status){
            if (status == "success") {
                resetStatement();
            } else {
                alert("Request error.");
            }
        },"html");
    }
    return false;
}

function deleteSelectedItems() {
    if (getTableSelectedCount() > 0) {
        var selected_ids = getTableSelectedIds();
        
        bootbox.dialog({
                message: "{$smarty.const.TEXT_DELETE_SELECTED}?",
                title: "{$smarty.const.TEXT_DELETE_SELECTED}",
                buttons: {
                        success: {
                                label: "Yes",
                                className: "btn-delete",
                                callback: function() {
                                    $.post("{Yii::$app->urlManager->createUrl('countries/delete-selected')}", { 'selected_ids' : selected_ids }, function(data, status){
                                        if (status == "success") {
                                            resetStatement();
                                        } else {
                                            alert("Request error.");
                                        }
                                    },"html");
                                }
                        },
                        main: {
                                label: "Cancel",
                                className: "btn-cancel",
                                callback: function() {
                                        //console.log("Primary button");
                                }
                        }
                }
        });
    }
    return false;
}

function switchOffCollapse(id) {
    if ($("#"+id).children('i').hasClass('icon-angle-down')) {
        $("#"+id).click();
    }
}

function switchStatement(id, status) {
    $.post("{Yii::$app->urlManager->createUrl('promotions/switch-status')}", { 'id' : id, 'status' : status }, function(data, status){
        if (status == "success") {
            resetStatement();
        } else {
            alert("Request error.");
        }
    },"html");
}

function switchOnCollapse(id) {
    if ($("#"+id).children('i').hasClass('icon-angle-up')) {
        $("#"+id).click();
    }
}

function setFilterState() {
    orig = $('#filterForm').serialize();
    var url = window.location.origin + window.location.pathname + '?' + orig.replace(/[^&]+=\.?(?:&|$)/g, '')
    window.history.replaceState({ }, '', url);
}

function resetStatement(id) {
    $("#promotions_management").hide();
    switchOnCollapse('countries_list_collapse');
    var table = $('.table').DataTable();
    table.draw(false);
    //$(window).scrollTop(0);
    return false;
}

function applyFilter() {
    $("#row_id").val(0);
    resetStatement();
    return false;    
}

function onClickEvent(obj, table) {
    $("#promotions_management").hide();
    $('#promotions_management_data .scroll_col').html('');
    $('#row_id').val(table.find(obj).index());
    setFilterState();
    var promo_id = $(obj).find('input.cell_identify').val();
    $(".check_on_off").bootstrapSwitch(
    {
        onSwitchChange: function (element, arguments) {
            switchStatement(element.target.value, arguments);
            return true;  
        },
        onText: "{$smarty.const.SW_ON}",
        offText: "{$smarty.const.SW_OFF}",
        handleWidth: '20px',
        labelWidth: '24px'
    }
);
    $.post("{Yii::$app->urlManager->createUrl('promotions/view')}", { 'promo_id' : promo_id }, function(data, status){
            if (status == "success") {
                $('#promotions_management_data .scroll_col').html(data);
                $("#promotions_management").show();
            } else {
                alert("Request error.");
            }
        },"html");
}

function onUnclickEvent(obj, table) {
    var event_id = $(obj).find('input.cell_identify').val();
}


function confirmDeletePromo(id){
    bootbox.dialog({
    message: "{$smarty.const.TEXT_CONFIRMATION_REMOVE}",
    title: "{$smarty.const.TEXT_REMOVE_PROMOTION}",
    buttons: {
            success: {
                    label: "{$smarty.const.TEXT_YES}",
                    className: "btn btn-primary",
                    callback: function() {
                        $.post("{Yii::$app->urlManager->createUrl('promotions/delete')}", { 'promo_id' : id}, function(data, status){
                            if (status == "success") {
                                $('.alert #message_plce').html(data.message);
                                $('.alert').addClass(data.messageType).show();
                                resetStatement();
                            } else {
                                alert("Request error.");
                            }
                        },"json");
                    }
            },
            cancel: {
                    label: "Cancel",
                    className: "btn-cancel",
                    callback: function() {
                            //console.log("Primary button");
                    }
            }
    }
});

}

$(document).ready(function() {
    $('th.checkbox-column .uniform').click(function() {
        if($(this).is(':checked')){
            $('.order-box-list .btn-wr').removeClass('disable-btn');
        }else{
            $('.order-box-list .btn-wr').addClass('disable-btn');
        }
    }); 
    $( ".datatable tbody" ).sortable({
        stop: function( event, ui ) {
            var idx = [];
            var table = $('.table').dataTable();
            $.each($(table).find('input'), function(i, e){
                idx.push($(e).val());
            });
            $.post("{Url::to('promotions/sort')}",{
                'promo_order':idx
            }, function(){
            }, "json");
        },
        update:function( event, ui ) {
            },
    });
    
    function availblepreferred(){
        $('.settings_preferred input:radio').prop('disabled', false);
    }
    
    function disablepreferred(){
        $('.settings_preferred input:radio').prop('disabled', true);
    }
    
    function saveSettings(){
        var to = ($(".settings_check_on_off:checked:enabled").size() > 1 ? $(".settings_check_on_off[name=preferred]:checked:enabled").val(): $(".settings_check_on_off:checked:enabled").val());
        $.post('promotions/save-settings',{
            'to' : to,
        }, function(data, status){
            
        });
    }
    
    function checkDefaultpreferred (){
        if (!$('.settings_check_on_off[name=preferred][value=to_base]').prop('checked') && !$('.settings_check_on_off[name=preferred][value=to_inventory]').prop('checked')){
            $('.settings_check_on_off[name=preferred][value=to_base]').prop('checked', true);
        }
    }
    
    $('.settings_check_on_off[name=settings]').change(function(){
        if ($(this).val() == 'preferred'){
            availblepreferred();
        } else {
            disablepreferred();
        }        
    })
    
    $('.settings_check_on_off').change(function(){
        saveSettings();
    })
    
    if ($('.settings_check_on_off[name=settings][value=to_both]').prop('checked')){
        disablepreferred();
    } else {
        availblepreferred();
    }
    checkDefaultpreferred();
    
    function saveSettingsSwitcher(url, arguments){
        $.post(url,{
            'instead' : (arguments? '1':'0'),
        }, function(data, status){
            
        });
    }
    
    $(".icon_on_off").bootstrapSwitch(
    {
        onSwitchChange: function (element, arguments) {
            var url;
            if ($(element.target).hasClass('icon')){
                url = 'promotions/save-icon-settings';
            } else {
                url = 'promotions/save-property-settings';
            }
            saveSettingsSwitcher(url, arguments);
            //switchStatement(element.target.value, arguments);
            return true;  
        },
        onText: "{$smarty.const.SW_ON}",
        offText: "{$smarty.const.SW_OFF}",
        handleWidth: '20px',
        labelWidth: '24px'
    });
    
})

					</script>
                                <!--===Actions ===-->
				<div class="row right_column" id="promotions_management">
						<div class="widget box">
							<div class="widget-content fields_style" id="promotions_management_data">
                                <div class="scroll_col"></div>
							</div>
						</div>
                                </div>
				<!--===Actions ===-->
				<!-- /Page Content -->		
</div>