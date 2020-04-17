<!-- /Page Header -->
{use class="yii\helpers\Html"}
{\backend\assets\OrderAsset::register($this)|void}
{\backend\assets\MultiSelectAsset::register($this)|void}
<!--=== Page Content ===-->
<div class="widget box box-wrapp-blue filter-wrapp widget-closed widget-fixed">
    <div class="widget-header filter-title">
        <h4>{$smarty.const.TEXT_FILTER} <form action="{$app->urlManager->createUrl('orders/process-order')}" method="get" class="go-to-order filterFormHead"><label>{$smarty.const.TEXT_GO_TO_ORDER}</label> <input type="text" class="form-control" name="orders_id"/> <button type="submit" class="btn">{$smarty.const.TEXT_GO}</button></form><form id="filterFormHead" name="filterFormHead" class="filterFormHead" onsubmit="return applyFilter();"><label>{$smarty.const.TEXT_SEARCH_BY}</label><select class="form-control" name="by">
                        {foreach $app->controller->view->filters->by as $Item}
                            <option {$Item['selected']} value="{$Item['value']}">{$Item['name']}</option>
                        {/foreach}
                    </select><input type="text" name="search" value="{$app->controller->view->filters->search}" class="form-control" /><button type="submit" class="btn">{$smarty.const.TEXT_GO}</button></form>
        {*if count($app->controller->view->filters->admin_choice)}
            <div class="dropdown btn-link-create" style="float:right">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                {$smarty.const.TEXT_UNSAVED_CARTS}
                <i class="icon-caret-down small"></i>
            </a>
            <ul class="dropdown-menu">
                {foreach $app->controller->view->filters->admin_choice as $choice}
                <li>{$choice}</li>
                {/foreach}
            </ul>
        </div>
        {/if*}
            <div class="pull-right">
                <form action="{$app->urlManager->createUrl('orders/index',$app->request->get())}" method="get" id="filterModeForm" class="filterFormHead" onsubmit="return applyFilter();">
                    <label>{$smarty.const.SHOW}</label>
                    {\yii\helpers\Html::dropDownList('', $app->controller->view->filters->mode, [''=>ORDER_FILTER_MODE_ALL, 'need_process'=>ORDER_FILTER_MODE_NEED_PROCESS], ['class'=>'form-control', 'onchange'=>"\$('#hMode').val(\$(this).val());applyFilter()"])}
                </form>
            </div>
        </h4>
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
                        {if $departments}
                        <div class="f_row_pl_cus">
                            <div class="f_td">
                                <label>{$smarty.const.TEXT_COMMON_DEPARTMENTS_FILTER}</label>
                            </div>
                            <div class="f_td f_td_radio ftd_block">
                                <div><label class="radio_label"><input type="checkbox" name="departments[]" class="js_department_checkboxes" value=""> {$smarty.const.TEXT_COMMON_PLATFORM_FILTER_ALL}</label></div>
                                {foreach $departments as $department}
                                    <div><label class="radio_label"><input type="checkbox" name="departments[]" class="js_department_checkboxes" value="{$department['id']}" {if in_array($department['id'], $app->controller->view->filters->departments)} checked="checked"{/if}> {$department['text']}</label></div>
                                {/foreach}
                            </div>
                        </div>
                        {/if}
                        {if $isMultiPlatform}
                            <div class="tl_filters_title">{$smarty.const.TEXT_COMMON_PLATFORM_FILTER}</div>
                            <div class="f_td wl-td ftd_block tl_fron_or">
                                {$count_platform = $platforms|@count}
                                {if $count_platform < 3}
                                    {foreach $platforms as $platform}
                                        <div><label class="radio_label"><input type="checkbox" name="platform[]" class="js_platform_checkboxes" value="{$platform['id']}" {if in_array($platform['id'], $app->controller->view->filters->platform)} checked="checked"{/if}> {$platform['text']}</label></div>
                                            {/foreach}
                                        {else}
                                    <div>
                                        <select class="form-control" name="platform[]" multiple="multiple" data-role="multiselect">
                                            {foreach $platforms as $platform}
                                                <option value="{$platform['id']}"{if in_array($platform['id'], $app->controller->view->filters->platform)} selected{/if}>{$platform['text']}</option>
                                            {/foreach}
                                        </select>
                                    </div>
                                {/if}
                            </div>
                        {/if}
                        <div class="flag-marker-row flag-flag-row">
                            {if is_array($app->controller->view->flags) && count($app->controller->view->flags) > 0}
                                <div class="tl_filters_title">{$smarty.const.TEXT_FLAG}</div>
                                <div class="wl-td w-tdc">
                                  {\yii\helpers\Html::dropDownList('flag', $app->controller->view->filters->flag, yii\helpers\ArrayHelper::map($app->controller->view->flags, 'id', 'text'), ['encode'=> false, 'data-role' => 'multiselect-radio', 'options' =>  $app->controller->view->flags , 'class' => 'form-control'])}
                                </div>
                            {/if}
                        </div>
                        <div class="flag-marker-row">
                            {if is_array($app->controller->view->markers) && count($app->controller->view->markers) > 0}
                                <div class="tl_filters_title">{$smarty.const.TEXT_MARKER}</div>
                                <div class="wl-td w-tdc">
                                 {\yii\helpers\Html::dropDownList('marker', $app->controller->view->filters->marker, yii\helpers\ArrayHelper::map($app->controller->view->markers, 'id', 'text'), ['encode'=> false, 'data-role' => 'multiselect-radio', 'options' =>  $app->controller->view->markers, 'class' => 'form-control' ])}
                                </div>
                            {/if}
                        </div>
                    </div>
                    <div class="item_filter item_filter_2">
                        <div class="tl_filters_title">{$smarty.const.TABLE_HEADING_STATUS}/{$smarty.const.TEXT_STOCK}</div>
                        <div class="wl-td">
                            <label>{$smarty.const.TEXT_STATUS}</label>
                            {Html::dropDownList('status[]', $app->controller->view->filters->status_selected, $app->controller->view->filters->status, ['class' => 'form-control', 'multiple' => 'multiple', 'data-role' => 'multiselect'])}
                        </div>
                        <div class="tl_filters_title tl_filters_title_border">{$smarty.const.TEXT_ORDER_PLACED}</div>
                        <div class="wl-td w-tdc">
                             <label class="radio_label"><input type="radio" name="date" value="presel" id="presel" {if $app->controller->view->filters->presel}checked{/if} /> {$smarty.const.TEXT_PRE_SELECTED}</label>
                             <select name="interval" class="form-control" {if $app->controller->view->filters->exact}disabled{/if}>
                                    {foreach $app->controller->view->filters->interval as $Item}
                                        <option {$Item['selected']} value="{$Item['value']}">{$Item['name']}</option>
                                    {/foreach}
                                </select>
                        </div>
                        <div class="wl-td wl-td-from w-tdc">
                            <label class="radio_label"><input type="radio" name="date" value="exact" id="exact" {if $app->controller->view->filters->exact}checked{/if} /> {$smarty.const.TEXT_EXACT_DATES}</label><table width="100%" cellpadding="0" cellspacing="0"><tr><td><span>{$smarty.const.TEXT_FROM}</span><input id="from_date" type="text" value="{$app->controller->view->filters->from}" autocomplete="off" name="from" class="datepicker form-control form-control-small" {if $app->controller->view->filters->presel}disabled{/if} /></td><td><span class="sp_marg">{$smarty.const.TEXT_TO}</span><input id="to_date" type="text" value="{$app->controller->view->filters->to}" autocomplete="off" name="to" class="datepicker form-control form-control-small" {if $app->controller->view->filters->presel}disabled{/if} /></td></tr></table>
                        </div>
                        {if $app->controller->view->filters->admin}
                        <div class="wl-td w-tdc">
                             <label>{$smarty.const.TEXT_WALKIN_ORDER}</label>
                             {\yii\helpers\Html::dropDownList('walkin[]', $app->controller->view->filters->walkin, $app->controller->view->filters->admin, ['class' => 'form-control', 'multiple' => 'multiple', 'data-role' => 'multiselect'])}
                        </div>
                        {/if}
                    </div>
                    <div class="item_filter item_filter_3">
                        <div class="tl_filters_title">{$smarty.const.TEXT_BY_DELIVERY}</div>
                        <div class="wl-td f_td_country">
                            <label>{$smarty.const.ENTRY_COUNTRY}:</label>
                            <input name="delivery_country" value="{$app->controller->view->filters->delivery_country}" id="selectCountry" type="text" class="form-control" placeholder="{$smarty.const.TEXT_TYPE_COUNTRY}" />
                        </div>
                        {if $app->controller->view->showState == true}
                        <div class="wl-td f_td_state">
                            <label>{$smarty.const.ENTRY_STATE}:</label>
                           <input name="delivery_state" value="{$app->controller->view->filters->delivery_state}" id="selectState" type="text" class="form-control" placeholder="{$smarty.const.TEXT_TYPE_COUNTY}" {if $app->controller->view->filters->delivery_country == ''}disabled{/if} />
                        </div>
                        {/if}
                        <div class="tl_filters_title tl_filters_title_border">{$smarty.const.TEXT_USED_COUPON}</div>
                        <div class="wl-td w-tdc">
                            <label class="radio_label">
                                <span> {$smarty.const.TEXT_FROM_LIST}</span>
                            </label>
                            {Html::dropDownList('fc_id[]', $app->controller->view->filters->fc_id, $app->controller->view->filters->fCoupons, ['class' => 'form-control', 'multiple' => 'multiple', 'data-role' => 'multiselect'])}
                        </div>
                    </div>
                    <div class="item_filter item_filter_4">
                        <div class="tl_filters_title {*tl_filters_title_border*}">{$smarty.const.TEXT_PAYMENT_SHIPPING}</div>
                        <div class="wl-td">
                            <label>{$smarty.const.ENTRY_PAYMENT_METHOD}</label>
                            {Html::dropDownList('payments[]', $app->controller->view->filters->payments_selected, $app->controller->view->filters->payments, ['class' => 'form-control', 'multiple' => 'multiple', 'data-role' => 'multiselect'])}
                        </div>
                        <div class="wl-td">
                            <label>{$smarty.const.TEXT_CHOOSE_SHIPPING_METHOD}:</label>
                            {Html::dropDownList('shipping[]', $app->controller->view->filters->shipping_selected, $app->controller->view->filters->shipping, ['class' => 'form-control', 'multiple' => 'multiple', 'data-role' => 'multiselect'])}
                        </div>
                        <div class="tl_filters_title tl_filters_title_border">{$smarty.const.TEXT_TOTALS}</div>
                        <div class="wl-tdr wl-td-from " style="padding-bottom:10px;">
                           <table cellspacing="0" cellpadding="0" width="100%">
                             <tr>
                               <td><label class="radio_label"><input type="checkbox" id="fpFrom" {if $app->controller->view->filters->fpFrom }checked{/if} /><span> {$smarty.const.TEXT_FROM}</span></label><input name="fp_from" value="{$app->controller->view->filters->fp_from}" id="fpFromSumm" type="text" class="form-control-small form-control"  {if !$app->controller->view->filters->fpFrom}disabled{/if} /></td>
                               <td><label class="radio_label"><input type="checkbox" id="fpTo" {if $app->controller->view->filters->fpTo }checked{/if} /><span> {$smarty.const.TEXT_TO}</span></label><input name="fp_to" value="{$app->controller->view->filters->fp_to}" id="fpToSumm" type="text" class="form-control-small form-control"  {if !$app->controller->view->filters->fpTo}disabled{/if} /></td>
                             </tr>
                           </table>
                        </div>
                        <div class="wl-td">
                          <label> {$smarty.const.TEXT_TOTAL_LINE}</label>
                             <select name="fp_class" id="fpClass" class="form-control" {if !$app->controller->view->filters->fpFrom  && !$app->controller->view->filters->fpTo}disabled{/if}>
                                    {foreach $app->controller->view->filters->fpClass as $Item}
                                        <option {$Item['selected']} value="{$Item['value']}">{$Item['name']}</option>
                                    {/foreach}
                                </select>
                        </div>
                    </div>
                </div>
                <div class="filters_btn">
                    <a href="javascript:void(0)" onclick="return resetFilter();" class="btn">{$smarty.const.TEXT_RESET}</a>&nbsp;&nbsp;&nbsp;<button type="submit" class="btn btn-primary">{$smarty.const.TEXT_SEARCH}</button>
        <input type="hidden" name="row" id="row_id" value="{$app->controller->view->filters->row}" />
        <input type="hidden" name="fs" value="{$app->controller->view->filters->fs}" />
        <input type="hidden" name="mode" value="{$app->controller->view->filters->mode}" id="hMode" />
                </div>
            </form>
        
    </div>
</div>

<!--===Orders List ===-->
<div class="order-wrap">    
<div class="row order-box-list order-sc-text">
    <div class="col-md-12">
        <div class="widget-content">
            <div class="btn-wr after btn-wr-top btn-wr-top1 disable-btn batch-actions">
                <div>
                    <span class="batch-actions-label">{$smarty.const.TEXT_BATCH_ACTIONS}:</span>
                    <a href="javascript:void(0)" onclick="invoiceSelectedOrders();" class="btn btn-no-margin">{$smarty.const.TEXT_BATCH_INVOICE}</a>
                    <a href="javascript:void(0)" onclick="packingslipSelectedOrders()" class="btn">{$smarty.const.TEXT_BATCH_PACKING_SLIP}</a>
                    <a href="javascript:void(0)" onclick="exportSelectedOrders();" class="btn">{$smarty.const.TEXT_BATCH_EXPORT}</a>
                    <a href="javascript:void(0)" onclick="deleteSelectedOrders();" class="btn btn-del">{$smarty.const.TEXT_DELETE_SELECTED}</a>
                    <a href="javascript:void(0)" onclick="changeStatus();" class="btn btn-chng">{$smarty.const.TEXT_CHANGE_STATUS_SELECTED}</a>
                    {if \common\helpers\Acl::checkExtensionAllowed('ShippingCarrierPick', 'allowed')}
                        <a href="javascript:void(0)" onclick="labelSelectedOrders();" class="btn">{$smarty.const.TEXT_BATCH_LABELS}</a>
                    {/if}
{if \common\helpers\Acl::checkExtensionAllowed('OrderMarkers', 'allowed')}
                    <a href="javascript:void(0)" onclick="flagSelectedOrders();" class="btn">{$smarty.const.TEXT_FLAG}</a>
                    <a href="javascript:void(0)" onclick="markerSelectedOrders();" class="btn">{$smarty.const.TEXT_MARKER}</a>
{/if}
                </div>
                <div>
                </div>
            </div>   
            <table class="table table-striped table-selectable table-checkable table-hover table-responsive table-bordered datatable tabl-res double-grid table-orders table-colored" data_ajax="orders/orderlist" checkable_list="">
                <thead>
                    <tr>
                        {foreach $app->controller->view->ordersTable as $tableItem}
                            <th{if $tableItem['not_important'] == 2} class="checkbox-column"{/if}{if $tableItem['not_important'] == 1} class="hidden-xs"{/if}>{$tableItem['title']}</th>
                            {/foreach}
                    </tr>
                </thead>

            </table>
            <div class="btn-wr after disable-btn batch-actions">
                <div>
                    <span class="batch-actions-label">{$smarty.const.TEXT_BATCH_ACTIONS}:</span>
                    <a href="javascript:void(0)" onclick="invoiceSelectedOrders();" class="btn btn-no-margin">{$smarty.const.TEXT_BATCH_INVOICE}</a>
                    <a href="javascript:void(0)" onclick="packingslipSelectedOrders()" class="btn">{$smarty.const.TEXT_BATCH_PACKING_SLIP}</a>
                    <a href="javascript:void(0)" onclick="exportSelectedOrders();" class="btn">{$smarty.const.TEXT_BATCH_EXPORT}</a>
                    <a href="javascript:void(0)" onclick="deleteSelectedOrders();" class="btn btn-del">{$smarty.const.TEXT_DELETE_SELECTED}</a>
                    <a href="javascript:void(0)" onclick="changeStatus();" class="btn btn-chng">{$smarty.const.TEXT_CHANGE_STATUS_SELECTED}</a>
                    {if \common\helpers\Acl::checkExtensionAllowed('ShippingCarrierPick', 'allowed')}
                        <a href="javascript:void(0)" onclick="labelSelectedOrders();" class="btn">{$smarty.const.TEXT_BATCH_LABELS}</a>
                    {/if}
{if \common\helpers\Acl::checkExtensionAllowed('OrderMarkers', 'allowed')}
                    <a href="javascript:void(0)" onclick="flagSelectedOrders();" class="btn">{$smarty.const.TEXT_FLAG}</a>
                    <a href="javascript:void(0)" onclick="markerSelectedOrders();" class="btn">{$smarty.const.TEXT_MARKER}</a>
{/if}
                </div>
                <div>
                </div>
            </div>                
        </div>
    </div>
</div>
<!-- /Orders List -->
        
<div id="status-mes" style="display: none;">
  <form role="form">
    <div class="form-group">
      <label>{$smarty.const.ENTRY_STATUS}</label>
      {Html::dropDownList('change_status', $order->info['order_status'], $ordersStatuses, ['class'=>'form-control', 'options' => $ordersStatusesOptions, 'onChange' => 'return doCheckOrderStatus();', 'id' => 'order-status'])}
    </div>

    <div class="form-group">
        <div class="" id="evaluation_state_force_holder" style="display: none;">
            <div class="f_td">
            </div>
            <div class="f_td">
                {Html::checkbox('evaluation_state_force', true, ['label' => TEXT_EVALUATION_STATE_FORCE, 'id' => 'evaluation_state_force'])}
            </div>
        </div>
        <div class="" id="evaluation_state_restock_holder" style="display: none;">
            <div class="f_td">
            </div>
            <div class="f_td">
                {Html::checkbox('evaluation_state_restock', false, ['label' => TEXT_EVALUATION_STATE_RESTOCK, 'id' => 'evaluation_state_restock'])}
            </div>
        </div>
        <div class="" id="evaluation_state_reset_cancel_holder" style="display: none;">
            <div class="f_td">
            </div>
            <div class="f_td">
                {Html::checkbox('evaluation_state_reset_cancel', false, ['label' => TEXT_EVALUATION_STATE_RESET_CANCEL, 'id' => 'evaluation_state_reset_cancel'])}
            </div>
        </div>
    </div>
           
    <div class="form-group" style="display: none;">
        {Html::checkbox('use_update_amount', false, ['label' => TEXT_UPDATE_PAID_AMOUNT, 'class' => 'upade_paid_on_process', 'id' => 'use_update_amount_checkbox'])}
    </div>
            
    <div class="form-group">
      <label for="comments">{$smarty.const.TABLE_HEADING_COMMENTS}:</label>
      <textarea name="comments" cols="60" rows="5" class="form-control" wrap="soft"></textarea>
    </div>
      
    <div class="form-group">
      <label>
        <input name="notify" type="checkbox"> {$smarty.const.ENTRY_NOTIFY_CUSTOMER}
      </label>
    </div>
  </form>
</div>
        
                                
<script type="text/javascript">
function doCheckOrderStatus() {
    let element = $('div.modal-body select[name="change_status"]');
    $('div.modal-body #evaluation_state_force_holder').hide();
    $('div.modal-body #evaluation_state_restock_holder').hide();
    $('div.modal-body #evaluation_state_reset_cancel_holder').hide();
    $('div.modal-body #evaluation_state_force').prop('checked', true);
    $('div.modal-body #evaluation_state_restock').prop('checked', false);
    $('div.modal-body #evaluation_state_reset_cancel').prop('checked', false);
    if (element.length > 0) {
        console.log($(element).val());
        let evaluation_state_id = $(element).find('option[value="' + $(element).val() + '"]').attr('evaluation_state_id');
        console.log(evaluation_state_id);
        if (evaluation_state_id == '{\common\helpers\Order::OES_DISPATCHED}'
            || evaluation_state_id == '{\common\helpers\Order::OES_DELIVERED}'
        ) {
            $('div.modal-body #evaluation_state_force_holder').show();
        } else if (evaluation_state_id == '{\common\helpers\Order::OES_CANCELLED}') {
            $('div.modal-body #evaluation_state_restock_holder').show();
        } else if (evaluation_state_id == '{\common\helpers\Order::OES_PENDING}') {
            $('div.modal-body #evaluation_state_reset_cancel_holder').show();
        }
        return true;
    }
    return false;
}
$(document).ready(function() {
    doCheckOrderStatus();
});
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
function switchOffCollapse(id) {
    if ($("#"+id).children('i').hasClass('icon-angle-down')) {
        $("#"+id).click();
    }
}
function switchOnCollapse(id) {
    if ($("#"+id).children('i').hasClass('icon-angle-up')) {
        $("#"+id).click();
    }
}
function cancelStatement() {
    var orders_id = $('.table tbody tr.selected').find('input.cell_identify').val();
    $.post("{$app->urlManager->createUrl('orders/orderactions')}", { 'orders_id' : orders_id }, function(data, status){
        if (status == "success") {
            $('#order_management_data .scroll_col').html(data);
            $("#order_management").show();
        } else {
            alert("Request error.");
        }
    },"html");
}
function setFilterState() {
    orig = $('#filterForm, #filterFormHead, #filterModeForm').serialize();
    var url = window.location.origin + window.location.pathname + '?' + orig.replace(/[^&]+=\.?(?:&|$)/g, '')
    window.history.replaceState({ }, '', url);
}
function resetStatement() {
    setFilterState();
    $("#order_management").hide();
    switchOnCollapse('orders_list_collapse');
    var table = $('.table').DataTable();
    table.draw(false);
    $(window).scrollTop(0);
    return false;
}
function onClickEvent(obj, table) {
    var dtable = $(table).DataTable();
    var id = dtable.row('.selected').index();
    $("#row_id").val(id);
    setFilterState();
    var orders_id = $(obj).find('input.cell_identify').val();
    $.post("{$app->urlManager->createUrl('orders/orderactions')}", { 'orders_id' : orders_id }, function(data, status){
        if (status == "success") {
            $('#order_management_data .scroll_col').html(data);
            $("#order_management").show();
        } else {
            alert("Request error.");
        }
    },"html");
}
function onUnclickEvent(obj, table) {

}
function check_form() {
//ajax save
    $("#order_management").hide();
    $.post("{$app->urlManager->createUrl('orders/ordersubmit')}", $('#status_edit').serialize(), function(data, status){
        if (status == "success") {
            $('#order_management_data .scroll_col').html(data);
            $("#order_management").show();
        } else {
            alert("Request error.");
        }
    },"html");
    return false;
}
function deleteOrder() {
    $("#order_management").hide();
    $.post("{$app->urlManager->createUrl('orders/orderdelete')}", $('#orders_edit').serialize(), function(data, status){
        if (status == "success") {
            resetStatement()
        } else {
            alert("Request error.");
        }
    },"html");
    return false;
}
function confirmDeleteOrder(orders_id) {
    $("#order_management").hide();
    $.post("{$app->urlManager->createUrl('orders/confirmorderdelete')}", { 'orders_id' : orders_id }, function(data, status){
        if (status == "success") {
            $('#order_management_data .scroll_col').html(data);
            $("#order_management").show();
            switchOffCollapse('orders_list_collapse');
        } else {
            alert("Request error.");
        }
    },"html");
    return false;
}
function reassignOrder(orders_id) {
    $("#order_management").hide();
    $.post("{$app->urlManager->createUrl('orders/order-reassign')}", { 'orders_id' : orders_id }, function(data, status){
        if (status == "success") {
            $('#order_management_data .scroll_col').html(data);
            $("#order_management").show();
            switchOffCollapse('orders_list_collapse');
        } else {
            alert("Request error.");
        }
    },"html");
    return false;
}
function confirmedReassignOrder() {
    $("#order_management").hide();
    $.post("{$app->urlManager->createUrl('orders/confirmed-order-reassign')}", $('#orders_edit').serialize(), function(data, status){
        if (status == "success") {
            resetStatement()
        } else {
            alert("Request error.");
        }
    },"html");
    return false;
}

$(document).ready(function() {

    $(window).resize(function(){ 
        setTimeout(function(){ 
            var height_box = $('.order-box-list').height() + 2;
            $('#order_management .widget.box').css('min-height', height_box);
        }, 800);        
    })
    $(window).resize();
    
    
    $('.w-tdc.act_row input[type="text"]').prop('disabled', false);
    $('.w-tdc.act_row select').prop('disabled', false);
    
    $('input[name="date"]').click(function() { 
        if($(this).is(':checked')){ 
            $(this).parents().siblings('div.w-tdc').removeClass('act_row');
            $(this).parents('.w-tdc').addClass('act_row');
            $('.w-tdc input[type="text"]').prop('disabled', true);
            $('.w-tdc select').prop('disabled', true);
            $('.w-tdc.act_row input[type="text"]').prop('disabled', false);
            $('.w-tdc.act_row select').prop('disabled', false);
        }
    });

    $('#fcById').off('click').click( function () {
        if ($(this).is(':checked')) {
          $(this).parent().addClass('active_options');
          $("#fcLike").prop("checked", false);
          $("#fcLike").parent().removeClass('active_options');
          $("#fcCode").prop("disabled", true);
          $("#fcId").prop("disabled", false);
        } else {
          $("#fcCode").prop("disabled", false);
          $("#fcId").prop("disabled", true);
        }
      }
    );
    $('#fcLike').off('click').click( function () {
        if ($(this).is(':checked')) {
          $(this).parent().addClass('active_options');
          $("#fcById").prop("checked", false);
          $("#fcById").parent().removeClass('active_options');
          $("#fcCode").prop("disabled", false);
          $("#fcId").prop("disabled", true);
        } else {
          $("#fcCode").prop("disabled", true);
          $("#fcId").prop("disabled", false);
        }
      }
    );


    $('#fpFrom').off('click').click( function () {
        if ($(this).is(':checked')) {
          $(this).parent().addClass('active_options');
          $("#fpClass").prop("disabled", false);
          $("#fpFromSumm").prop("disabled", false);
        } else {
          $(this).parent().removeClass('active_options');
          $("#fpFromSumm").prop("disabled", true);
          if (!$("#fpTo").is(':checked')) {
            $("#fpClass").prop("disabled", true);
          }
        }
      }
    );
    $('#fpTo').off('click').click( function () {
        if ($(this).is(':checked')) {
          $(this).parent().addClass('active_options');
          $("#fpClass").prop("disabled", false);
          $("#fpToSumm").prop("disabled", false);
        } else {
          $(this).parent().removeClass('active_options');
          $("#fpToSumm").prop("disabled", true);
          if (!$("#fpFrom").is(':checked')) {
            $("#fpClass").prop("disabled", true);
          }
        }
      }
    );

    $('body').on('click', 'th.checkbox-column .uniform', function() { 
        if($(this).is(':checked')){
            $('tr.checkbox-column .uniform').prop('checked', true).uniform('update');
            $('.order-box-list .btn-wr').removeClass('disable-btn');
        }else{
            $('.order-box-list .btn-wr').addClass('disable-btn');
        }
    });
    
    $('select.select2-offscreen').change(function(){ 
        setTimeout(function(){ 
            var height_box = $('.order-box-list').height() + 2;
            $('#order_management .widget.box').css('min-height', height_box);
        }, 800); 
    });

    var $platforms = $('.js_platform_checkboxes');
    var check_platform_checkboxes = function(){
        var checked_all = true;
        $platforms.not('[value=""]').each(function () {
            if (!this.checked) checked_all = false;
        });
        $platforms.filter('[value=""]').each(function() {
            this.checked = checked_all
        });
    };
    check_platform_checkboxes();
    $platforms.on('click',function(){
        var self = this;
        if (this.value=='') {
            $platforms.each(function(){
                this.checked = self.checked;
            });
        }else{
            var checked_all = this.checked;
            if ( checked_all ) {
                $platforms.not('[value=""]').each(function () {
                    if (!this.checked) checked_all = false;
                });
            }
            $platforms.filter('[value=""]').each(function() {
                this.checked = checked_all
            });
        }
    });
    {if $departments}
    var $departments = $('.js_department_checkboxes');
    var check_department_checkboxes = function(){
        var checked_all = true;
        $departments.not('[value=""]').each(function () {
            if (!this.checked) checked_all = false;
        });
        $departments.filter('[value=""]').each(function() {
            this.checked = checked_all
        });
    };
    check_department_checkboxes();
    $departments.on('click',function(){
        var self = this;
        if (this.value=='') {
            $departments.each(function(){
                this.checked = self.checked;
            });
        }else{
            var checked_all = this.checked;
            if ( checked_all ) {
                $departments.not('[value=""]').each(function () {
                    if (!this.checked) checked_all = false;
                });
            }
            $departments.filter('[value=""]').each(function() {
                this.checked = checked_all
            });
        }
    });
    {/if}
});

function resetFilter() {
    $('select[name="by"]').val('');
    $('input[name="search"]').val('');
    $("#presel").prop("checked", true);
    $("#exact").prop("checked", false);
    $('.js_platform_checkboxes').prop("checked", false);
    $('.js_department_checkboxes').prop("checked", false);
    $('select[name="interval"]').val('');
    $('input[name="from"]').val('');
    $('input[name="to"]').val('');
    $('select[name="status"]').val('');
    $('input[name="delivery_country"]').val('');
    $('input[name="delivery_state"]').val('');
    $("#fcById").prop("checked", true);
    $("#fcLike").prop("checked", false);
    $("#fcId").val('');
    $("#fcCode").val('');
    $("#fcCode").prop('disabled', true);
    $("#fcId").prop('disabled', false);

    $("#fpFrom").prop("checked", false);
    $("#fpTo").prop("checked", false);
    $("#fpClass").val('');
    $("#fpFromSumm").val('');
    $("#fpToSumm").val('');
    $("#fpFromSumm").prop('disabled', true);
    $("#fpToSumm").prop('disabled', true);
    $("#fpClass").prop('disabled', true);
    $('select[name="walkin"]').val('');
    $("input[name='flag'][value='0']").prop("checked", true);
    $("input[name='marker'][value='0']").prop("checked", true);
    $("select[data-role=multiselect]").multipleSelect('uncheckAll');
    $("select[data-role=multiselect-radio]").multipleSelect('uncheckAll');

    $("#row_id").val(0);
    $('label.active_options, span.active_options').removeClass('active_options');
    resetStatement();
    return false;  
}
    
function applyFilter() {
    resetStatement();
    return false;    
}

{if \common\helpers\Acl::checkExtension('OrderMarkers', 'allowed')}
function sendOrderFlag(id, flag_state) {
    var selected_ids = [];
    selected_ids[0] = id;
    if (typeof flag_state == "undefined") flag_state = 0;
    sendOrdersFlag(selected_ids, flag_state);
}
function flagSelectedOrders() {
    if (getTableSelectedCount() > 0) {
        var selected_ids = getTableSelectedIds();
        sendOrdersFlag(selected_ids, 0);
    }
    return false;
}
function sendOrdersFlag(selected_ids, flag_state) {
    bootbox.dialog({
        message: '{foreach $app->controller->view->flags as $flag}<label class="{$flag['class']}" style="{$flag['style']}">{\yii\helpers\Html::radio('o_flag', false, ['value' => $flag['id']])|escape:'javascript'}<span>{$flag['text']}</span></label><br>{/foreach}',
        title: "{$smarty.const.TEXT_SET_FLAG}",
        buttons: {
                success: {
                        label: "{$smarty.const.IMAGE_SAVE}",
                        className: "btn",
                        callback: function() {
                            $.post("{$app->urlManager->createUrl(['extensions/', 'module' => 'OrderMarkers', 'action' => 'adminActionSetFlag'])}", { 'selected_ids' : selected_ids, 'o_flag' : $('input:checked[name="o_flag"]').val() }, function(data, status){
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

                        }
                }
        }
    });
    setTimeout(function(){
        $('input[name="o_flag"][value="'+flag_state+'"]').prop('checked', 'checked');
    }, 200);
}
function sendOrderMarker(id, marker_state) {
    var selected_ids = [];
    selected_ids[0] = id;
    sendOrdersMarker(selected_ids, marker_state);
}
function markerSelectedOrders() {
    if (getTableSelectedCount() > 0) {
        var selected_ids = getTableSelectedIds();
        sendOrdersMarker(selected_ids, 0);
    }
    return false;
}
function sendOrdersMarker(selected_ids, marker_state) {

        bootbox.dialog({
                message: '{foreach $app->controller->view->markers as $marker}<label class="{$marker['class']}" style="{$marker['style']}">{\yii\helpers\Html::radio('o_marker', false, ['value' => $marker['id']])|escape:'javascript'}<span>{$marker['text']}</span></label><br>{/foreach}',
                title: "{$smarty.const.TEXT_SET_MARKER}",
                buttons: {
                        success: {
                                label: "{$smarty.const.IMAGE_SAVE}",
                                className: "btn",
                                callback: function() {
                                    $.post("{$app->urlManager->createUrl(['extensions/', 'module' => 'OrderMarkers', 'action' => 'adminActionSetMarker'])}", { 'selected_ids' : selected_ids, 'o_marker' : $('input:checked[name="o_marker"]').val() }, function(data, status){
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
                                        
                                }
                        }
                }
        });
    setTimeout(function(){
        $('input[name="o_marker"][value="'+marker_state+'"]').prop('checked', 'checked');
    }, 200);
}
{/if}
function invoiceSelectedOrders() {
    if (getTableSelectedCount() > 0) {

        var form = document.createElement("form");
        form.target = "_blank";    
        form.method = "POST";
        form.action = 'orders/ordersbatch?pdf=invoice&action=selected';

        var selected_ids = getTableSelectedIds();
        var hiddenField = document.createElement("input");              
        hiddenField.setAttribute("name", "orders");
        hiddenField.setAttribute("value", selected_ids);
        form.appendChild(hiddenField);

        document.body.appendChild(form);
        form.submit();
    }
    
    return false;
}

function packingslipSelectedOrders() {
    if (getTableSelectedCount() > 0) {

        var form = document.createElement("form");
        form.target = "_blank";    
        form.method = "POST";
        form.action = 'orders/ordersbatch?action=selected';

        var selected_ids = getTableSelectedIds();
        var hiddenField = document.createElement("input");              
        hiddenField.setAttribute("name", "orders");
        hiddenField.setAttribute("value", selected_ids);
        form.appendChild(hiddenField);

        document.body.appendChild(form);
        form.submit();
    }
    
    return false;
}

function exportSelectedOrders() {
    if (getTableSelectedCount() > 0) {

        var form = document.createElement("form");
        form.target = "_blank";
        form.method = "POST";
        form.action = 'orders/ordersexport';

        var selected_ids = getTableSelectedIds();
        var hiddenField = document.createElement("input");
        hiddenField.setAttribute("name", "orders");
        hiddenField.setAttribute("value", selected_ids);
        form.appendChild(hiddenField);

        document.body.appendChild(form);
        form.submit();
    }
    
    return false;
}

function deleteSelectedOrders() {
    if (getTableSelectedCount() > 0) {
        var selected_ids = getTableSelectedIds();
        
        bootbox.dialog({
                message: "Restock product quantity?",
                title: "Delete selected Orders",
                buttons: {
                        success: {
                                label: "Yes",
                                className: "btn-delete",
                                callback: function() {
                                    $.post("orders/ordersdelete", { 'selected_ids' : selected_ids, 'restock' : '1' }, function(data, status){
                                        if (status == "success") {
                                            resetStatement();
                                        } else {
                                            alert("Request error.");
                                        }
                                    },"html");
                                }
                        },
                        danger: {
                                label: "No",
                                className: "btn-delete",
                                callback: function() {
                                    $.post("orders/ordersdelete", { 'selected_ids' : selected_ids, 'restock' : '0' }, function(data, status){
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

function changeStatus() {
  if (getTableSelectedCount() > 0) {
    var selected_ids = getTableSelectedIds();
    bootbox.dialog({
      message: $('#status-mes').html(),
      title: "{$smarty.const.TABLE_HEADING_COMMENTS_STATUS}",
      buttons: {
        success: {
          label: "OK",
          className: "btn-confirm",
          callback: function() {
            $.post("orders/set-status", {
              'selected_ids' : selected_ids,
              'status':$('div.modal-body select[name="change_status"]').val(),
              'force':$('div.modal-body #evaluation_state_force').prop('checked'),
              'restock':$('div.modal-body #evaluation_state_restock').prop('checked'),
              'cancel':$('div.modal-body #evaluation_state_reset_cancel').prop('checked'),
              'comments':$('div.modal-body textarea[name="comments"]').val(),
              'notify':$('div.modal-body input[name="notify"]').prop('checked'),
              'paid':$('div.modal-body #use_update_amount_checkbox').prop('checked'),
            }, function(data, status){
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

$(document).ready(function(){
	//===== Date Pickers  =====//
	$( ".datepicker" ).datepicker({
		changeMonth: true,
                changeYear: true,
		showOtherMonths:true,
		autoSize: false,
		dateFormat: '{$smarty.const.DATE_FORMAT_DATEPICKER}',
                onSelect: function (e) { 
                    if ($(this).val().length > 0) { 
                      $(this).siblings('span').addClass('active_options');
                    }else{ 
                      $(this).siblings('span').removeClass('active_options');
                    }
                  }
        });
        $("select[data-role=multiselect]").multipleSelect({
            multiple: true,
            filter: true,
        });
		
		$('[data-role=multiselect-radio]').multipleSelect({
            multiple: false,
            filter: true,
            single: true,
            onClick : function(option){
                applyFilter();
            }
        });
 
        $('#selectCountry').autocomplete({
            source: "orders/countries",
            minLength: 0,
            autoFocus: true,
            delay: 0,
            appendTo: '.f_td_country',
            open: function (e, ui) {
              if ($(this).val().length > 0) {
                var acData = $(this).data('ui-autocomplete');
                acData.menu.element.find('a').each(function () {
                  var me = $(this);
                  var keywords = acData.term.split(' ').join('|');
                  me.html(me.text().replace(new RegExp("(" + keywords + ")", "gi"), '<b>$1</b>'));
                });
                $(this).siblings('label').addClass('active_options');
              }else{ 
                  $(this).siblings('label').removeClass('active_options');
              }
            },
            select: function(event, ui) {
                if ($(this).val().length > 0) { 
                    $(this).siblings('label').addClass('active_options');
                }else{ 
                    $(this).siblings('label').removeClass('active_options');
                }
                $('input[name="delivery_state"]').prop('disabled', true);
                if(ui.item.value != null){ 
                    $('input[name="delivery_state"]').prop('disabled', false);
                }
            }
        }).focus(function () {
          $(this).autocomplete("search");
          if ($(this).val().length > 0) { 
                    $(this).siblings('label').addClass('active_options');
                }else{ 
                    $(this).siblings('label').removeClass('active_options');
                }
        });
        
        $('#selectState').autocomplete({
            // source: "orders/state?country=" + $('#selectCountry').val(),
            source: function(request, response) {
                $.ajax({
                    url: "orders/state",
                    dataType: "json",
                    data: {
                        term : request.term,
                        country : $("#selectCountry").val()
                    },
                    success: function(data) {
                        response(data);
                    }
                });
            },
            minLength: 0,
            autoFocus: true,
            delay: 0,
            appendTo: '.f_td_state',
            open: function (e, ui) {
              if ($(this).val().length > 0) {
                var acData = $(this).data('ui-autocomplete');
                acData.menu.element.find('a').each(function () {
                  var me = $(this);
                  var keywords = acData.term.split(' ').join('|');
                  me.html(me.text().replace(new RegExp("(" + keywords + ")", "gi"), '<b>$1</b>'));
                });
                $(this).siblings('label').addClass('active_options');
              }else{ 
                  $(this).siblings('label').removeClass('active_options');
              }
            },
            select: function(event, ui) {
                if ($(this).val().length > 0) { 
                    $(this).siblings('label').addClass('active_options');
                }else{ 
                    $(this).siblings('label').removeClass('active_options');
                }
            }
        }).focus(function () {
          $(this).autocomplete("search");
          if ($(this).val().length > 0) { 
                $(this).siblings('label').addClass('active_options');
            }else{ 
                $(this).siblings('label').removeClass('active_options');
            }
        });  
});

</script>
{if \common\helpers\Acl::checkExtensionAllowed('ShippingCarrierPick', 'allowed')}
    {assign var="ext" value=\common\helpers\Acl::checkExtensionAllowed('ShippingCarrierPick', 'allowed')}
    {$ext::orderIndexJs()}
{/if}
<!--===Actions ===-->
    <div class="row right_column" id="order_management">
        <div class="widget box">
            <div class="widget-content fields_style" id="order_management_data">
                <div class="scroll_col"></div>
            </div>
        </div>
    </div>
</div>
<!--===Actions ===-->

<!-- /Page Content -->
