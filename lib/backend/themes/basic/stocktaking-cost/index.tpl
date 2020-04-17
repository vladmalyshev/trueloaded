
<!--=== Page Header ===-->
<div class="page-header">
  <div class="page-title">
    <h3>{$app->controller->view->headingTitle}</h3>
  </div> 
</div>
<!-- /Page Header -->
<!--<div>{$excluded}</div>-->
<style>
  tr.orange{
    background: #f9f9f9;
	border-bottom:3px solid #ddd;
  }
  .headd {
	padding:10px 0 0 10px;
  }
  .order-wrap a {
	line-height: 1.428571429;
	color: #424242;
  }
  .cedit-block.cedit-block-2 .cr-ord-cust:before {
    content: '\f1b3';
  }
  .cedit-block.cedit-block-3 .cr-ord-cust:before, .cedit-block.cedit-block-4 .cr-ord-cust:before {
    content: '\f0d6';
  }
.cedit-top {
  margin:10px;
}
.cedit-block {
    float: left;
    width: 33.3333%;
    padding: 0 1%;
    border-left: 1px solid #d9d9d9;
}
</style>
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
        <div class="headd" style="width:100%;">
			<div class="cedit-top after">
				   
			</div>
        </div>
		<div class="col-md-12">
        <table class="table table-striped table-bordered table-hover table-responsive table-checkable table-selectable js-table-sortable datatable table-no-search" verticalHeight="true" checkable_list="0" data_ajax="stocktaking-cost/list">
          <thead>
          <tr>
            {foreach $app->controller->view->CostTable as $tableItem}
              <th{if $tableItem['not_important'] == 1} class="hidden-xs"{/if} width="{$tableItem['width']}">{$tableItem['title']}</th>
            {/foreach}
          </tr>
          </thead>

        </table>
		</div>


        <p class="btn-wr">        	
            <a style="display:none;" href="javascript:void(0);" ></a>
            <button class="btn btn-primary" onClick="_export(this);">{$smarty.const.TEXT_EXPORT}</button>
        </p>
        
      </div>

    </div>
  </div>
  <script type="text/javascript">

      function resetStatement() {
      }
      function onClickEvent(obj, table) {        
      }

      function onUnclickEvent(obj, table) {          
      }

      function applyFilter() {
          resetStatement();
          return false;
      }
   
   var fTable;
   
   function maluem(rows){
        var row;
        $.each(rows, function (i, e){
            row = fTable.fnGetNodes(e);
            //console.log(e);return;
            $(row).addClass('orange');
        });
   }
   
   function onDraw(json, table){
    fTable = table;
    if (json.head){
        $('.cedit-top').html('');
		s=1;
        $.each(json.head.list, function (i, e){
		    s++;
            $('.cedit-top').append('<div class="cedit-block cedit-block-'+s+'"><div class="cr-ord-cust"><span>'+i+'</span><div>'+e+'</div></div></div>');
        });
        if (json.head.row){
            if (fTable.fnSettings().fnDisplayEnd() > 0){
                maluem(json.head.row);
            } else {
                var tm = setInterval(function(){
                    if (fTable.fnSettings().fnDisplayEnd() > 0){
                         maluem(json.head.row);
                         var row = fTable.fnGetNodes(json.head.last);
                         $(row).addClass('orange');
                         clearInterval(tm);
                    }
                }, 1000);
            }
      
        }
    }
    
    return;
   }
   
   function _export(obj){
        $.get('stocktaking-cost/export', 
                {},
                function(data, status, e){
                        var filename = 'export.csv';
                        var reader = new FileReader();
                        reader.onload = function(e) {
                          $(obj).prev().attr({ "href": e.target.result, "download": filename }).get(0).click();
                        }                
                        reader.readAsDataURL(new Blob([data]));
                });
                return false;
   }
  </script>
  <!-- /Page Content -->
</div>