{use class="common\helpers\Html"}
<div class="gridBg">
    <div class="btn-bar btn-bar-top after">
        <div class="btn-left"><a href="javascript:void(0)" onclick="return resetStatement();" class="btn btn-back">{$smarty.const.IMAGE_BACK}</a></div>
        <div class="btn-right"><a href="javascript:void(0)" onclick="return deleteOrder({$orders_id});" class="btn btn-delete">{$smarty.const.IMAGE_DELETE}</a>
        </div>
    </div>
<!--=== Page Header ===-->
<div class="page-header">
    <div class="page-title">
        <h3>{$app->controller->view->headingTitle}</h3>
    </div>
</div>
<!-- /Page Header -->
<!--=== Page Content ===-->
<div class="box-or-prod-wrap">
    <div class="widget box box-no-shadow">
        <div class="widget-header widget-header-address">
            <h4>{$smarty.const.T_ADD_DET}</h4>
            <div class="toolbar no-padding">
                <div class="btn-group">
                    <span id="orders_list_collapse" class="btn btn-xs widget-collapse"><i class="icon-angle-down"></i></span>
                </div>
            </div>
        </div>
        <div id="order_management_data" class="widget-content fields_style">
            <div class="pr-add-det-box pr-add-det-box02 after">
                <div class="pra-sub-box after">
                    <span>{$smarty.const.TEXT_SUPPLIER}</span>
                    <a target="_blank" href="{$app->urlManager->createUrl(['suppliers/edit', 'suppliers_id' => $order->info['suppliers_id']])}">{$order->info['suppliers_name']}</a><br>
                    {$order->info['send_email']}
                </div>
                <div class="pra-sub-box after">
                    <span>{$smarty.const.TEXT_WAREHOUSE}</span>
                    <a target="_blank" href="{$app->urlManager->createUrl(['warehouses/edit', 'id' => $order->info['warehouse_id']])}">{$order->info['delivery_name']}</a><br>
                    {$delivery_address}
                </div>
                <div class="after">
                  <div class="col-md-2">
                    <span>{$smarty.const.EMAIL_TEXT_ORDER_NUMBER}</span> {$order->getOrderNumber()}
                   </div>
                  <div class="col-md-2">
                    <span>{$smarty.const.TEXT_ESTIMATED_DELIVERY}</span> {$delivery_date}
                   </div>
                 </div>
            </div>
        </div>
    </div>
    <div class="widget box box-no-shadow">
        <div class="widget-header widget-header-prod">
            <h4>{$smarty.const.TEXT_PROD_DET}</h4>
            <div class="toolbar no-padding">
                <div class="btn-group">
                    <span id="orders_list_collapse" class="btn btn-xs widget-collapse"><i class="icon-angle-down"></i></span>
                </div>
            </div>
        </div>
        <div class="widget-content widget-content-prod">
            <table border="0" class="table table-process" width="100%" cellspacing="0" cellpadding="2">
                <thead>
                    <tr class="dataTableHeadingRow">
                        <th class="dataTableHeadingContent" colspan="3">{$smarty.const.TABLE_HEADING_PRODUCTS}</th>
                        <th class="dataTableHeadingContent">{$smarty.const.TABLE_HEADING_PRODUCTS_MODEL}</th>
                        <th class="dataTableHeadingContent">{$smarty.const.TABLE_HEADING_STATUS}</th>
                        <th class="dataTableHeadingContent">{$smarty.const.TEXT_STATUS_LONG_OPS_CANCELLED}</th>
                        <th class="dataTableHeadingContent">{$smarty.const.TEXT_STATUS_LONG_OPS_RECEIVED}</th>
                        <th class="dataTableHeadingContent" align="right">{$smarty.const.TABLE_HEADING_TAX}</th>
                        <th class="dataTableHeadingContent" align="right">{$smarty.const.TABLE_HEADING_PRICE_EXCLUDING_TAX}</th>
                        <th class="dataTableHeadingContent" align="right">{$smarty.const.TABLE_HEADING_PRICE_INCLUDING_TAX}</th>
                        <th class="dataTableHeadingContent" align="right">{$smarty.const.TABLE_HEADING_TOTAL_EXCLUDING_TAX}</th>
                        <th class="dataTableHeadingContent" align="right">{$smarty.const.TABLE_HEADING_TOTAL_INCLUDING_TAX}</th>
                    </tr>
                </thead>

                {foreach $products as $product}
                    <tr class="dataTableRow">
                        {foreach $product as $cell}
                            {$cell}
                        {/foreach}
                    </tr>
                {/foreach}
            </table>
            <div class="order-sub-totals">
                <table>
                {foreach $order->totals as $total}
                    <tr class="{$total['class']}">
                        <td>{$total['title']}</td>
                        <td>{$total['text']}</td>
                    </tr>
                {/foreach}
                </table>
            </div>
                <div>
                 {$smarty.const.TEXT_WEIGHT} {round($order->info['shipping_weight'],2)}
                </div>
            <div style="clear: both"></div>
        </div>
    </div>
    <div class="widget box box-no-shadow">
        <div class="widget-header widget-header-order-status">
            <h4>{$smarty.const.TEXT_ORDER_STATUS}</h4>
            <div class="toolbar no-padding">
                <div class="btn-group">
                    <span id="orders_list_collapse" class="btn btn-xs widget-collapse"><i class="icon-angle-down"></i></span>
                </div>
            </div>
        </div>
        <div class="widget-content">
            <table class="table table-st" border="0" cellspacing="0" cellpadding="0" width="100%">
                <thead>
                    <tr>
                     <th class="smallText" align="left">{$smarty.const.TABLE_HEADING_DATE_ADDED}</th>
                     <th class="smallText" align="left">{$smarty.const.TABLE_HEADING_CUSTOMER_NOTIFIED}</th>
                     <th class="smallText" align="left">{$smarty.const.TABLE_HEADING_STATUS}</th>
                     <th class="smallText" align="left">{$smarty.const.TABLE_HEADING_COMMENTS}</th>
                     <th class="smallText" align="left">{$smarty.const.TABLE_HEADING_PROCESSED_BY}</th>
                   </tr>
                </thead>
                {foreach $histories as $history}
                    <tr>
                        {foreach $history as $cell}
                            {$cell}
                        {/foreach}
                    </tr>
                {/foreach}
            </table>
                <form name="status" action="#" method="post" id="status_edit" onsubmit="return check_form();">
                    <div class="widget box box-wrapp-blue filter-wrapp">
                        <div class="widget-header upd-sc-title">
                            <h4>{$smarty.const.TABLE_HEADING_COMMENTS_STATUS}</h4>
                        </div>
                        <div class="widget-content usc-box usc-box2">
                            <div class="f_tab">
                                <div class="f_row">
                                    <div class="f_td">
                                        <label>{$smarty.const.ENTRY_STATUS}</label>
                                    </div>
                                    <div class="f_td">
                                        {Html::dropDownList('status', (int)$order->info['orders_status'] , \common\helpers\PurchaseOrder::getStatusList(false), ['class'=>'form-control', 'id' => 'order-status'])}
                                    </div>
                                </div>
                                {*if ( class_exists('\common\helpers\CommentTemplate') )}
                                {\common\helpers\CommentTemplate::renderFor('purchase', $order)}
                                {/if*}
                                <div class="f_row">
                                    <div class="f_td">
                                        <label>{$smarty.const.TABLE_HEADING_COMMENTS}:</label>
                                    </div>
                                    <div class="f_td">
                                        {tep_draw_textarea_field('comments', 'soft', '60', '5', '', 'class="form-control"')}
                                    </div>
                                </div>
                                <div class="f_row">
                                    <div class="f_td"></div>
                                    <div class="f_td">
                                        {tep_draw_checkbox_field('notify', 1, true)}<b>{$smarty.const.ENTRY_NOTIFY_CUSTOMER}</b>

                                        <input type="submit" style="float: right; margin-right: -9px;" class="btn btn-confirm" value="{$smarty.const.IMAGE_UPDATE}" >
                                        {Html::input('hidden', 'orders_id', $orders_id)}

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
        </div>
    </div>
                                        
<div class="btn-bar" style="padding: 0; text-align: center;">
    <div class="btn-left">
        <a href="javascript:void(0)" onclick="return resetStatement();" class="btn btn-back">{$smarty.const.IMAGE_BACK}</a>
    </div>
    <div class="btn-right">
        <a href="{$app->urlManager->createUrl(['purchase-orders/print-order', 'orders_id' => $orders_id])}" target="_blank" class="btn btn-mar-right btn-primary">{$smarty.const.TEXT_PRINT_ORDER}</a>
    </div>
</div>

</div>
<script type="text/javascript">
function deleteOrder(orders_id) {
    var r = confirm("{$smarty.const.TEXT_INFO_DELETE_INTRO}");
    if (r == true) {
        $("#order_management").hide();
        $.post("{$app->urlManager->createUrl('purchase-orders/orderdelete')}", {
                    'orders_id': orders_id,
                }, function(data, status){
            if (status == "success") {
                $("#order_management_data").html('<div class="alert alert-success fade in"><i data-dismiss="alert" class="icon-remove close"></i>Order deleted. Please wait before continuing.</div>');
                window.location.href= "{$app->urlManager->createUrl('purchase-orders/')}";
            } else {
                alert("Request error.");
            }
        },"html");
    }
    return false;
}
function check_form() {
    $.post("{$app->urlManager->createUrl('purchase-orders/submit-purchase-orders')}", $('#status_edit').serialize(), function(data, status){
        if (status == "success") {
            //$("#order_management_data").html(data);
            window.location.reload();
        } else {
            alert("Request error.");
        }
    },"html");
    return false;
}
function resetStatement() {
    window.location.href = '{$app->urlManager->createUrl('purchase-orders')}';
    return false;
}
function closePopup() {
    $('.popup-box').trigger('popup.close');
    $('.popup-box-wrap').remove();
    return false;
}
$(document).ready(function() { 
    $('a.popup').popUp();
    $("a.js_gv_state_popup").popUp({
        box: "<div class='popup-box-wrap'><div class='around-pop-up'></div><div class='popup-box'><div class='pop-up-close'></div><div class='popup-heading pup-head'>{$smarty.const.POPUP_TITLE_GV_STATE_SWITCH}</div><div class='pop-up-content'><div class='preloader'></div></div></div></div>"
    });
    $('.right-link').off().popUp({ 'box_class':'popupCredithistory' });
});
</script>
<!-- /Page Content -->
</div>