<!--=== Page Header ===-->
<div class="page-header">
    <div class="page-title">
        <h3>{$app->controller->view->headingTitle}</h3>
    </div>
</div>
<!-- /Page Header -->
<style>.sorting_asc{ display:none!important; } .date-range input{ width:25%!important; } </style>
<!--===modules list===-->
<div class="">
    <div class="widget box box-wrapp-blue filter-wrapp">
        <div class="widget-header filter-title">
            <h4>{$smarty.const.TEXT_FILTER}</h4>
            <div class="toolbar no-padding">
                <div class="btn-group">
                    <span class="btn btn-xs widget-collapse"><i class="icon-angle-down"></i></span>
                </div>
            </div>
        </div>
        <div class="widget-content after recover_filter">
            <div class="f_row f_row_pl_cus f_row_pl filter_show column-block">  
            </div>          

            <div class="filter_categories column-block filter-box filter-box-cus filters-text dis_module">

                <div class="filter_block after">
                    <div class="filter_left">
                        <div class="filter_row row_with_label">
                            <label>{$smarty.const.TEXT_SEARCH_BY}</label>
                            <select class="form-control"></select>
                        </div>
                        <div class="filter_row row_with_label">
                            <label>{$smarty.const.TEXT_STATUS}</label>
                            <select class="form-control"></select>
                        </div>
                        <div class="filter_row date-range">
                            <label>{$smarty.const.DAYS_FIELD_PREFIX}</label>                  
                            <span><input class="form-control" size="4" width="4" type="text" disabled>&nbsp;&nbsp;-&nbsp;&nbsp;<input class="form-control" size="4" width="4" type="text" disabled> {$smarty.const.DAYS_FIELD_POSTFIX} </span>
                        </div>                  
                    </div>
                    <div class="filter_right">
                        <div class="filter_row filter_disable">
                            <div class="f_td_group f_td_group-pr">
                                <input class="form-control" type="text" disabled>
                            </div>
                        </div>
                    </div>                
                </div>
                <div class="filters_buttons">
                    <a href="javascript:void(0)" class="btn" disabled>{$smarty.const.TEXT_RESET}</a>
                    <button class="btn btn-primary" disabled>{$smarty.const.TEXT_SEARCH}</button>
                </div>
                <input type="hidden" name="row" id="row_id" value="{$app->controller->view->filters->row}" />

            </div>
        </div>
    </div>  
    <div class="widget box row dis_module" id="recovery_list">
        <div class="col-md-12">
            <div class="" id="recovery_list_data">

                <div class="alert" style="display:none;">
                    <i data-dismiss="alert" class="icon-remove close"></i>
                    <span id="message_text"></span>
                </div>
                <div class="">
                    <div class="btn-wr after btn-wr-top btn-wr-top1">
                        <div class="rec-cart-btn">
                            <a href="javascript:void(0)" class="btn postm-btn disable-btn">{$smarty.const.PSMSG}</a>
                            <a href="javascript:void(0)" class="btn collapse-btn disable-btn">{$smarty.const.ENTRY_COLLAPSE_ALL}</a>
                        </div>
                        <div>
                        </div>
                    </div>                
                    <form name="recovery">
                        <table class="table table-striped table-bordered table-hover table-responsive table-checkable datatable table-recover_cart_sales"
                               checkable_list="0" data_ajax="recover_cart_sales/list?type_list={$app->controller->type_list}&tdate={$app->controller->tdate}&exact_date={$app->controller->exact_date}">					   
                            <thead>

                                <tr>
                                    {foreach $app->controller->view->recoveryTable as $tableItem}
                                        <th{if $tableItem['not_important'] == 1} class="hidden-xs"{/if}>{$tableItem['title']}</th>
                                        {/foreach}
                                </tr>
                            </thead>
                        </table>
                    </form>     
                </div>
            </div>
        </div>
    </div>
    <!--===/modules list===-->

    <script type="text/javascript">

        var _table = null;
    
        var detailRows = {};

        function setFilterState() {
            var orig = $('#filterForm').serialize();
            var url = window.location.protocol + '//' + window.location.hostname + window.location.pathname + '?' + orig.replace(/[^&]+=\.?(?:&|$)/g, '')
            window.history.replaceState({}, '', url);
        }

        function resetStatement() {
            return false;
        }

        var jData;
        function onClickEvent(obj, table) {
            jData = table.fnSettings();
            if (jData.jqXHR.responseJSON.hasOwnProperty('grandTotal')) {
                $('#content').remove('.grand-total-bar').append('<div class="grand-total-bar">{$smarty.const.TEXT_GRAND_TOTAL} ' + jData.jqXHR.responseJSON.grandTotal + '</div>');
                $('.grand-total-bar').width(document.body.clientWidth - $('#sidebar-content').width() - 100);
            }
            $('.popup').popUp();
            jData._iRecordsTotal = 1;
            App.init(true);
        }

        function deleteRow(cid, bid) {
            return false;
        }

        function contacted(cid, bid) {
            return false;
        }

        function workedout(cid, bid) {
            return false;
        }

        function onUnclickEvent(obj, table) {

        }
        var cids = new Array();
        var bids = new Array();
        var batch_data = { 'batch': 1, 'type': 'm', 'batch_cids': cids, 'batch_bids': bids };


        var collapse = true;

        $(document).ready(function () {
            $('.set_all_platforms').click(function () {
                if ($(this).prop('checked')) {
                    $('input[name*=platform_id]').prop('checked', true);
                } else {
                    $('input[name*=platform_id]').prop('checked', false);
                }
                resetStatement();
            });

            $('input[name*=platform_id]').click(function () {
                resetStatement();
            });

            $('body').on('click', 'div[class*=contactcid]', function (e) {
                if (e.target.tagName.toLowerCase() != 'a') {
                    var obj = $(this).find('.btn-xs');
                    $(obj)[0].click();
                    e.preventDefault();
                }
            });

            $('body').on('click', 'div[class*=contactcid] .cust-email a', function (e) {
                contacted($(this).data('cid'), $(this).data('bid'));
            });

            $('.collapse-btn').click(function () {
                if (collapse) {
                    $('#recovery_list .widget:not(.widget-closed) .btn-xs').click();
                    collapse = false;
                } else {
                    $('#recovery_list .btn-xs').click();
                    collapse = true;
                }
            });

            $('.postm-btn').click(function () {
                batch_data = { 'batch': 1, 'type': 'm', 'batch_cids': [], 'batch_bids': [] };
                if ($('.batchbox:checked').size() > 0) {
                    cids = [];
                    bids = [];
                    $.each($('.batchbox:checked'), function (i, e) {
                        cids.push($(e).data('cid'));
                        bids.push($(e).data('bid'));
                    });
                    batch_data = { 'batch': 1, 'type': 'm', 'batch_cids': cids, 'batch_bids': bids };
                }

                $.post('recover_cart_sales/mail', batch_data, function (data) {

                    $('.postm-btn').popUp({
                        'data': data,
                        'event': 'show',
                        'only_show': true
                    });

                }, 'html');


            })




        });

    </script>

</div>