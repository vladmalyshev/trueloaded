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
<!-- /Page Header -->
<div class="sale-report">
    <!--=== Page Content ===-->
    <div class="widget box box-wrapp-blue filter-wrapp widget-fixed">
        <div class="widget-header filter-title">
            <h4>{$smarty.const.TEXT_FILTER}</h4>
            <div class="toolbar no-padding">
              <div class="btn-group">
                <span class="btn btn-xs widget-collapse"><i class="icon-angle-down"></i></span>
              </div>
            </div>
          </div>
        <div class="widget-content filter_values">
            <form id="filterForm" name="filterForm" onsubmit="return updateData();">
            <div class="item_filter item_filter_1 choose_platform">
                {if $isMultiPlatform}
                    <div class="tl_filters_title">{$smarty.const.TEXT_COMMON_PLATFORM_FILTER}</div>
                    <div class="f_td f_td_radio ftd_block tl_fron_height">
                        <div><label class="radio_label"><input type="checkbox" name="platform[]" class="js_platform_checkboxes" value=""> {$smarty.const.TEXT_COMMON_PLATFORM_FILTER_ALL}</label></div>
                                {foreach $platforms as $platform}
                            <div><label class="radio_label"><input type="checkbox" name="platform[]" class="js_platform_checkboxes" value="{$platform['id']}" {if in_array($platform['id'], $app->controller->view->filter->platform)} checked="checked"{/if}> {$platform['text']}</label></div>
                                {/foreach}
                    </div>
                {/if}
            </div>
            <div class="wrap_filters after wrap_filters_4">
                <div class="item_filter item_filter_1">
                    <div class="tl_filters_title">{$smarty.const.TEXT_CHOOSE_PRECISION}</div>
                    <div class="wl-td">
                        <label>{$smarty.const.TEXT_PERIOD}:</label>
                        {Html::dropDownList('period', $app->controller->view->filter->period_selected, $app->controller->view->filter->period, ['class'=>'form-control', 'onchange'=>'updatePeriod(this.value);'])}
                    </div>
                    <div class="report-details-options">
                        {$options}
                    </div>
                    <div class="wl-td">
                        <label>{$smarty.const.TEXT_QUANTITY}:</label>
                        {Html::dropDownList('range', $app->controller->view->filter->range_selected, $app->controller->view->filter->range, ['class'=>'form-control'])}
                    </div>
                </div>
                
                
                <div class="filters_btn">
                    <a href="{$app->urlManager->createUrl('compare-report')}" class="btn" >{$smarty.const.TEXT_RESET}</a>
                    <a href="javascript:void(0)" onclick="updateData();" class="btn btn-primary" >{$smarty.const.IMAGE_UPDATE}</a>
                </div>
            </div>
            </form>
        </div>
    </div>
    <div class="row table-row">
        <div class="col-md-12">
            <div class="widget box">
                <div class="widget-header">
                    <h4><i class="icon-area-chart"></i><span id="table_title">Statistics</span><span class="range1">{$range}</span></h4>
                    <div class="toolbar no-padding">
                        <div class="btn-group">
                            <span class="btn btn-xs widget-collapse"><i class="icon-angle-down"></i></span>
                        </div>
                    </div>
                    <div class="export-block export-data" style="display: none;"><span>{$smarty.const.TEXT_EXPORT}</span><label>{Html::radio('exportO', true, ['value'=>'CSV', 'class' => 'export'])}CSV</label><label>{Html::radio('exportO', false, ['value'=>'XLS', 'class' => 'export'])}XLS</label><a href="javascript:void(0)" onclick="exportData();" class="btn export-btn">{$smarty.const.TEXT_EXPORT}</a><a class="blind" style="display:none;"></a></div>
                </div>
                <div class="widget-content after">
                    <div id="report-content"></div>
                </div>                            
            </div>

        </div>
    </div>

    
    
</div>            
<script>
function updatePeriod(type) {
    $.get('{$app->urlManager->createUrl('compare-report/load-options')}', { 'type':type },
            function(data, status){
                if (status == 'success') {
                    $('.report-details-options').html(data.options);
                }
            }, 'json');
}
function updateData() {
    orig = $('#filterForm').serialize();
    var url = window.location.origin + window.location.pathname + '?' + orig.replace(/[^&]+=\.?(?:&|$)/g, '')
    window.history.replaceState({ }, '', url);
    $.post("{$app->urlManager->createUrl('compare-report/generate')}", $('#filterForm').serialize(), function(data, status){
        if (status == "success") {
            $('#report-content').html(data);
            $('.export-block').show();
        }
    },"html");
    $('.js_platform_checkboxes:checked').each(function () {
        //$('#filterForm').find('select,input[name!="platform[]"]').serialize()
        console.log(this);
        $.post("{$app->urlManager->createUrl('compare-report/generate')}", $('#filterForm').find('select,input[name!="platform[]"]').serialize() + '&platform[]=' + $(this).val(), function(data, status){
        if (status == "success") {
            $('#report-content').append(data);
        }
    },"html");
    });
}

function datenum(v, date1904) {
    if(date1904) v+=1462;
    var epoch = Date.parse(v);
    return (epoch - new Date(Date.UTC(1899, 11, 30))) / (24 * 60 * 60 * 1000);
}

function sheet_from_array_of_arrays(data, opts) {
    var ws = {};
    var range = { s: { c:10000000, r:10000000 }, e: { c:0, r:0 } };
    for(var R = 0; R != data.length; ++R) {
        for(var C = 0; C != data[R].length; ++C) {
            if(range.s.r > R) range.s.r = R;
            if(range.s.c > C) range.s.c = C;
            if(range.e.r < R) range.e.r = R;
            if(range.e.c < C) range.e.c = C;
            var cell = { v: data[R][C] };
            if(cell.v == null) continue;
            var cell_ref = XLSX.utils.encode_cell({ c:C,r:R });

            if(typeof cell.v === 'number') cell.t = 'n';
            else if(typeof cell.v === 'boolean') cell.t = 'b';
            else if(cell.v instanceof Date) {
                cell.t = 'n'; cell.z = XLSX.SSF._table[14];
                cell.v = datenum(cell.v);
            }
            else cell.t = 's';
            cell.s = { font: { bold : true } };//??doesn't work
            ws[cell_ref] = cell;
        }
    }
    if(range.s.c < 10000000) ws['!ref'] = XLSX.utils.encode_range(range);
    return ws;
}

function Workbook() {
    if(!(this instanceof Workbook)) return new Workbook();
    this.SheetNames = [];
    this.Sheets = {};
}

function saveToFile(wb){
    var wbout = XLSX.write(wb, { bookType:'xlsx', bookSST:true, type: 'binary' });

    function s2ab(s) {
        var buf = new ArrayBuffer(s.length);
        var view = new Uint8Array(buf);
        for (var i=0; i!=s.length; ++i) view[i] = s.charCodeAt(i) & 0xFF;
        return buf;
    }
    var d = new Date();
    saveAs(new Blob([s2ab(wbout)],{ type:"application/octet-stream" }),  "sale_statistics_" +d.getDate() + d.getMonth() + "_" + d.getFullYear() + d.getHours() + d.getMinutes() + d.getSeconds() + ".xlsx");
}
function exportData() {
    
    var type = $('input:checked[name="exportO"]').val();
    
    if (type == 'XLS') {
        var data = [];
        $('#report-content .table').each(function () {
            var header = [];
            data.push(header);
            $(this).find('tr').each(function (i, e){
                var header = [];
                $(this).find('td, th').each(function (i, e){
                    header.push(e.textContent);
                });
                data.push(header);
            });
        });
        var ws_name = "Export";
        var wb = new Workbook(), ws = sheet_from_array_of_arrays(data);

        /* add worksheet to workbook */
        wb.SheetNames.push(ws_name);
        wb.Sheets[ws_name] = ws;
        var svgs = $(parent).parents('.widget.box').find('svg'), s = '', img;
        wb.Sheets[ws_name]['!images'] = [];

        saveToFile(wb);
        return false;
    }
    
    
    var form = document.createElement("form");
    form.target = "_blank";
    form.method = "POST";
    form.action = "{$app->urlManager->createUrl('compare-report/generate')}?export=" + type;

    $('#filterForm input, select').each(function () {
        var ex_data = document.createElement('input');
        ex_data.setAttribute('name', $(this).attr('name'));
        ex_data.setAttribute('type', 'hidden');
        ex_data.value = $(this).val();
        form.appendChild(ex_data);
    });
    document.body.appendChild(form);
    form.submit();

    return false;
}
$(document).ready(function() {
        var $platforms = $('.js_platform_checkboxes');
    var check_platform_checkboxes = function(){
        var checked_all = true;
        $platforms.not('[value=""]').each(function () {
            if (!this.checked) checked_all = false;
        });
        $platforms.filter('[value=""]').each(function() {
            this.checked = checked_all
        });
    };
    check_platform_checkboxes();
    $platforms.on('click',function(){
        var self = this;
        if (this.value=='') {
            $platforms.each(function(){
                this.checked = self.checked;
            });
        }else{
            var checked_all = this.checked;
            if ( checked_all ) {
                $platforms.not('[value=""]').each(function () {
                    if (!this.checked) checked_all = false;
                });
            }
            $platforms.filter('[value=""]').each(function() {
                this.checked = checked_all
            });
        }
    });
});
</script>

<div style="height: 50px;clear: left;"></div>
