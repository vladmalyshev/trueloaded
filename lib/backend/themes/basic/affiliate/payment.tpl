<!--=== Page Header ===-->
<div class="page-header">
    <div class="page-title">
        <h3>{$app->controller->view->headingTitle}</h3>
    </div>
</div>
<!-- /Page Header -->
<form id="filterForm" name="filterForm" onsubmit="return applyFilter();">
    <input type="hidden" name="row" id="row_id" value="{$app->controller->view->row_id}" />
</form>

<div class="order-wrap">
<div class="row order-box-list">
    <div class="col-md-12">
            <div class="widget-content" id="reviews_list_data">
                <table class="table table-striped table-bordered table-hover table-responsive table-checkable datatable table-coupon_admin"
                       checkable_list="3" data_ajax="affiliate/payment-list">
                    <thead>
                    <tr>
                        {foreach $app->controller->view->catalogTable as $tableItem}
                            <th{if $tableItem['not_important'] == 1} class="hidden-xs"{/if}>{$tableItem['title']}</th>
                        {/foreach}
                    </tr>
                    </thead>
                </table>
            </div>
    </div>
</div>

<script type="text/javascript">
    function applyFilter() {
        $("#row_id").val(0);
        resetStatement();
        return false;    
    }
    
    function setFilterState() {
        orig = $('#filterForm').serialize();
        var url = window.location.origin + window.location.pathname + '?' + orig.replace(/[^&]+=\.?(?:&|$)/g, '')
        window.history.replaceState({ }, '', url);
    }

    function preEditItem( item_id ) {
        $.post("{$app->urlManager->createUrl('affiliate/payment-actions')}", {
            'item_id': item_id
        }, function (data, status) {
            if (status == "success") {
                $('#reviews_management_data .scroll_col').html(data);
                $("#reviews_management").show();
            } else {
                alert("Request error.");
            }
        }, "html");
        return false;
    }
    
    function itemEdit( item_id ) {
        return false;
    }
    
    function deleteItemConfirm( item_id) {
        $.post("{Yii::$app->urlManager->createUrl('affiliate/confirm-payment-delete')}", {  'item_id': item_id }, function (data, status) {
            if (status == "success") {
                $('#reviews_management_data .scroll_col').html(data);
            } else {
                alert("Request error.");
            }
        }, "html");
        return false;
    }

    function deleteItem() {
        $.post("{Yii::$app->urlManager->createUrl('affiliate/payment-delete')}", $('#item_delete').serialize(), function (data, status) {
            if (status == "success") {
                $('#reviews_management_data .scroll_col').html("");
                resetStatement();
            } else {
                alert("Request error.");
            }
        }, "html");

        return false;
    }
    
    function resetStatement() {
        $("#reviews_management").hide();

        $('reviews_management_data').html('');
        $('#reviews_management').hide();

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
        var event_id = $(obj).find('input.cell_identify').val();

        preEditItem(  event_id );
    }

    function onUnclickEvent(obj, table) {

        var event_id = $(obj).find('input.cell_identify').val();
    }

</script>

<!--===  management ===-->
<div class="row right_column" id="reviews_management">
        <div class="widget box">
            <div class="widget-content fields_style" id="reviews_management_data">
                <div class="scroll_col"></div>
            </div>
        </div>
</div>
</div>
<!--=== management ===-->

