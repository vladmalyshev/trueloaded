{\backend\assets\BDPAsset::register($this)|void}
{use class="\yii\helpers\Html"}
{use class="\yii\helpers\Url"}
<div class="widget box box-wrapp-blue filter-wrapp widget-closed1 widget-fixed">
    <div class="widget-header filter-title">
        <h4>{$smarty.const.TEXT_FILTER}</h4>
        <div class="toolbar no-padding">
          <div class="btn-group">
            <span class="btn btn-xs widget-collapse"><i class="icon-angle-up"></i></span>
          </div>
        </div>
    </div>
    <div class="widget-content">
        
            <form id="filterForm" name="filterForm" onsubmit="return applyFilter();">
                <div class="wrap_filters after {if $isMultiPlatform}wrap_filters_4{/if}">
                    <div class="item_filter item_filter_1 choose_platform">
                        <div class="tl_filters_title">{$smarty.const.TEXT_COMMON_PLATFORM_TAB}</div>
                        <div class="f_td f_td_radio ftd_block">
                            <div>
                                {assign var = items value = \yii\helpers\ArrayHelper::map($platforms, 'id', 'text')}
                                {Html::dropDownList('platform', null, $items, ['class' => 'form-control'])}
                            </div>
                        </div>
                    </div> 
                    <div class="item_filter item_filter_2">
                        <div class="">
                            <label>{$smarty.const.TEXT_DATE_RANGE}</label>
                            <div class="wl-td">
                             <label>{$smarty.const.TEXT_FROM}:</label>
                            {Html::input('text', 'start_date', date("d/m/Y", strtotime("-1 year")), ['class' =>'form-control'])}
                            </div>
                            <div class="wl-td">
                            <label>{$smarty.const.TEXT_TO}</label>
                            {Html::input('text', 'end_date', date("d/m/Y"), ['class' =>'form-control'])}
                            </div>
                        </div>
                        <div class="">
                            <label>View ID</label>
                            <div class="wl-td">
                            {Html::input('text', 'view_id', $view_id, ['class' =>'form-control'])}
                            {Html::a(TEXT_ANALYTICS_SETTINGS, Url::toRoute('google-settings/index?#report'),['target' => '_blank'])}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="filters_btn">
                    <a herf="javascript:void(0)" class="btn btn-delete trash-keywords">{$smarty.const.IMAGE_TRASH}</a>
                    <button type="submit" class="btn btn-primary">{$smarty.const.IMAGE_UPDATE}</button>
                </div>
            </form>
        
    </div>
</div>

<div class="order-wrap">
<input type="hidden" name="row" id="row_id" value="{$app->controller->view->row_id}" />
<div class="row order-box-list">
    <div class="col-md-12">
            <div class="widget-content">
                <table class="table table-striped table-bordered table-hover table-responsive table-checkable datatable double-grid" checkable_list="0,1" data_ajax="{Url::to('google_keywords/list')}">
                    <thead>
                    <tr>
                        {foreach $app->controller->view->keywordTable as $tableItem}
                            <th{if $tableItem['not_important'] == 1} class="hidden-xs"{/if}>{$tableItem['title']}</th>
                        {/foreach}
                    </tr>
                    </thead>
                </table>
            </div>

    </div>
</div>
                <!--===Actions ===-->
                <div class="row right_column" id="catalog_management">
                    <div class="widget box">
                        <div class="widget-content" id="catalog_management_data">
                            <div class="scroll_col"></div>
                        </div>
                    </div>
                </div>
				<!--===Actions ===-->
</div>
<script>
var kTable;
function applyFilter(){
    if ($('input[name=view_id]').val().length > 0){
        alertMessage('<div class="preloader"></div>');
        $.post("{Url::toRoute(['google_keywords/fetch-words'])}", 
            $('#filterForm').serialize(),
        function (data, status) {        
            if (status == "success") {
                $('.pop-up-close:last').trigger('click');
                var str = '';
                Object.keys(data).map(function(i, e){
                    str = i + ': '+ data[i];
                })
                alertMessage('<center><br/>'+str+'<br/><br/></center>');
                kTable.fnDraw(false);
            } else {
                alert("Request error.");
            }
        }, "json");
    } else {
        alertMessage('<center><br/>Please define View ID<br/><br/></center>');
    }
    return false;            
}

function confirmDeleteCategory(id){
    bootbox.dialog({
        message: "{$smarty.const.IMAGE_TRASH} <span class=\"lowercase\">{$smarty.const.TABLE_HEADING_KEYWORDS}?</span>",
        title: "{$smarty.const.IMAGE_TRASH} <span class=\"lowercase\">{$smarty.const.TABLE_HEADING_KEYWORDS}</span>",
        buttons: {
            success: {
                    label: "Yes",
                    className: "btn-delete",
                    callback: function() {
                        $.post("google_keywords/delete", { 'gapi_id' : id }, function(data, status){
                            if (status == "success") {
                                kTable.fnDraw(false);
                            } else {
                                alert("Request error.");
                            }
                        },"html");
                    }
            },
            main: {
                    label: "Cancel",
                    className: "btn-cancel",
                    callback: function() {
                            //console.log("Primary button");
                    }
            }
        }
    });
}

function onClickEvent(obj, table) {
    $('#row_id').val(table.find(obj).index());
    var event_id = $(obj).find('input.cell_identify').val();
    kTable = table;
    $.post("google_keywords/preview", { 'gapi_id' : event_id, 'row_id' : $('#row_id').val() }, function(data, status){
            if (status == "success") {
                $('#catalog_management_data').html(data);
                $("#catalog_management").show();
            } else {
                alert("Request error.");
            }
        },"html");
}
function onUnclickEvent(obj, table){
  return false;
}

 $(document).ready(function(){
 
        $('select[name=platform]').change(function(){
            var id = $(this).val();
            $.get("{Url::toRoute(['google_keywords/get-view'])}", {
                'platform_id': id
            },
            function(data, status){
                if (data.view_id){
                    if (Number.isInteger(parseInt(data.view_id))){
                        $('input[name=view_id]').val(data.view_id);
                    } else {
                        $('input[name=view_id]').val('');
                    }
                }
                
                if (data.settings == false){
                    alertMessage('<center><br/>Configuration file is not detected. Please check settings.<br/><br/></center>');
                }
                kTable.fnDraw(false);                
            }, 'json');
        })
            
        $('input[name=start_date] ').datepicker({ 
            'minViewMode':0,
            'format':'dd/mm/yyyy',
            autoclose:true,
            endDate:"-1",
            beforeShowMonth: function(date){
                var $end = $('input[name=end_date]').val();
                if ($end.length > 0){
                    $_end = $end.split("/");
                    $end = $_end[1]+'/'+$_end[0]+'/' + $_end[2];
                    $gend = new Date($end);
                    return date <= $gend;
                }
                return true;
            }
            }).on('show', function(e){
                var $end = $('input[name=end_date]').val();
                var $send = new Date(e.date);
                if ($end.length > 0){
                    $_end = $end.split("/");
                    $end = $_end[1]+'/'+$_end[0]+'/' + $_end[2];
                    $gend = new Date($end);
                    if ($gend.getFullYear() == $send.getFullYear() || isNaN($send.getFullYear())){
                        $('input[name=start_date]').datepicker('setEndDate', $gend);
                    } else {
                        $('input[name=start_date]').datepicker('setEndDate', '');
                    }
                }
            })
            
            
        $('input[name=end_date] ').datepicker({ 
            'minViewMode':0,
            'format':'dd/mm/yyyy',
            autoclose:true,
            endDate:"-1",
            beforeShowMonth: function(date){
                var $end = $('input[name=start_date]').val();
                if ($end.length > 0){
                    $_end = $end.split("/");
                    $end = $_end[1]+'/'+$_end[0]+'/' + $_end[2];
                    $gend = new Date($end);
                    return date <= $gend;
                }
                return true;
            }
            }).on('show', function(e){
                var $start = $('input[name=start_date]').val();
                var $sstart = new Date(e.date);
                if ($start.length > 0){
                    $_start = $start.split("/");
                    $start = $_start[1]+'/'+$_start[0]+'/' + $_start[2];
                    $gstart = new Date($start);
                    if ($gstart.getFullYear() == $sstart.getFullYear() || isNaN($sstart.getFullYear()) ){
                        $('input[name=end_date]').datepicker('setStartDate', new Date($start));
                    } else {
                        $('input[name=end_date]').datepicker('setStartDate', '');
                    }                
                }
            });
            
            $('.trash-keywords').click(function(){
                bootbox.dialog({
                message: "{$smarty.const.IMAGE_TRASH} <span class=\"lowercase\">{$smarty.const.TEXT_KEYWORDS} " + $('select[name=platform] option:selected').text() + "?</span>",
                title: "{$smarty.const.IMAGE_TRASH} <span class=\"lowercase\">{$smarty.const.TEXT_KEYWORDS}</span>",
                buttons: {
                        success: {
                                label: "Yes",
                                className: "btn-delete",
                                callback: function() {
                                    $.post("google_keywords/trash", { 'platform_id' : $('select[name=platform]').val() }, function(data, status){
                                        if (status == "success") {
                                            kTable.fnDraw(false);
                                        } else {
                                            alert("Request error.");
                                        }
                                    },"html");
                                }
                        },
                        main: {
                                label: "Cancel",
                                className: "btn-cancel",
                                callback: function() {
                                        //console.log("Primary button");
                                }
                        }
                }
        });
            })
});
</script>