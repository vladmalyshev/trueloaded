<!--=== Page Header ===-->
<div class="page-header">
  <div class="page-title">
    <h3>{$app->controller->view->headingTitle}</h3>
  </div> 
</div>
<style>
  tr.orange{
    background: #f9f9f9;
	border-bottom:3px solid #ddd;
  }
</style>
<!-- /Page Header -->
<div class="widget box box-wrapp-blue filter-wrapp">
          <div class="widget-header filter-title">
            <h4>{$smarty.const.TEXT_FILTER}</h4>
            <div class="toolbar no-padding">
              <div class="btn-group">
                <span class="btn btn-xs widget-collapse"><i class="icon-angle-down"></i></span>
              </div>
            </div>
          </div>
          <div class="widget-content">
              <form id="filterForm" name="filterForm" onsubmit="return applyFilter();">                
                <div class="/*filter-box*/ filter-box-cus {if $isMultiPlatform}filter-box-pl{/if}">
                <table width="100%" border="0" cellpadding="0" cellspacing="0">                    
                    <tr>                        
                        <td>                        
                         <table style="vertical-align: top;" width="100%" >
                            <tr>
                                {if $isMultiPlatform}
                                <td align="left"  style="vertical-align: top;">
                                
                                    <fieldset style="display:inline-grid;">
                                    <legend>{$smarty.const.TEXT_COMMON_PLATFORM_FILTER}</legend>
                                    <div class="f_td f_td_radio ftd_block">
                                      <div><label class="radio_label"><input type="checkbox" name="platform[]" class="js_platform_checkboxes uniform" value=""> {$smarty.const.TEXT_COMMON_PLATFORM_FILTER_ALL}</label></div>
                                      {foreach $platforms as $platform}
                                        <div><label class="radio_label"><input type="checkbox" name="platform[]" class="js_platform_checkboxes uniform" value="{$platform['id']}" {if in_array($platform['id'], $app->controller->view->filters->platform)} checked="checked"{/if}> {$platform['text']}</label></div>
                                      {/foreach}
                                    </div>
                                    </fieldset>
                                 </td>
                                {/if}
                                <td align="left"  style="vertical-align: top; width:50%">
                                    <fieldset style="display:inline-grid;">
                                    <legend>{$smarty.const.TEXT_DATE_RANGE}</legend>
                                    <div class="f_td f_td_radio ftd_block">
                                      <div><label>{$smarty.const.TEXT_FROM}<input type="text" name="start_date" class="form-control datepicker" value="{$app->controller->start_date}"></label></div>
                                      <div><label>{$smarty.const.TEXT_TO}<input type="text" name="end_date" class="form-control datepicker" value="{$app->controller->end_date}"></label></div>
                                    </div>
                                    </fieldset>
                                </td>
                            </tr>
                        </table>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" align="right">
                            <a href="javascript:void(0)" onclick="return resetFilter();" class="btn">{$smarty.const.TEXT_RESET}</a>&nbsp;&nbsp;&nbsp;<button type="submit" class="btn btn-primary">{$smarty.const.TEXT_SEARCH}</button>&nbsp;
                        </td>
                    </tr>
                </table>  
                <input type="hidden" name="row" id="row_id" value="{$app->controller->view->filters->row}" />
                </div>
            </form>
          </div>
        </div>
<div class="order-wrap">    
  <input type="hidden" id="row_id">
  <!--=== Page Content ===-->
  <div class="row">
    <div class="col-md-12">
      <div class="widget-content">
        <div class="alert fade in" style="display:none;">
          <i data-dismiss="alert" class="icon-remove close"></i>
          <span id="message_plce"></span>
        </div>
        
        <table class="table table-striped table-bordered table-hover table-responsive table-checkable datatable double-grid" checkable_list="0,1,2" data_ajax="manufacturer-sales/list">
          <thead>
          <tr>
            {foreach $app->controller->view->reportTable as $tableItem}
              <th{if $tableItem['not_important'] == 1} class="hidden-xs"{/if}>{$tableItem['title']}</th>
            {/foreach}
          </tr>
          </thead>

        </table>


        <p class="btn-wr">        	
            <a style="display:none;" href="javascript:void(0);" ></a>
            <button class="btn btn-primary" onClick="_export();">{$smarty.const.TEXT_EXPORT}</button>
        </p>
        
      </div>

    </div>
  </div>
  <script type="text/javascript">
var fTable;

    function resetFilter(){
        $.each(document.getElementById('filterForm').elements, function(i, e){
            if ($(e).is('input')){
                $(e).val('');
            } else if ($(e).is('select')){
                $(e).val('') || $(e).val(0);
            }
        })
        applyFilter();
    }
    function setFilterState(only_query) {
        orig = $('#filterForm').serialize();
        var query = orig.replace(/[^&]+=\.?(?:&|$)/g, '').replace(/\[/g, '%5B').replace(/\]/g, '%5D');
        if (only_query){
            return query;
        } else {
            var url = window.location.origin + window.location.pathname + '?' + query;
            window.history.replaceState({ }, '', url);
        }
    }

    function resetStatement() {
        setFilterState();
        fTable.fnDraw(false);
        return false;
    }
    function onClickEvent(obj, table) {
        fTable = table;
        $('.dataTables_wrapper .row:first .dataTables_header div:first').html('{$smarty.const.TEXT_USE_DOUBLE_CLICK}');
    }

    function onUnclickEvent(obj, table) {          
    }

    function applyFilter() {      
      resetStatement();
      return false;    
    }

    function getTableSelectedCount(){
        return 0;
    }
   
   var currentItem;
   $(document).ready(function(){

       $( ".datepicker" ).datepicker({
           changeMonth: true,
           changeYear: true,
           showOtherMonths:true,
           autoSize: false,
           dateFormat: '{$smarty.const.DATE_FORMAT_DATEPICKER}',
           onSelect: function (e) {
               if ($(this).val().length > 0) {
                   $(this).siblings('span').addClass('active_options');
               }else{
                   $(this).siblings('span').removeClass('active_options');
               }
           }
       });

        var $platforms = $('.js_platform_checkboxes');
        
        $('.js_platform_checkboxes[value=""]').click(function(){
            if ($(this).parent().hasClass('checked')){
                $('.js_platform_checkboxes').filter('[value!=""]').each(function() {
                    $(this).parent().addClass('checked');
                    $(this).prop('checked', true);
                });
            } else {
                $('.js_platform_checkboxes').filter('[value!=""]').each(function() {
                    $(this).parent().removeClass('checked');
                    $(this).prop('checked', false);
                });
            }
        })
        
        var check_platform_checkboxes = function(){
            if (!$platforms.size()) return;
            var checked_all = true;
            $platforms.not('[value=""]').each(function () {
              if (!this.checked) checked_all = false;
            });
            
            if (checked_all){        
                $platforms.filter('[value=""]').parent().addClass('checked');
                $platforms[0].checked = true;
            } else {
                $platforms.filter('[value=""]').parent().removeClass('checked');
                $platforms[0].checked = false;
            }        
        };
        
        check_platform_checkboxes();
        
        var fnFormatDetails = function(tr, json){
           if (json.hasOwnProperty('data')){
                var str = '<table class="table datatable "><tr class="orange"><td>{$smarty.const.TABLE_HEADING_PRODUCTS_MODEL}</td><td>{$smarty.const.TEXT_PRODUCTS_NAME}</td><td>{$smarty.const.TABLE_HEADING_QUANTITY}</td><td>{$smarty.const.TEXT_AMOUNT}</td></tr>';
                var _class= '';
                $.each(json['data'], function(i, e){
                    if (i == json['data'].length - 1) _class = 'orange';
                    str += '<tr class="' + _class + '"><td>'+e[0]+'</td><td>'+e[1]+'</td><td>'+e[2]+'</td><td>'+e[3]+'</td></tr>';
                });
                return str;
            }
            return false;
        }
        
        $('body').on('dblclick', 'table.datatable tr', function(){
            var href = $(this).find('div:first').data('click');
            var id =  $(this).find('div:first').data('id');
            var tr = this;
            
            if ( fTable.fnIsOpen(tr) ) {
                fTable.fnClose(tr);
            } else {
                $.get(href, $('#filterForm').serialize() + '&mID=' + id, function(data){
                    fTable.fnOpen(tr, fnFormatDetails(tr, data), 'details')
                }, "json");
            }
            
        })

   });
   
   function _export(){
     var form = $('#filterForm')[0];
     form.setAttribute('action', 'manufacturer-sales/export?' + setFilterState(true));
     form.submit();
   }
   
   
  </script>
  <!-- /Page Content -->
</div>