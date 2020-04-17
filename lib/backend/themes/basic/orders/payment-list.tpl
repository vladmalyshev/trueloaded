{use class="\yii\helpers\Html"}
<div class="popup-heading">{$smarty.const.POPUP_HEADING_ORDER_PAYMENT}</div>
<div class="creditHistoryPopup">
    <table class="table table-striped table-bordered table-hover table-responsive table-ordering order-payment-datatable double-grid">
        <thead>
            <tr>
                <th>&nbsp;</th>
                <th data-orderable="false">{$smarty.const.TABLE_HEADING_PAYMENT_METHOD}</th>
                <th data-orderable="false">{$smarty.const.TABLE_HEADING_PAYMENT_STATUS}</th>
                <th data-orderable="false">{$smarty.const.TABLE_HEADING_PAYMENT_AMOUNT}</th>
                <th data-orderable="false">{$smarty.const.TABLE_HEADING_PAYMENT_TRANSACTION_ID}</th>
                <th data-orderable="false">{$smarty.const.TABLE_HEADING_PAYMENT_TRANSACTION_STATUS}</th>
                <th data-orderable="false">{$smarty.const.TABLE_HEADING_PAYMENT_TRANSACTION_COMMENTARY}</th>
                <th data-orderable="false">{$smarty.const.TABLE_HEADING_PAYMENT_TRANSACTION_DATE}</th>
                <th data-orderable="false">{$smarty.const.TABLE_HEADING_PROCESSED_BY}</th>
                <th data-orderable="false">{$smarty.const.TABLE_HEADING_DATE_ADDED}</th>
                <th data-orderable="false">{$smarty.const.TABLE_HEADING_UPDATE_BY}</th>
                <th data-orderable="false">{$smarty.const.TABLE_HEADING_UPDATE_DATE}</th>
                <th data-orderable="false">{$smarty.const.TABLE_HEADING_ACTION}</th>
            </tr>
        </thead>
        <tbody>
            {foreach $paymentArray as $paymentRecord}
                <tr>
                    <td>{$paymentRecord['orders_payment_id']}</td>
                    <td>{$paymentRecord['orders_payment_module_name']}</td>
                    <td>{$paymentRecord['orders_payment_status']}</td>
                    <td><span style="color: {$paymentRecord['orders_payment_amount_colour']};">{$paymentRecord['orders_payment_amount']}</span></td>
                    <td>{$paymentRecord['orders_payment_transaction_id']}</td>
                    <td>{$paymentRecord['orders_payment_transaction_status']}</td>
                    <td>{$paymentRecord['orders_payment_transaction_commentary']}</td>
                    <td>{$paymentRecord['orders_payment_transaction_date']}</td>
                    <td>{$paymentRecord['orders_payment_admin_create']}</td>
                    <td>{$paymentRecord['orders_payment_date_create']}</td>
                    <td>{$paymentRecord['orders_payment_admin_update']}</td>
                    <td>{$paymentRecord['orders_payment_date_update']}</td>
                    <td>
                        <a href="{Yii::$app->urlManager->createUrl(['orders/payment-edit', 'opyID' => $paymentRecord['orders_payment_id']])}" class="popup-opye" data-class="popupCredithistory">{$smarty.const.IMAGE_EDIT}</a>
                        {if $paymentRecord['orders_payment_is_refund'] > 0}
                        <a href="{Yii::$app->urlManager->createUrl(['orders/payment-refund', 'opyID' => $paymentRecord['orders_payment_id']])}" class="popup-opye" data-class="popupCredithistory">{$smarty.const.IMAGE_REFUND}</a>
                        {/if}
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>
</div>
<div class="mail-sending noti-btn">
    <div><span class="btn btn-cancel">{$smarty.const.IMAGE_CANCEL}</span></div>
    <div>
    {if $onBehalfUrl}
    <div><a id="popup-pay-now" class="btn" href="#">{$smarty.const.TEXT_ORDER_PAY}</a></div>
    {/if}<a id="popup-opye-add" href="{Yii::$app->urlManager->createUrl(['orders/payment-edit', 'oID' => $oID])}" class="popup btn" data-class="popupCredithistory">{$smarty.const.IMAGE_ADD}</a></div>
</div>
<script>
    var table;
    (function($) {
        table = $('.order-payment-datatable').dataTable({
            'pageLength': 5,
            'order': [[0, 'desc']],
            'columnDefs': [{ 'visible': false, 'targets': 0 }]
        });
        var oSettings = table.fnSettings();
        oSettings._iDisplayStart = 0;
        table.fnDraw();
        $('#popup-pay-now').on('click', function () {
          event.preventDefault();
          $('.mail-sending.noti-btn').hide();

          var paymentPopup = $('.popupEditCat'); //popup-box
          
          $('.pop-up-close', paymentPopup).hide();
          var w = Math.max(300, Math.round(screen.width/2));
          var h = Math.max(300, Math.round(screen.height*0.65));

          paymentPopup.css("width", w +'px').css("height", h +'px');
          var d = ($(window).height() - $('.popup-box').height()) / 2;
          if (d < 0) d = 0;
          $('.popup-box-wrap').css('top', $(window).scrollTop() + d);
          $(".pop-up-content:last").html('<iframe src="{$onBehalfUrl}" frameborder="0" style="width:' + (w-15) +'px;height:' + (h-15) +'px"></iframe><div class="noti-btn"><div />{$smarty.const.TEXT_SCROLL_TO_CONFIRM}<div class="btn-right"><button class="btn btn-confirm" id="paymentCloseBtn">{$smarty.const.IMAGE_CLOSE}</button></div></div>');
          $("#paymentCloseBtn").on('click', function() {
            $.ajax({
                url: "{tep_catalog_href_link('account/logoff')}",
                complete: function(data, status, xhr) {
                    window.location.reload();
                }
            });
            
          });

          return false;
        });
    })(jQuery);

    $('.popup-opye').popUp({ 'one_popup': false });
    $('#popup-opye-add').popUp({ 'one_popup': false });
</script>