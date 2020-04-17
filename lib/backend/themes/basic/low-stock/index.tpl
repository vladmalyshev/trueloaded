
<!--=== Page Header ===-->
<div class="page-header">
  <div class="page-title">
    <h3>{$app->controller->view->headingTitle}</h3>
  </div> 
</div>
<!-- /Page Header -->
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
        </div>
        <table class="table table-striped table-bordered table-hover table-responsive table-checkable datatable double-grid" checkable_list="0,1,2,3" data_ajax="low-stock/list">
          <thead>
          <tr>
            {foreach $app->controller->view->StockTable as $tableItem}
              <th{if $tableItem['not_important'] == 1} class="hidden-xs"{/if}>{$tableItem['title']}</th>
            {/foreach}
          </tr>
          </thead>

        </table>


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
        $('.headd').html('');
        $.each(json.head.list, function (i, e){
            $('.headd').append('<div ><b>'+i+' '+e+'</b></div>');
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