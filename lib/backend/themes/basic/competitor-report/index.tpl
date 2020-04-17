{use class="\yii\helpers\Html"}
{use class="\yii\helpers\Url"}
{\backend\assets\BDPAsset::register($this)|void}
{\backend\assets\XLSAsset::register($this)|void}
<!--=== Page Header ===-->
<div class="page-header">
    <div class="page-title">
        <h3>{$app->controller->view->headingTitle}</h3>
    </div>
</div>
<style>
.smile{ font-size:24px;font-weight:bold;text-align:center; }
.smile.green{ color:#08ce08; }
.smile.red{ color:#f44336; }
</style>
<!-- /Page Header -->
<div class="competitor-report">    
    <div class="row table-row">
        <div class="col-md-12">
            
            <div class="widget box">
                <div class="widget-header ">
                    <h4 class="s_filter_title">Filter</h4>
                    <div>
                    {tep_draw_form('creport', 'competitor-report', '', 'get', 'id="filterForm"')}
                    {Html::dropDownList('type', $app->controller->selectedType, $app->controller->view->filter->types, ['class'=>'form-control', 'onchange'=>'applyFilter();'])}
                    </form>
                    <br/>
                    </div>
                </div> 
                 
                <div class="widget-content after">
                    <div class="wrap_filters after wrap_filters_4">
                        <div class="item_filter item_filter_1">
                            <div class="tl_filters_title">{$smarty.const.TEXT_CHOOSE_PRECISION}</div>
                            <div class="wl-td">
                                <label>{$smarty.const.TEXT_PRECISION}</label>
                                
                            </div>                            
                        </div>   
                    </div>
                    <div id="report-content">
                        <table class="table tabl-res table-striped table-selectable table-checkable table-hover table-responsive table-bordered datatable table-switch-on-off double-grid" checkable_list="0" data_ajax="competitor-report/list">
                            <thead>
                                    <tr>
                                        {foreach $app->controller->view->reportTable as $tableItem}
                                            <th{if $tableItem['not_important'] == 2} class="checkbox-column"{/if}{if $tableItem['not_important'] == 1} class="hidden-xs"{/if}>{$tableItem['title']}</th>
                                        {/foreach}
                                    </tr>
                            </thead>

                    </table>            

                    
                    </div>
                </div>                            
            </div>

        </div>
    </div>
</div>

<script>
var cTable;
function setFilterState() {
    orig = $('#filterForm').serialize();
    var url = window.location.origin + window.location.pathname + '?' + orig.replace(/[^&]+=\.?(?:&|$)/g, '')
    window.history.replaceState({ }, '', url);
}
function applyFilter() {
    setFilterState();
    console.log(2);
    cTable.fnDraw(false);
    return false;    
}

function onClickEvent(obj, table) {
    cTable = table;    
}
</script>
