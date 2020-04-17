
<!--=== Page Header ===-->
<div class="page-header">
    <div class="page-title">
        <h3>{$app->controller->view->headingTitle}</h3>
    </div>
</div>
<!-- /Page Header -->
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
              {if {$messages|@count} > 0}
               {foreach $messages as $message}
              <div class="alert fade in {$message['messageType']}">
                  <i data-dismiss="alert" class="icon-remove close"></i>
                  <span id="message_plce">{$message['message']}</span>
              </div>               
               {/foreach}
              {/if}
                                <table class="table table-striped table-bordered table-hover table-responsive table-checkable datatable" checkable_list="0" data_ajax="pc_templates/list">
                                    <thead>
                                        <tr>
                                                {foreach $app->controller->view->pc_templateTable as $tableItem}
                                                    <th{if $tableItem['not_important'] == 1} class="hidden-xs"{/if}>{$tableItem['title']}</th>
                                                {/foreach}
                                        </tr>
                                    </thead>
                                    
                                </table>            


                </form>
                            </div>
             
                    </div>
                </div>
<script type="text/javascript">
var global = '{$tID}';

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

function resetStatement(item_id) {
    if (item_id > 0) global = item_id;

    $("#pc_templates_management").hide();
    switchOnCollapse('pc_templates_list_collapse');
    var table = $('.table').DataTable();
    table.draw(false);
    $(window).scrollTop(0);
    return false;
}

var first = true;
function onClickEvent(obj, table) {
    $('#row_id').val(table.find(obj).index());
    $("#pc_templates_management").hide();
    $('#pc_templates_management_data .scroll_col').html('');
    var pctemplates_id = $(obj).find('input.cell_identify').val();
    if (global > 0) pctemplates_id = global;

    $.post("pc_templates/statusactions", { 'pctemplates_id' : pctemplates_id }, function(data, status) {
        if (status == "success") {
            $('#pc_templates_management_data .scroll_col').html(data);
            $("#pc_templates_management").show();
        } else {
            alert("Request error.");
        }
    },"html");

    $('.table tr').removeClass('selected');
    $('.table').find('input.cell_identify[value=' + pctemplates_id + ']').parents('tr').addClass('selected');
    global = '';
    url = window.location.href;
    if (url.indexOf('tID=') > 0) {
      url = url.replace(/tID=\d+/g, 'tID=' + pctemplates_id);
    } else {
      url += '?tID=' + pctemplates_id;
    }
    if (first) {
      first = false;
    } else {
      window.history.replaceState({}, '', url);
    }
}

function onUnclickEvent(obj, table) {
    $("#pc_templates_management").hide();
    var event_id = $(obj).find('input.cell_identify').val();
    var type_code = $(obj).find('input.cell_type').val();
    $(table).DataTable().draw(false);
}

function pc_templateEdit(id) {
    $("#pc_templates_management").hide();
    $.get("pc_templates/edit", { 'pctemplates_id' : id }, function(data, status) {
        if (status == "success") {
            $('#pc_templates_management_data .scroll_col').html(data);
            $("#pc_templates_management").show();
            switchOffCollapse('pc_templates_list_collapse');
        } else {
            alert("Request error.");
        }
    },"html");
    return false;
}

function pc_templateSave(id) {
    $.post("pc_templates/save?pctemplates_id="+id, $('form[name=pc_template]').serialize(), function(data, status) {
        if (status == "success") {
            //$('#pc_templates_management_data').html(data);
            //$("#pc_templates_management").show();
            $('.alert #message_plce').html('');
            $('.alert').show().removeClass('alert-error alert-success alert-warning').addClass(data['messageType']).find('#message_plce').append(data['message']);
            resetStatement(id);
            switchOffCollapse('pc_templates_list_collapse');
        } else {
            alert("Request error.");
        }
    },"json");
    return false;    
}

function pc_templateDeleteConfirm(id) {
    $.post("{$app->urlManager->createUrl('pc_templates/confirmdelete')}", { 'pctemplates_id': id }, function (data, status) {
        if (status == "success") {
            $('#pc_templates_management_data .scroll_col').html(data);
        } else {
            alert("Request error.");
        }
    }, "html");
    return false;
}

function pc_templateDelete() {
    if (confirm('Are you sure?')) {
        $.post("{$app->urlManager->createUrl('pc_templates/delete')}", $('#item_delete').serialize(), function (data, status) {
            if (status == "success") {
                if (data == 'reset') {
                    resetStatement();
                } else {
                    $('#pc_templates_management_data .scroll_col').html(data);
                    $("#pc_templates_management").show();
                }
                switchOnCollapse('pc_templates_list_collapse');
            } else {
                alert("Request error.");
            }
        }, "html");
    }
    return false;
}
</script>
                                <!--===Actions ===-->
                <div class="row right_column" id="pc_templates_management">
                        <div class="widget box">
                            <div class="widget-content fields_style" id="pc_templates_management_data">
                               <div class="scroll_col"></div>
                            </div>
                        </div>
                    </div>
                <!--===Actions ===-->
                <!-- /Page Content -->
</div>
