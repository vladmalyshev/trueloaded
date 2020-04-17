<!--=== Page Header ===-->
<!--<div class="page-header">
    <div class="page-title">
        <h3>{$app->controller->view->headingTitle}</h3>
    </div>
</div>-->
<!-- /Page Header -->

<!--=== Page Content ===-->
<div class="widget box box-wrapp-blue filter-wrapp">
    <div class="widget-header filter-title">
        <h4>{$smarty.const.TEXT_FILTER}</h4>
        <div class="toolbar no-padding">
          <div class="btn-group">
            <span class="btn btn-xs widget-collapse"><i class="icon-angle-down"></i></span>
          </div>
        </div>
    </div>
    <div class="widget-content dis_module">
        
            <form id="filterForm" name="filterForm" onsubmit="return applyFilter();">
                <div class="filter-box">
                
            <div class="f_row">
                <div class="f_td">
                    <label>{$smarty.const.TEXT_SEARCH_BY}</label>
                </div>
                <div class="f_td">
                    <select class="form-control" name="by">
                        {foreach $app->controller->view->filters->by as $Item}
                            <option {$Item['selected']} value="{$Item['value']}">{$Item['name']}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="f_td f_td2">
                    <input type="text" name="search" value="{$app->controller->view->filters->search}" class="form-control" />
                </div>
            </div>
            <div class="f_row f_row_d act_row">
                <div class="f_td">
                    <label>{$smarty.const.TEXT_ORDER_PLACED}</label>
                </div>
                <div class="f_td f_td_radio">
                    <label class="radio_label"><input type="radio" name="date" value="presel" id="presel" class="form-control" {if $app->controller->view->filters->presel}checked{/if} /> {$smarty.const.TEXT_PRE_SELECTED}</label>
                </div>
                <div class="f_td f_td2">
                    <select name="interval" class="form-control" {if $app->controller->view->filters->exact}disabled{/if}>
                        {foreach $app->controller->view->filters->interval as $Item}
                            <option {$Item['selected']} value="{$Item['value']}">{$Item['name']}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="f_row  f_row_d">
                <div class="f_td">
                </div>
                <div class="f_td f_td_radio">
                    <label class="radio_label"><input type="radio" name="date" value="exact" id="exact" class="form-control" {if $app->controller->view->filters->exact}checked{/if} /> {$smarty.const.TEXT_EXACT_DATES}</label>
                </div>
                <div class="f_td f_td2">
                    <span>{$smarty.const.TEXT_FROM}</span>
                    <input id="from_date" type="text" value="{$app->controller->view->filters->from}" autocomplete="off" name="from" class="datepicker form-control form-control-small" {if $app->controller->view->filters->presel}disabled{/if} />
                    <span class="sp_marg">{$smarty.const.TEXT_TO}</span>
                    <input id="to_date" type="text" value="{$app->controller->view->filters->to}" autocomplete="off" name="to" class="datepicker form-control form-control-small" {if $app->controller->view->filters->presel}disabled{/if} />
                </div>
            </div>
            <div class="f_row">
                <div class="f_td">
                    <label>{$smarty.const.TEXT_STATUS}</label>
                </div>
                <div class="f_td">
                    <select name="status" class="form-control">
                        {foreach $app->controller->view->filters->status as $Item}
                            <option {$Item['selected']} value="{$Item['value']}">{$Item['name']}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="f_td f_td2">
                </div>
            </div>
            <div class="f_row">
                <div class="f_td">
                    <label>{$smarty.const.TEXT_BY_DELIVERY}</label>
                </div>
                <div class="f_td f_td_country">
                    <input name="delivery_country" value="{$app->controller->view->filters->delivery_country}" id="selectCountry" type="text" class="form-control" placeholder="{$smarty.const.TEXT_TYPE_COUNTRY}" />
                </div>
                {if $app->controller->view->showState == true}
                <div class="f_td f_td2 f_td_state">
                    <input name="delivery_state" value="{$app->controller->view->filters->delivery_state}" id="selectState" type="text" class="form-control" placeholder="{$smarty.const.TEXT_TYPE_COUNTY}" {if $app->controller->view->filters->delivery_country == ''}disabled{/if} />
                </div>
                {/if}
            </div>
            <div class="f_row">
                <div class="f_td">
                </div>
                <div class="f_td">
                </div>
                <div class="f_td f_td2">
                    <a href="javascript:void(0)" onclick="return resetFilter();" class="btn">{$smarty.const.TEXT_RESET}</a>&nbsp;&nbsp;&nbsp;<button type="submit" class="btn btn-primary">{$smarty.const.TEXT_SEARCH}</button>
                </div>
            </div>
            <input type="hidden" name="row" id="row_id" value="{$app->controller->view->filters->row}" />
            </div>
            </form>
        
    </div>
</div>

<!--===Orders List ===-->
<div class="order-wrap">    
<div class="row order-box-list order-sc-text">
    <div class="col-md-12">
        <div class="widget-content dis_module">
            <div class="btn-wr after btn-wr-top btn-wr-top1 disable-btn">
                <div>
                    <a href="javascript:void(0)" onclick="exportSelectedOrders();" class="btn">{$smarty.const.TEXT_BATCH_EXPORT}</a><a href="javascript:void(0)" onclick="deleteSelectedOrders();" class="btn btn-del">{$smarty.const.TEXT_DELETE_SELECTED}</a>
                </div>
                <div>
                </div>
            </div>   
            <table class="table table-striped table-selectable table-checkable table-hover table-responsive table-bordered datatable tabl-res double-grid table-orders" data_ajax="purchase-orders/purchase-orders-list" checkable_list="">
                <thead>
                    <tr>
                        {foreach $app->controller->view->ordersTable as $tableItem}
                            <th{if $tableItem['not_important'] == 2} class="checkbox-column"{/if}{if $tableItem['not_important'] == 1} class="hidden-xs"{/if}>{$tableItem['title']}</th>
                            {/foreach}
                    </tr>
                </thead>

            </table>
            <div class="btn-wr after disable-btn">
                <div>
                    <a href="javascript:void(0)" onclick="exportSelectedOrders();" class="btn">{$smarty.const.TEXT_BATCH_EXPORT}</a><a href="javascript:void(0)" onclick="deleteSelectedOrders();" class="btn btn-del">{$smarty.const.TEXT_DELETE_SELECTED}</a>
                </div>
                <div>
                </div>
            </div>                
        </div>
    </div>
</div>
<!-- /Orders List -->
        
        
                                
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
    //var selected_messages_ids = [];
    var selected_messages_count = 0;
    $('input:checkbox:checked.uniform').each(function(j, cb) {
        var aaa = $(cb).closest('td').find('.cell_identify').val();
        if (typeof(aaa) != 'undefined') {
            //selected_messages_ids[selected_messages_count] = aaa;
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
    $.post("{$app->urlManager->createUrl('purchase-orders/purchase-orders-actions')}", { 'orders_id' : orders_id }, function(data, status){
        if (status == "success") {
            $('#order_management_data .scroll_col').html(data);
            $("#order_management").show();
        } else {
            alert("Request error.");
            //$("#order_management").hide();
        }
    },"html");
}
function setFilterState() {
    orig = $('#filterForm').serialize();
    var url = window.location.origin + window.location.pathname + '?' + orig.replace(/[^&]+=\.?(?:&|$)/g, '')
    window.history.replaceState({ }, '', url);
}
function resetStatement() {
    setFilterState();
    if ($('#customers_edit').is('form')){
      createOrder();
      $(window).scrollTop(0);
      return;
    }
    $("#order_management").hide();
    switchOnCollapse('orders_list_collapse');
    var table = $('.table').DataTable();
    table.draw(false);
    //$('.table tbody tr:eq(0)').click();
    $(window).scrollTop(0);
    return false;
}
function onClickEvent(obj, table) {
    var dtable = $(table).DataTable();
    var id = dtable.row('.selected').index();
    $("#row_id").val(id);
    setFilterState();
    //$("#order_management").hide();
    var orders_id = $(obj).find('input.cell_identify').val();
    $.post("{$app->urlManager->createUrl('purchase-orders/purchase-orders-actions')}", { 'orders_id' : orders_id }, function(data, status){
        if (status == "success") {
            $('#order_management_data .scroll_col').html(data);
            $("#order_management").show();
        } else {
            alert("Request error.");
            //$("#order_management").hide();
        }
    },"html");
}
function onUnclickEvent(obj, table) {
    //$("#order_management").hide();
}
function check_form() {
//ajax save
    $("#order_management").hide();
    //var orders_id = $( "input[name='orders_id']" ).val();
    $.post("{$app->urlManager->createUrl('purchase-orders/ordersubmit')}", $('#status_edit').serialize(), function(data, status){
        if (status == "success") {
            $('#order_management_data .scroll_col').html(data);
            $("#order_management").show();
    /*        
            switchOnCollapse('orders_list_collapse');
            var table = $('.table').DataTable();
            table.draw(false);
            setTimeout('$(".cell_identify[value=\''+orders_id+'\']").click();', 500);
            //$(".cell_identify[value='"+orders_id+"']").click();
    */        
        } else {
            alert("Request error.");
            //$("#order_management").hide();
        }
    },"html");
    //$('#order_management_data').html('');
    return false;
}
function deleteOrder() {
    $("#order_management").hide();
    $.post("{$app->urlManager->createUrl('purchase-orders/orderdelete')}", $('#orders_edit').serialize(), function(data, status){
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
    $.post("{$app->urlManager->createUrl('purchase-orders/confirmorderdelete')}", { 'orders_id' : orders_id }, function(data, status){
        if (status == "success") {
            $('#order_management_data .scroll_col').html(data);
            $("#order_management").show();
            switchOffCollapse('orders_list_collapse');
        } else {
            alert("Request error.");
            //$("#order_management").hide();
        }
    },"html");
    return false;
}
function reassignOrder(orders_id) {
    $("#order_management").hide();
    $.post("{$app->urlManager->createUrl('purchase-orders/order-reassign')}", { 'orders_id' : orders_id }, function(data, status){
        if (status == "success") {
            $('#order_management_data .scroll_col').html(data);
            $("#order_management").show();
            switchOffCollapse('orders_list_collapse');
        } else {
            alert("Request error.");
            //$("#order_management").hide();
        }
    },"html");
    return false;
}
function confirmedReassignOrder() {
    $("#order_management").hide();
    $.post("{$app->urlManager->createUrl('purchase-orders/confirmed-order-reassign')}", $('#orders_edit').serialize(), function(data, status){
        if (status == "success") {
            resetStatement()
        } else {
            alert("Request error.");
        }
    },"html");
    return false;
}
                                
function createOrder(){
    $.post("{$app->urlManager->createUrl('purchase-orders/createorder')}", $('form[name=create_order]').serialize(), function(data, status){
        if (status == "success") {
            switchOffCollapse('orders_list_collapse');
            $('#order_management_data .scroll_col').html(data);
            $("#order_management").show();
        } else {
            alert("Request error.");
        }  
    },"html");
    return false;                                  
}
                                
function createOrderProcess(){
    $.post("{$app->urlManager->createUrl('purchase-orders/createorderprocess')}", $('form[name=create_order]').serialize(), function(data, status){
        if (status == "success") {
            switchOffCollapse('orders_list_collapse');
            $('#order_management_data .scroll_col').html(data);
            $("#order_management").show();
        } else {
            alert("Request error.");
        }
    },"html");
    return false;
}   
function editCustomer(customers_id) {
    $("#order_management").hide();
    switchOffCollapse('orders_list_collapse');
    $.post("{$app->urlManager->createUrl('customers/customeredit')}", { 'customers_id' : customers_id }, function(data, status){
        if (status == "success") {
            $('#order_management_data .scroll_col').html(data);
            $("#order_management").show();
            switchOffCollapse('orders_list_collapse');
        } else {
            alert("Request error.");
            //$("#order_management").hide();
        }
    },"html");
    return false;
}
function loadCustomer(form){
    $.get("{$app->urlManager->createUrl('purchase-orders/createorder')}", $(form).serialize(), function(data, status){
        if (status == "success") {
            $('#order_management_data .scroll_col').html(data);
            $("#order_management").show();
            switchOffCollapse('orders_list_collapse');
        } else {
            alert("Request error.");
            //$("#order_management").hide();
        }
    },"html");
    return false;
}

function addProduct(id){
    $("#order_management").hide();
    $(window).scrollTop(0);
    $.get("{$app->urlManager->createUrl('purchase-orders/addproduct')}", $('form[name=search]').serialize()+'&oID='+id, function(data, status){
        if (status == "success") {
            $('#order_management_data .scroll_col').html(data);
            $("#order_management").show();
            switchOffCollapse('customers_list_collapse');
        } else {
            alert("Request error.");
            //$("#customer_management").hide();
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
    
    
    $('.f_row.f_row_d.act_row input[type="text"]').prop('disabled', false);
    $('.f_row.f_row_d.act_row select').prop('disabled', false);
    
    $('input[name="date"]').click(function() { 
        if($(this).is(':checked')){ 
            $(this).parents().siblings('div.f_row').removeClass('act_row');
            $(this).parents('.f_row').addClass('act_row');
            $('.f_row.f_row_d input[type="text"]').prop('disabled', true);
            $('.f_row.f_row_d select').prop('disabled', true);
            $('.f_row.f_row_d.act_row input[type="text"]').prop('disabled', false);
            $('.f_row.f_row_d.act_row select').prop('disabled', false);
        }
    });    

    $('body').on('click', 'th.checkbox-column .uniform', function() { 
        if($(this).is(':checked')){
			$('tr.checkbox-column .uniform').prop('checked', true);
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

});

function resetFilter() {
    $('select[name="by"]').val('');
    $('input[name="search"]').val('');
    $("#presel").prop("checked", true);
    $("#exact").prop("checked", false);
    $('.js_platform_checkboxes').prop("checked", false);
    $('select[name="interval"]').val('');
    $('input[name="from"]').val('');
    $('input[name="to"]').val('');
    $('select[name="status"]').val('');
    $('input[name="delivery_country"]').val('');
    $('input[name="delivery_state"]').val('');
    $("#row_id").val(0);
    resetStatement();
    return false;  
}
    
function applyFilter() {
    resetStatement();
    return false;    
}
    
function invoiceSelectedOrders() {
    if (getTableSelectedCount() > 0) {

        var form = document.createElement("form");
        form.target = "_blank";    
        form.method = "POST";
        form.action = 'purchase-orders/ordersbatch?pdf=invoice&action=selected';

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
        form.action = 'purchase-orders/ordersbatch?action=selected';

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
        form.action = 'purchase-orders/ordersexport';

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
                message: "Are you sure you want to delete the selected orders?",
                title: "Delete selected Orders",
                buttons: {
                        confirm: {
                                label: "Yes",
                                className: "btn-delete",
                                callback: function() {
                                    $.post("purchase-orders/ordersdelete", { 'selected_ids' : selected_ids, 'restock' : '1' }, function(data, status){
                                        if (status == "success") {
                                            resetStatement();
                                        } else {
                                            alert("Request error.");
                                        }
                                    },"html");
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
    return false;
}

$(document).ready(function(){
	//===== Date Pickers  =====//
	$( ".datepicker" ).datepicker({
		changeMonth: true,
                changeYear: true,
		showOtherMonths:true,
		autoSize: false,
		dateFormat: '{$smarty.const.DATE_FORMAT_DATEPICKER}'
        });
        /*$( "select[name='interval']" ).focus(function() {
            $("#presel").prop("checked", true);
            $("#exact").prop("checked", false);
        });
        $( "#from_date, #to_date" ).focus(function() {
            $("#presel").prop("checked", false);
            $("#exact").prop("checked", true);
        });*/
        
        $('#selectCountry').autocomplete({
            source: "purchase-orders/countries",
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
              }
            },
            select: function(event, ui) {
                $('input[name="delivery_state"]').prop('disabled', true);
                if(ui.item.value != null){ 
                    $('input[name="delivery_state"]').prop('disabled', false);
                }
            }
        }).focus(function () {
          $(this).autocomplete("search");
        });
        
        $('#selectState').autocomplete({
            // source: "purchase-orders/state?country=" + $('#selectCountry').val(),
            source: function(request, response) {
                $.ajax({
                    url: "purchase-orders/state",
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
              }
            }
        }).focus(function () {
          $(this).autocomplete("search");
        });      
});

</script>
<!--===Actions ===-->
    <div class="row right_column" id="order_management">
        <div class="widget box">
            <div class="widget-content fields_style dis_module" id="order_management_data">
                <div class="scroll_col"></div>
            </div>
        </div>
    </div>
</div>
<!--===Actions ===-->

<!-- /Page Content -->