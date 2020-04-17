<!--=== Page Header ===-->
<div class="page-header">
    <div class="page-title">
        <h3>{$app->controller->view->headingTitle}</h3>
    </div>
</div>
<!-- /Page Header -->

<!--=== Page Content ===-->

<!--===Customers List ===-->
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
                {if $departments}
                <div class="f_row f_row_pl_cus f_row_pl">
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
                <div class="f_row f_row_pl_cus f_row_pl">
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
                <div class="filter-box filter-box-cus {if $isMultiPlatform}filter-box-pl{/if}">
                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    
                    <tr>
                        <td align="right">
                            <label>{$smarty.const.TEXT_SEARCH_BY}</label>
                        </td>
                        <td>                            
                            <select class="form-control" name="by">
                                {foreach $app->controller->view->filters->by as $Item}
                                    <option {$Item['selected']} value="{$Item['value']}">{$Item['name']}</option>
                                {/foreach}
                            </select>
                        </td>
                        <td>
                            <input type="text" name="search" value="{$app->controller->view->filters->search}" class="form-control" />
                        </td>
                    </tr>
                    {if $app->controller->view->filters->showGroup}
                    <tr>
                        <td align="right">
                            <label>{$smarty.const.TEXT_GROUP}</label>
                        </td>
                        <td colspan="2">
                            <div class="f_td f_td_group">
                                <input name="group" id="selectGroup" type="text" value="{$app->controller->view->filters->group}" placeholder="{$smarty.const.TEXT_PLEASE_CHOOSE_GROUP}" class="form-control">
                            </div>
                        </td>
                    </tr>
                    {/if}
                    <tr>
                        <td align="right"><label>{$smarty.const.TEXT_ADDRESS_BY}</label></td>
                        <td class="td_3" colspan="2">
                            <div class="f_td f_td_country">
                                <input name="country" id="selectCountry" type="text" value="{$app->controller->view->filters->country}" placeholder="{$smarty.const.TEXT_TYPE_COUNTRY}" class="form-control">
                            </div>
                            {if $app->controller->view->showState == true}
                            <div class="f_td f_td2 f_td_state">
                                <input name="state" id="selectState" type="text" value="{$app->controller->view->filters->state}" placeholder="{$smarty.const.TEXT_TYPE_COUNTY}" class="form-control" {if $app->controller->view->filters->country == ''}disabled{/if}>
                            </div>
                            {/if}
                            <div class="f_td f_td2 f_td_city">
                                <input name="city" id="selectCity" type="text" value="{$app->controller->view->filters->city}" placeholder="{$smarty.const.TEXT_TYPE_CITY}" class="form-control" {if $app->controller->view->filters->state == ''}disabled{/if}>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td align="right">
                            <label>{$smarty.const.TEXT_COMPANY}</label>
                        </td>
                        <td>
                            <div class="f_td f_td_company">
                                <input name="company" id="selectCompany" type="text" value="{$app->controller->view->filters->company}" placeholder="{$smarty.const.TEXT_CHOOSE_COMPANY}" class="form-control">
                            </div>
                        </td>
                        <td class="td_small" align="right">
                            <label>{$smarty.const.TEXT_GUEST}</label>
                            <select name="guest" class="form-control small-in">
                                {foreach $app->controller->view->filters->guest as $Item}
                                    <option {$Item['selected']} value="{$Item['value']}">{$Item['name']}</option>
                                {/foreach}
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td align="right">
                            <label>{$smarty.const.TEXT_NEWSLETTER}</label>
                        </td>
                        <td>
                            <select name="newsletter" class="form-control">
                                {foreach $app->controller->view->filters->newsletter as $Item}
                                    <option {$Item['selected']} value="{$Item['value']}">{$Item['name']}</option>
                                {/foreach}
                            </select>
                        </td>
                        <td class="td_small" align="right">
                            <label>{$smarty.const.TEXT_STATUS}</label>
                            <select name="status" class="form-control small-in">
                                {foreach $app->controller->view->filters->status as $Item}
                                    <option {$Item['selected']} value="{$Item['value']}">{$Item['name']}</option>
                                {/foreach}
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top" align="right">
                            <label style="display: inline-block; margin-top: 7px;">{$smarty.const.TEXT_REGISTERED}</label>
                        </td>
                        <td class="td_radio">
                            <div class="f_row">
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
                            <div class="f_row">
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
                        </td>
                        <td valign="top" class="td_small" align="right">
                            <label>{$smarty.const.TEXT_TITLE}</label>
                            <select name="title" class="form-control small-in">
                                {foreach $app->controller->view->filters->title as $Item}
                                    <option {$Item['selected']} value="{$Item['value']}">{$Item['name']}</option>
                                {/foreach}
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" align="right">
                            <a href="javascript:void(0)" onclick="return resetFilter();" class="btn">{$smarty.const.TEXT_RESET}</a>&nbsp;&nbsp;&nbsp;<button type="submit" class="btn btn-primary">{$smarty.const.TEXT_SEARCH}</button>&nbsp;
                        </td>
                    </tr>
                </table>  
                <input type="hidden" name="row" id="row_id" value="{$app->controller->view->filters->row}" />
                </div>
            </form>
        
    </div>
</div>
<div class="order-wrap">
    <div class="row order-box-list">
        <div class="col-md-12">
            <div class="widget-content">
                <div class="btn-wr after btn-wr-top disable-btn">
                    <div>
                        <a href="javascript:void(0)" onclick="deleteSelectedOrders();" class="btn btn-del btn-no-margin">{$smarty.const.TEXT_DELETE_SELECTED}</a>
                    </div>
                    <div>
                    </div>
                </div>
                <table class="table table-striped table-selectable table-checkable table-hover table-responsive table-bordered datatable tab-cust tabl-res double-grid" checkable_list="" data_ajax="customers/customerlist">
                    <thead>
                        <tr>
                            {foreach $app->controller->view->customersTable as $tableItem}
                                <th{if $tableItem['not_important'] == 2} class="checkbox-column"{/if}{if $tableItem['not_important'] == 1} class="hidden-xs"{/if}>{$tableItem['title']}</th>
                                {/foreach}
                        </tr>
                    </thead>

                </table>
                <div class="btn-wr after disable-btn">
                    <div>
                        <a href="javascript:void(0)" onclick="deleteSelectedOrders();" class="btn btn-del btn-no-margin">{$smarty.const.TEXT_DELETE_SELECTED}</a>
                    </div>
                    <div>
                    </div>
                </div>
            </div>

        </div>
    </div>
				<!-- /Customers List -->
                                
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
function deleteSelectedOrders() {
    if (getTableSelectedCount() > 0) {
        var selected_ids = getTableSelectedIds();
        
        bootbox.dialog({
                message: "{$smarty.const.TEXT_DELETE_SELECTED} <span class=\"lowercase\">{$smarty.const.TEXT_CUSTOMERS}?</span>",
                title: "{$smarty.const.TEXT_DELETE_SELECTED} <span class=\"lowercase\">{$smarty.const.TEXT_CUSTOMERS}</span>",
                buttons: {
                        success: {
                                label: "Yes",
                                className: "btn-delete",
                                callback: function() {
                                    $.post("customers/customersdelete", { 'selected_ids' : selected_ids, 'delete_reviews' : '1' }, function(data, status){
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
                                    $.post("customers/customersdelete", { 'selected_ids' : selected_ids, 'delete_reviews' : '0' }, function(data, status){
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
function setFilterState() {
    orig = $('#filterForm').serialize();
    var url = window.location.origin + window.location.pathname + '?' + orig.replace(/[^&]+=\.?(?:&|$)/g, '')
    window.history.replaceState({ }, '', url);
}
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
    $('input[name="country"]').val('');
    $('input[name="state"]').val('');
    $('input[name="city"]').val('');
    $('input[name="group"]').val('');
    $('input[name="company"]').val('');
    $('select[name="title"]').val('');
    $('select[name="newsletter"]').val('');
    $("#row_id").val(0);
    resetStatement();
    return false;  
}
function applyFilter() {
    resetStatement();
    return false;    
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
function resetStatement() {
    setFilterState();
    $("#order_management").hide();
    switchOnCollapse('customers_list_collapse');
    var table = $('.table').DataTable();
    table.draw(false);
    return false;
}
function onClickEvent(obj, table) {
    var dtable = $(table).DataTable();
    var id = dtable.row('.selected').index();
    $("#row_id").val(id);
    setFilterState();
    //$("#order_management").hide();
    var customers_id = $(obj).find('input.cell_identify').val();
    $.post("customers/customeractions", { 'customers_id' : customers_id }, function(data, status){
        if (status == "success") {
            $('#customer_management_data .scroll_col').html(data);
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
function editCustomer(customers_id) {
    $("#order_management").hide();
    $.post("customers/customeredit", { 'customers_id' : customers_id }, function(data, status){
        if (status == "success") {
            $('#customer_management_data .scroll_col').html(data);
            $("#order_management").show();
            switchOffCollapse('customers_list_collapse');
        } else {
            alert("Request error.");
            //$("#order_management").hide();
        }
    },"html");
    return false;
}
function check_form() {
    //ajax save
    $("#order_management").hide();
    var customers_id = $( "input[name='customers_id']" ).val();
    $.post("customers/customersubmit", $('#customers_edit').serialize(), function(data, status){
        if (status == "success") {
            //$('#customer_management_data').html(data);
            //$("#order_management").show();
            switchOnCollapse('customers_list_collapse');
            var table = $('.table').DataTable();
            table.draw(false);
            setTimeout('$(".cell_identify[value=\''+customers_id+'\']").click();', 500);
            //$(".cell_identify[value='"+customers_id+"']").click();
        } else {
            alert("Request error.");
            //$("#order_management").hide();
        }
    },"html");
    //$('#customer_management_data').html('');
    return false;
}
function deleteCustomer() {
    $("#order_management").hide();
    $.post("customers/customerdelete", $('#customers_edit').serialize(), function(data, status){
        if (status == "success") {
            resetStatement()
        } else {
            alert("Request error.");
        }
    },"html");
    return false;
}
function confirmDeleteCustomer(customers_id) {
    $("#order_management").hide();
    $.post("customers/confirmcustomerdelete", { 'customers_id' : customers_id }, function(data, status){
        if (status == "success") {
            $('#customer_management_data .scroll_col').html(data);
            $("#order_management").show();
            switchOffCollapse('customers_list_collapse');
        } else {
            alert("Request error.");
            //$("#order_management").hide();
        }
    },"html");
    return false;
}

function check_passw_form(length){
  if (document.forms.passw_form.change_pass.value.length < length){
    alert("New password must have at least " + length + " characters.");
    return false;       
  } else {
    switchOffCollapse('customer_management_bar');
    $.post('customers/generatepassword', $('form[name=passw_form]').serialize(), function(data, status){
      console.log(data);
      if (status == "success") {
          var customers_id = $( "input[name='customers_id']" ).val();
          $.post("customers/customeractions", {
            'customers_id': data.customers_id,
          }, function (data, status) {
            $('#customer_management_data .scroll_col').html(data);
            $("#order_management").show();
            switchOnCollapse('customer_management_bar');
            $('.popup-box:last').trigger('popup.close');
            $('.popup-box-wrap:last').remove();
          }, "html");
      } else {
          alert("Request error.");
      }      
    }, "json");
    return false;
  }
}

$('.fields_style select').change(function(){
    $(this).focusout();
});
$(document).ready(function() {
    
    //===== Date Pickers  =====//
    $( ".datepicker" ).datepicker({
            changeMonth: true,
            changeYear: true,
            showOtherMonths:true,
            autoSize: false,
            dateFormat: '{$smarty.const.DATE_FORMAT_DATEPICKER}'
    });
    
  $(window).resize(function () {
    setTimeout(function () {
      var height_box = $('.order-box-list').height() + 2;
      $('#order_management .widget.box').css('min-height', height_box);
    }, 800);
  })
  $(window).resize();
  
  $('.f_row.act_row input[type="text"]').prop('disabled', false);
    $('.f_row.act_row select').prop('disabled', false);
    
    $('input[name="date"]').click(function() { 
        if($(this).is(':checked')){ 
            $(this).parents().siblings('div.f_row').removeClass('act_row');
            $(this).parents('.f_row').addClass('act_row');
            $('.f_row input[type="text"]').prop('disabled', true);
            $('.f_row select').prop('disabled', true);
            $('.f_row.act_row input[type="text"]').prop('disabled', false);
            $('.f_row.act_row select').prop('disabled', false);
        }
    });  
  
  $('th.checkbox-column .uniform').click(function() { 
        if($(this).is(':checked')){
            $('.order-box-list .btn-wr').removeClass('disable-btn');
        }else{
            $('.order-box-list .btn-wr').addClass('disable-btn');
        }
    });

    $('#selectCountry').autocomplete({
        source: "customers/countries",
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
            $('input[name="state"]').prop('disabled', true);
            if(ui.item.value != null){ 
                $('input[name="state"]').prop('disabled', false);
            }
            $('input[name="city"]').prop('disabled', true);
            if(ui.item.value != null){ 
                $('input[name="city"]').prop('disabled', false);
            }
        }
    }).focus(function () {
      $(this).autocomplete("search");
    });
    
    $('#selectState').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "customers/state",
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
        },
        select: function(event, ui) {
            /*$('input[name="city"]').prop('disabled', true);
            if(ui.item.value != null){ 
                $('input[name="city"]').prop('disabled', false);
            }*/
        }
    }).focus(function () {
      $(this).autocomplete("search");
    });
    
    $('#selectCity').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "customers/city",
                dataType: "json",
                data: {
                    term : request.term,
                    country : $("#selectCountry").val(),
                    state :  $("#selectState").val(),
                },
                success: function(data) {
                    response(data);
                }
            });
        },
        minLength: 0,
        autoFocus: true,
        delay: 0,
        appendTo: '.f_td_city',
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
    
{if $app->controller->view->filters->showGroup}
    $('#selectGroup').autocomplete({
        source: "customers/group",
        minLength: 0,
        autoFocus: true,
        delay: 0,
        appendTo: '.f_td_group',
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
{/if}
    
    
    $('#selectCompany').autocomplete({
        source: "customers/company",
        minLength: 0,
        autoFocus: true,
        delay: 0,
        appendTo: '.f_td_company',
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
</script>
				<!--===Actions ===-->
				<div class="row right_column" id="order_management" style="display: none;">
						<div class="widget box">
							<div class="widget-content fields_style" id="customer_management_data">
                <div class="scroll_col"></div>
							</div>
						</div>
        </div>
				<!--===Actions ===-->
</div>
				<!-- /Page Content -->