<!--=== Page Header ===-->
<div class="page-header">
    <div class="page-title">
        <h3>{$app->controller->view->headingTitle}</h3>
    </div>
</div>
<!-- /Page Header -->
<div class="order-wrap">
<!--===list===-->
<div class="row order-box-list">
    <div class="col-md-12">
            <div class="widget-content dis_module">
              
                <table class="table table-striped table-selectable table-checkable table-hover table-responsive table-bordered datatable tab-cust tabl-res double-grid" checkable_list="" data_ajax="messages/list">
                    <thead>
                    <tr>
                        {foreach $app->controller->view->messagesTable as $tableItem}
                            <th{if $tableItem['not_important'] == 2} class="checkbox-column"{/if}{if $tableItem['not_important'] == 1} class="hidden-xs"{/if}>{$tableItem['title']}</th>
                        {/foreach}
                    </tr>
                    </thead>
                </table>
                
            </div>
    </div>
</div>
<!--===/list===-->
<!--===  management ===-->
<div class="row right_column" id="order_management" style="display: none;">
        <div class="widget box">
            <div class="widget-content fields_style" id="groups_management_data">
                <div class="scroll_col"></div>
            </div>
        </div>
</div>
<!--=== management ===-->
</div>
