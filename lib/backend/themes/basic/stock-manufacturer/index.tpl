{\backend\assets\BDPAsset::register($this)|void}
{\backend\assets\BDTPAsset::register($this)|void}
{use class="yii\helpers\Html"}
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
              <form id="filterForm" name="filterForm" action="stock-manufacturer" onsubmit="return applyFilter();">
                <div class="/*filter-box*/ filter-box-cus {if $isMultiPlatform}filter-box-pl{/if} item_filter item_filter_2" style="width:100%;">
                    <div class="item_filter item_filter_2">
                        <div class="wl-td">
                            <label>{$smarty.const.TEXT_MANUFACTURERS}</label>
                            {Html::dropDownList('brand[]', $app->controller->view->filters->selected_brands, $app->controller->view->filters->brands, ['class' => '', 'multiple' => 'multiple', 'data-role' => 'multiselect'])}
                        </div>
                        <div class="wl-td">
                            <label>{$smarty.const.FIND_PRODUCTS}</label>
                            {Html::textInput('product', $app->controller->view->filters->product, ['class' => 'form-control', 'id' => 'search_text'])}
                            {Html::hiddenInput('products_id', $app->controller->view->filters->products_id)}
                        </div>
                        <div class="wl-td">
                            <label>on Date</label>
                            {Html::textInput('exact_date', '', ['class' => 'form-control datepicker'])}
                        </div>
                    </div>
                    <div class="item_filter item_filter_2">
                    <table width="100%" border="0" cellpadding="0" cellspacing="0">                    
                        <tr>
                            <td>
                              <label>{$smarty.const.TEXT_SHOW_SUPLLIER_PRICE}</label><br/>
                                {Html::radioList('ssp', (int)$app->controller->showSupplierPrice, [TEXT_NO, TEXT_YES], ['onchange' => 'document.forms["filterForm"].submit();'])}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" align="right">
                                <a href="javascript:void(0)" onclick="return resetFilter();" class="btn">{$smarty.const.TEXT_RESET}</a>&nbsp;&nbsp;&nbsp;<button type="submit" class="btn btn-primary">{$smarty.const.TEXT_SEARCH}</button>&nbsp;
                            </td>
                        </tr>
                    </table>  
                    <input type="hidden" name="row" id="row_id" value="{$app->controller->view->filters->row}" />
                    
                    </div>
                    </div>
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
        
        <table class="table table-striped table-bordered table-hover table-responsive table-checkable datatable double-grid " checkable_list="" data_ajax="stock-manufacturer/list">
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
        $("form select[data-role=multiselect]").multipleSelect('uncheckAll');
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
     var rows = fTable.fnGetNodes();
     var toral_row = rows[rows.length-1];
     $(toral_row).addClass('orange');
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
   
   
   function _export(){
     var form = $('#filterForm')[0];
     form.setAttribute('action', 'stock-manufacturer/export?' + setFilterState(true));
     form.submit();
     form.setAttribute('action', '');
   }
   
   $(document).ready(function(){

        $("form select[data-role=multiselect]").multipleSelect({
                multiple: true,
                filter: true
        });
        
        $('.datepicker').datetimepicker({
            format: 'DD MMM YYYY h:mm A'
        });
        /*$('.datepicker').datepicker({
            'autoclose':true,
            'minViewMode':0,
            'format':'dd/mm/yyyy',
        });*/
        
        $('#search_text').autocomplete({
			create: function(){
				$(this).data('ui-autocomplete')._renderItem = function( ul, item ) {
					return $( "<li></li>" )
						.data( "item.autocomplete", item )
						.append( "<a>"+(item.hasOwnProperty('image') && item.image.length>0?"<img src='" + item.image + "' align='left' width='25px' height='25px'>":'')+"<span>" + item.label + "</span></a>")
						.appendTo( ul );
					};
			},
			source: function(request, response){
				if (request.term.length > 2){                    
                        $.get("{\Yii::$app->urlManager->createUrl('stock-manufacturer/seacrh-product')}", {
                            'search':request.term,
                        }, function(data){
                            response($.map(data, function(item, i) {
                                return {
                                        values: item.text,
                                        label: item.text,
                                        id: parseInt(item.id),                                        
                                    };
                                }));
                        },'json');
                    
				}
			},
            minLength: 2,
            autoFocus: true,
            delay: 0,
            appendTo: '.auto-wrapp',
            select: function(event, ui) {
				if (ui.item.id > 0){
					$('input[name=products_id]').val(ui.item.id)                    
				}
			},
        }).focus(function () {
			$('#search_text').autocomplete("search");  
        });
    })
   
   
  </script>
  <!-- /Page Content -->
</div>