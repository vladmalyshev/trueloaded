
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
  <div class="row order-box-list" style="width:99%;">
    <div class="col-md-12">
      <div class="widget-content">
        <div class="alert fade in" style="display:none;">
          <i data-dismiss="alert" class="icon-remove close"></i>
          <span id="message_plce"></span>
        </div>
        <div class="ord_status_filter_row">
            <form id="filterForm" name="filterForm" onsubmit="return applyFilter();">
                <select class="form-control" name="osgID" onchange="return applyFilter();">
                    <option value="0">{$smarty.const.GOOGLE_CATEGORY_ALL_CATEGORIES}</option>
                    <option value="1">{$smarty.const.GOOGLE_CATEGORY_TOP_CATEGORIES}</option>                   
                </select>
            </form>
        </div>
        {if {$messages|@count} > 0}
          {foreach $messages as $message}
            <div class="alert fade in {$message['messageType']}">
              <i data-dismiss="alert" class="icon-remove close"></i>
              <span id="message_plce">{$message['message']}</span>
            </div>
          {/foreach}
        {/if}
        <div id="list_bread_crumb" style="margin: 0 0 0 250px;"></div>
        <table class="table table-striped table-bordered table-hover table-responsive table-checkable js-table-sortable datatable catelogue-grid" checkable_list="0,1" data_ajax="google-categories/list">
            <thead>
            <tr>
              {foreach $app->controller->view->ViewTable as $tableItem}
                <th{if $tableItem['not_important'] == 1} class="hidden-xs"{/if} width="{$tableItem['width']}">{$tableItem['title']}</th>
              {/foreach}
            </tr>
            </thead>
        </table>
        <input type="hidden" name="category_id" id="global_id" value="{$app->controller->view->filters->category_id}" />
        <input type="hidden" name="row" id="row_id" value="{$app->controller->view->filters->row}" />
        </form>
      </div>

    </div>
  </div>
  <script type="text/javascript">

      function switchOffCollapse(id) {
          if ($("#"+id).children('i').hasClass('icon-angle-down')) {
              $("#"+id).click();
          }
      }

      function switchOnCollapse(id) {
          if ($("#"+id).children('i').hasClass('icon-angle-up')) {
              $("#"+id).click();
          }
      }

      function resetStatement() {
          $("#item_management").hide();
          switchOnCollapse('status_list_collapse');
          var table = $('.table').DataTable();
          table.draw(false);
          $(window).scrollTop(0);
          return false;
      }
      function onClickEvent(obj, table) {
          $("#item_management").hide();
          $('#item_management_data .scroll_col').html('');
          $('#row_id').val(table.find(obj).index());
          var categories_id = $(obj).find('input.cell_identify').val();
          $.post("google-categories/list-actions", { 'categories_id' : categories_id }, function(data, status){
              if (status == "success") {
                  $('#item_management_data .scroll_col').html(data);
                  $("#item_management").show();
              } else {
                  alert("Request error.");
              }
          },"html");
      }

      function onUnclickEvent(obj, table) {
          $("#item_management").hide();
          var event_id = $(obj).find('input.cell_identify').val();
          var type_code = $(obj).find('input.cell_type').val();
          $(table).DataTable().draw(false);
      }

      function itemSave(id){
          $.post("google-categories/save?categories_id="+id, $('form[name=google_category]').serialize(), function(data, status){
              if (status == "success") {
                  //$('#item_management_data').html(data);
                  //$("#item_management").show();
                  $('.alert #message_plce').html('');
                  $('.alert').show().removeClass('alert-error alert-success alert-warning').addClass(data['messageType']).find('#message_plce').append(data['message']);
                  resetStatement();
                  switchOffCollapse('status_list_collapse');
              } else {
                  alert("Request error.");
              }
          },"json");
          return false;
      }

    function applyFilter() {
        $('#global_id').val('0');
        resetStatement();
        return false;
    }
      
    function onClickEvent(obj, table, event) {
        if ( $(event.target).is(':input') ) return false;
        var dtable = $(table).DataTable();
        var id = dtable.row('.selected').index();
        $("#row_id").val(id);
        setFilterState();
        $(".check_on_off").bootstrapSwitch(
            {
                onSwitchChange: function (element, arguments) {
                    switchStatement(element.target.name, element.target.value, arguments);
                    return true;
                },
                onText: "{$smarty.const.SW_ON}",
                offText: "{$smarty.const.SW_OFF}",
                handleWidth: '20px',
                labelWidth: '24px'
            }
        );
        $(".check_on_off_check").change(
            function (element, arguments) {
                ///console.log(element.target.name, element.target.value, arguments);
                if (this.checked) {
                    var arguments = true;
                }else{
                    var arguments = false;
                }
                switchStatement(element.target.name, element.target.value, arguments);
                return true;
        });
    }

    function onUnclickEvent(obj, table, event) {
        if ( $(event.target).is(':input') ) return false;
        $("#catalog_management").hide();
        var event_id = $(obj).find('input.cell_identify').val();
        var type_code = $(obj).find('input.cell_type').val();
        /*$(table).dataTable({
            destroy: true,
            "ajax": "categories/list/parent/"+event_id
        });*/
        if (type_code == 'category' || type_code == 'parent') {
            $('#global_id').val(event_id);
            changeCategory($('span#'+event_id))
            //$(table).DataTable().draw(false);
        }

    }
        
    function switchStatement(type ,id, status) {
        $.post("google-categories/switch-status", { 'type' : type, 'id' : id, 'status' : status }, function(data, status){
            if (status == "success") {
                resetStatement();
            } else {
                alert("Request error.");
            }
        },"html");
    }
    
    function changeCategory(obj) {
        //console.info('run changeCategory');
        var event_id = $(obj).attr('id');
        if ( typeof event_id === 'undefined' ) {
            event_id = $(obj).data('id');
        }
        {*var parent = $(obj).attr('id');
        if (typeof parent === 'undefined' ) {
            parent = '0';
        }
        if (parent !== '0') {
            $('select[name=osgID]').val('0');
            //$('select[name=osgID]').parent().submit();
        }
        //console.info(event_id);*}
        
        if($('select[name=osgID]').val() == '1') {
            $('select[name=osgID]').val('0');
            applyFilter();
        }        
        
        $('#global_id').val(event_id);
        $("div.dd3-content.selected").removeClass('selected');
        $("#cat-main-box-cat-" + event_id).addClass('selected');

        var table = $('.table').DataTable();
        table.page( 'first' );// .draw( 'page' );

        resetFilter();       
        return false;
    }
    
    function resetFilter() {
        
        $("#row_id").val(0);
        resetStatement();
        return false;
    }
    
    function setFilterState() {
        orig = $('#filterForm').serialize();
        var url = window.location.origin + window.location.pathname + '?' + orig.replace(/[^&]+=\.?(?:&|$)/g, '').replace(/\[/g, '%5B').replace(/\]/g, '%5D');
        window.history.replaceState({ }, '', url);
    }
    
    function resetStatement() {
        
        setFilterState();
        var table = $('.table').DataTable();
        table.draw(false);
        return false;
    }
    
    $( document ).ready(function() {
        $('#list_bread_crumb').on('click','.js-category-navigate',function(event){
            changeCategory(event.target);
        });
        {*console.info($('.hierarchy_line').length);
        $('.hierarchy_line').on('click','.js-category-navigate', function(event) {
            changeCategory(event.target);
        });*}
    });
  </script>
  <!--===Actions ===-->
  <!--<div class="row right_column" id="item_management">
    <div class="widget box">
      <div class="widget-content fields_style" id="item_management_data">
        <div class="scroll_col"></div>
      </div>
    </div>

  </div>-->
  <!--===Actions ===-->
  <!-- /Page Content -->
</div>
    <style type="text/css">
    .underline-on-hover:hover {
        text-decoration: underline;
    }
    </style>