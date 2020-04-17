<!--=== Page Header ===-->
<div class="page-header">
    <div class="page-title">
        <h3>{$app->controller->view->headingTitle}</h3>
    </div>
</div>
<!-- /Page Header -->
<div class="order-wrap">
    <div class="row">
        <div class="col-md-12">
            <div class="widget-content">
                <div class="alert fade in" style="display:none;">
                    <i data-dismiss="alert" class="icon-remove close"></i>
                    <span id="message_plce"></span>
                </div>
                <table class="table table-striped table-selectable table-checkable table-hover table-responsive table-bordered datatable tab-cust tabl-res double-grid" checkable_list="" data_ajax="{Yii::$app->urlManager->createUrl(['freeze-stock/product-listing'])}">
                    <thead>
                        <tr>
                            {foreach $app->controller->view->ViewTable as $tableItem}
                                <th{if $tableItem['not_important'] == 2} class="checkbox-column"{/if}{if $tableItem['not_important'] == 1} class="hidden-xs"{/if}>{$tableItem['title']}</th>
                                {/foreach}
                        </tr>
                    </thead>

                </table>

                </form>
            </div>

        </div>
    </div>
</div>
<script type="text/javascript">
function resetStatement() {
    var table = $('.table').DataTable();
    table.draw(false);
    $(window).scrollTop(0);
    return false;
}
function onDrawCallbackEvent() {
    $('.right-link-upd').popUp({
        'box_class': 'popupCredithistory',
        close: function() {
            $('.pop-up-close').click(function() {
                $('.popup-box:last').trigger('popup.close');
                $('.popup-box-wrap:last').remove();
                var table = $('.table').DataTable();
                table.draw(false);
                return false;
            });
            $('.popup-box').on('click', '.btn-cancel', function() {
                $('.popup-box:last').trigger('popup.close');
                $('.popup-box-wrap:last').remove();
                var table = $('.table').DataTable();
                table.draw(false);
                return false;
            });
        }
    });
    $('.right-link').popUp({ 'box_class': 'popupCredithistory'});
}
</script>