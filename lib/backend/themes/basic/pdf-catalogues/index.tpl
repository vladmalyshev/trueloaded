
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
              {if isset($messages) && $messages|count > 0}
               {foreach $messages as $message}
              <div class="alert fade in {$message['messageType']}">
                  <i data-dismiss="alert" class="icon-remove close"></i>
                  <span id="message_plce">{$message['message']}</span>
              </div>               
               {/foreach}
              {/if}
                                <table class="table table-striped table-bordered table-hover table-responsive table-checkable datatable" checkable_list="0" data_ajax="pdf-catalogues/list">
                                    <thead>
                                        <tr>
                                                {foreach $app->controller->view->pdf_catalogueTable as $tableItem}
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
var global = '{$pcID}';

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

    $("#pdf_catalogues_management").hide();
    switchOnCollapse('pdf_catalogues_list_collapse');
    var table = $('.table').DataTable();
    table.draw(false);
    $(window).scrollTop(0);
    return false;
}

var first = true;
function onClickEvent(obj, table) {
    $('#row_id').val(table.find(obj).index());
    $("#pdf_catalogues_management").hide();
    $('#pdf_catalogues_management_data .scroll_col').html('');
    var pdf_catalogues_id = $(obj).find('input.cell_identify').val();
    if (global > 0) pdf_catalogues_id = global;

    $.post("pdf-catalogues/statusactions", { 'pdf_catalogues_id' : pdf_catalogues_id }, function(data, status) {
        if (status == "success") {
            $('#pdf_catalogues_management_data .scroll_col').html(data);
            $("#pdf_catalogues_management").show();
            $('.js-open-tree-popup').popUp();
            $('.js-new-pdf-catalogue-popup').popUp({ 'box_class':'popupNewPdfCatalogue' });
        } else {
            alert("Request error.");
        }
    },"html");

    $('.table tr').removeClass('selected');
    $('.table').find('input.cell_identify[value=' + pdf_catalogues_id + ']').parents('tr').addClass('selected');
    global = '';
    url = window.location.href;
    if (url.indexOf('pcID=') > 0) {
      url = url.replace(/pcID=\d+/g, 'pcID=' + pdf_catalogues_id);
    } else {
      url += '?pcID=' + pdf_catalogues_id;
    }
    if (first) {
      first = false;
    } else {
      window.history.replaceState({}, '', url);
    }
}

function onUnclickEvent(obj, table) {
    $("#pdf_catalogues_management").hide();
    var event_id = $(obj).find('input.cell_identify').val();
    var type_code = $(obj).find('input.cell_type').val();
    $(table).DataTable().draw(false);
}

function pdf_catalogueEdit(id) {
    $("#pdf_catalogues_management").hide();
    $.get("pdf-catalogues/edit", { 'pdf_catalogues_id' : id }, function(data, status) {
        if (status == "success") {
            $('#pdf_catalogues_management_data .scroll_col').html(data);
            $("#pdf_catalogues_management").show();
            switchOffCollapse('pdf_catalogues_list_collapse');
        } else {
            alert("Request error.");
        }
    },"html");
    return false;
}

function pdf_catalogueSave(id) {
    $.post("pdf-catalogues/save?pdf_catalogues_id="+id, $('form[name=pdf_catalogue]').serialize(), function(data, status) {
        if (status == "success") {
            //$('#pdf_catalogues_management_data').html(data);
            //$("#pdf_catalogues_management").show();
            $('.alert #message_plce').html('');
            $('.alert').show().removeClass('alert-error alert-success alert-warning').addClass(data['messageType']).find('#message_plce').append(data['message']);
            resetStatement(id);
            switchOffCollapse('pdf_catalogues_list_collapse');
        } else {
            alert("Request error.");
        }
    },"json");
    return false;    
}

function pdf_catalogueDeleteConfirm(id) {
    $.post("{$app->urlManager->createUrl('pdf-catalogues/confirmdelete')}", { 'pdf_catalogues_id': id }, function (data, status) {
        if (status == "success") {
            $('#pdf_catalogues_management_data .scroll_col').html(data);
        } else {
            alert("Request error.");
        }
    }, "html");
    return false;
}

function pdf_catalogueDelete() {
    if (confirm('Are you sure?')) {
        $.post("{$app->urlManager->createUrl('pdf-catalogues/delete')}", $('#item_delete').serialize(), function (data, status) {
            if (status == "success") {
                if (data == 'reset') {
                    resetStatement();
                } else {
                    $('#pdf_catalogues_management_data .scroll_col').html(data);
                    $("#pdf_catalogues_management").show();
                }
                switchOnCollapse('pdf_catalogues_list_collapse');
            } else {
                alert("Request error.");
            }
        }, "html");
    }
    return false;
}
</script>
                                <!--===Actions ===-->
                <div class="row right_column" id="pdf_catalogues_management">
                        <div class="widget box">
                            <div class="widget-content fields_style" id="pdf_catalogues_management_data">
                               <div class="scroll_col"></div>
                            </div>
                        </div>
                    </div>
                <!--===Actions ===-->
                <!-- /Page Content -->
</div>

<link href="{$app->request->baseUrl}/plugins/fancytree/skin-bootstrap/ui.fancytree.min.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{$app->request->baseUrl}/plugins/fancytree/jquery.fancytree-all.min.js"></script>
