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
    <div class="widget-content">
        
            <form id="filterForm" name="filterForm" onsubmit="return applyFilter();">
                {if $isMultiPlatform}
                <div class="f_row f_row_pl">
                    <div class="f_td">
                    <label>{$smarty.const.TEXT_COMMON_PLATFORM_FILTER}</label>
                    </div>
                    <div class="f_td f_td_radio ftd_block">
                      <div><label class="radio_label"><input type="checkbox" name="platform[]" class="js_platform_checkboxes" value=""> {$smarty.const.TEXT_COMMON_PLATFORM_FILTER_ALL}</label></div>
                      {foreach $platforms as $platform}
                        <div><label class="radio_label"><input type="checkbox" name="platform[]" class="js_platform_checkboxes" value="{$platform['id']}" {if in_array($platform['id'], $app->controller->view->filters->platform)} checked="checked"{/if}> {$platform['text']}</label></div>
                      {/foreach}
                    </div>
                </div>
                {/if}
                <div class="filter-box {if $isMultiPlatform}filter-box-pl{/if}">
                
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
        <div class="widget-content">
            <div class="btn-wr after btn-wr-top btn-wr-top1 disable-btn">
                <div>
                    <a href="javascript:void(0)" onclick="exportSelectedOrders();" class="btn">{$smarty.const.TEXT_BATCH_EXPORT}</a><a href="javascript:void(0)" onclick="deleteSelectedOrders();" class="btn btn-del">{$smarty.const.TEXT_DELETE_SELECTED}</a>
                </div>
                <div>
                </div>
            </div>   
            <table class="table table-striped table-selectable table-checkable table-hover table-responsive table-bordered datatable tabl-res double-grid table-orders" data_ajax="subscribers/subscribers-list" checkable_list="">
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
    var subscribers_id = $('.table tbody tr.selected').find('input.cell_identify').val();
    $.post("{$app->urlManager->createUrl('subscribers/subscribers-actions')}", { 'subscribers_id' : subscribers_id }, function(data, status){
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
   // return false;
    var dtable = $(table).DataTable();
    var id = dtable.row('.selected').index();
    $("#row_id").val(id);
    setFilterState();
    //$("#order_management").hide();
    var subscribers_id = $(obj).find('input.cell_identify').val();
    $.post("{$app->urlManager->createUrl('subscribers/subscribers-actions')}", { 'subscribers_id' : subscribers_id }, function(data, status){
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

function deleteOrder() {
    $("#order_management").hide();
    $.post("{$app->urlManager->createUrl('subscribers/orderdelete')}", $('#orders_edit').serialize(), function(data, status){
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
    $.post("{$app->urlManager->createUrl('subscribers/confirmorderdelete')}", { 'orders_id' : orders_id }, function(data, status){
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
    $("#row_id").val(0);
    resetStatement();
    return false;  
}
    
function applyFilter() {
    resetStatement();
    return false;    
}
    

function exportSelectedOrders() {
    if (getTableSelectedCount() > 0) {

        var form = document.createElement("form");
        form.target = "_blank";
        form.method = "POST";
        form.action = 'subscribers/subscribersexport';

        var selected_ids = getTableSelectedIds();
        var hiddenField = document.createElement("input");
        hiddenField.setAttribute("name", "subscribers");
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
                message: "Delete selected subscribers",
                title: "Delete selected subscribers",
                buttons: {
                        success: {
                                label: "Yes",
                                className: "btn-delete",
                                callback: function() {
                                    $.post("subscribers/subscribersdelete", { 'selected_ids' : selected_ids, 'restock' : '1' }, function(data, status){
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

</script>
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