<!--=== Page Header ===-->
<div class="page-header">
    <div class="page-title">
        <h3>{$app->controller->view->headingTitle}</h3>
    </div>
</div>
<!-- /Page Header -->


<!--===featured list===-->
<div class="order-wrap">
<input type="hidden" id="row_id">
<div class="row order-box-list" id="featured_list">
    <div class="col-md-12">

        <div class="ord_status_filter_row">
            <form id="filterForm" name="filterForm" onsubmit="return applyFilter();">
                {$featuredTypesDown}
            </form>
        </div>

            <div class="widget-content" id="featured_list_data">
                <table class="table table-striped table-bordered table-hover table-responsive table-checkable datatable table-featured"
                       checkable_list="0" data_ajax="featured/list">
                    <thead>
                    <tr>
                        {foreach $app->controller->view->featuredTable as $tableItem}
                            <th{if $tableItem['not_important'] == 1} class="hidden-xs"{/if}>{$tableItem['title']}</th>
                        {/foreach}
                    </tr>
                    </thead>
                </table>
            </div>
    </div>
</div>
<!--===/featured list===-->

<script type="text/javascript">

    function preEditItem( item_id ) {
        $.post("featured/itempreedit", {
            'item_id': item_id,
            'featured_type_id': $('.featured-type-id').val(),
        }, function (data, status) {
            if (status == "success") {
                $('#featured_management_data .scroll_col').html(data);
                $("#featured_management").show();
                switchOnCollapse('featured_management_collapse');
            } else {
                alert("Request error.");
            }
        }, "html");

        ///$("html, body").animate({ scrollTop: $(document).height() }, "slow");

        return false;
    }

    function editItem(item_id) {

        $.post("featured/itemedit", {
            'item_id': item_id,
            'featured_type_id': $('.featured-type-id').val(),
        }, function (data, status) {
            if (status == "success") {
                $('#featured_management_data .scroll_col').html(data);
                $("#featured_management").show();
                $(".check_on_off").bootstrapSwitch(
                  {
                onText: "{$smarty.const.SW_ON}",
                offText: "{$smarty.const.SW_OFF}",
                    handleWidth: '20px',
                    labelWidth: '24px'
                  }
                );
            } else {
                alert("Request error.");
            }
        }, "html");
        return false;
    }

    function saveItem() {
        $.post("featured/submit", $('#save_item_form').serialize(), function (data, status) {
            if (status == "success") {
                $('#featured_management_data .scroll_col').html(data);
                $("#featured_management").show();

                $('.table').DataTable().search('').draw(false);

            } else {
                alert("Request error.");
            }
        }, "html");

        return false;
    }

    function deleteItemConfirm( item_id) {
        $.post("featured/confirmitemdelete", {  'item_id': item_id }, function (data, status) {
            if (status == "success") {
                $('#featured_management_data .scroll_col').html(data);
                $("#featured_management").show();
                switchOnCollapse('featured_management_collapse');
            } else {
                alert("Request error.");
            }
        }, "html");
        return false;
    }

    function deleteItem() {
        $.post("featured/itemdelete", $('#item_delete').serialize(), function (data, status) {
            if (status == "success") {
                resetStatement();
                $('#featured_management_data .scroll_col').html("");
                switchOffCollapse('featured_management_collapse');
            } else {
                alert("Request error.");
            }
        }, "html");

        return false;
    }

    function switchOffCollapse(id) {
        if ($("#" + id).children('i').hasClass('icon-angle-down')) {
            $("#" + id).click();
        }
    }

    function switchOnCollapse(id) {
        if ($("#" + id).children('i').hasClass('icon-angle-up')) {
            $("#" + id).click();
        }
    }

    function resetStatement() {
        $("#featured_management").hide();

        switchOnCollapse('featured_list_box_collapse');
        switchOffCollapse('featured_management_collapse');

        $('featured_management_data .scroll_col').html('');
        $('#featured_management').hide();

        var table = $('.table').DataTable();
        table.draw(false);

        //$(window).scrollTop(0);

        return false;
    }

    function switchStatement(id, status) {
      $.post("featured/switch-status", { 'id' : id, 'status' : status }, function(data, status){
        if (status == "success") {
          resetStatement();
        } else {
          alert("Request error.");
        }
      },"html");
    }    
    
    function onClickEvent(obj, table) {
        $('#row_id').val(table.find(obj).index());
        var event_id = $(obj).find('input.cell_identify').val();
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
        preEditItem(  event_id );
    }

    function onUnclickEvent(obj, table) {

        var event_id = $(obj).find('input.cell_identify').val();
    }

</script>

<!--===  featured management ===-->
<div class="row right_column" id="featured_management">
        <div class="widget box">
            <div class="widget-content fields_style" id="featured_management_data">
                <div class="scroll_col"></div>
            </div>
        </div> 
</div>
</div>
<!--=== featured management ===-->