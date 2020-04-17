{use class="yii\helpers\Html"}
<!--=== Page Header ===-->
<div class="page-header">
  <div class="page-title">
    <h3>{$app->controller->view->headingTitle}</h3>
  </div>
</div>
  <div class="popup-box-wrap pop-mess alert" style="display:none;">
            <div class="around-pop-up"></div>
            <div class="popup-box">
                <div class="pop-up-close pop-up-close-alert"></div>
                <div class="pop-up-content">
                    <div class="popup-heading">{$smarty.const.TEXT_NOTIFIC}</div>
                    <div class="popup-content pop-mess-cont pop-mess-cont-{$messageType}">
                        <span id="message_plce"></span>
                    </div>  
                </div>   
                 <div class="noti-btn">
                    <div></div>
                    <div><span class="btn btn-primary">{$smarty.const.TEXT_BTN_OK}</span></div>
                </div>
            </div>  
            <script>
            $('body').scrollTop(0);
            $('.pop-mess .pop-up-close-alert, .noti-btn .btn').click(function(){
                $(this).parents('.pop-mess').remove();
            });
        </script>
        </div>
<!-- /Page Header -->

{if $isMultiPlatforms}
    <div class="tabbable tabbable-custom" style="margin-bottom: 0;">
        <ul class="nav nav-tabs">
            {foreach $platforms as $platform}
            <li class="{if $platform['id']==$selected_platform_id} active {/if}">{if $platform['id']!=$selected_platform_id}<a style="float:right;padding:0 7px; margin-bottom: -3px;margin-right: -5px; z-index: 10;" class="gso-copy"  href="#" onclick="return copyGSO('{$selected_platform_id}','{$platform['id']}');"><span class="icon-copy"></span></a>{else}<span style="float:right;padding:0 7px;height:19px;"></span>{/if}<a class="js_link_platform_modules_select" href="{$platform['link']}" data-platform_id="{$platform['id']}"><span>{$platform['text']}</span></a></li>
            {/foreach}
        </ul>
    </div>
{/if}

<div class="order-wrap">
  <!--=== Page Content ===-->
  <div class="row ">
    <div class="col-md-12">
{if {$messages|@count} > 0}
          {foreach $messages as $message}
            <div class="alert fade in {$message['messageType']}">
              <i data-dismiss="alert" class="icon-remove close"></i>
              <span id="message_plce">{$message['message']}</span>
            </div>
          {/foreach}
        {/if}

            <form id="filterForm" name="filterForm" onsubmit="return applyFilter();">
              <input type="hidden" name="platform_id" id="page_platform_id" value="{$selected_platform_id}" />
              <input type="hidden" name="row" id="row_id" value="{$app->controller->view->filters->row}" />
                <div class="ord_status_filter_row modules-filter">
                    <div>{tep_draw_pull_down_menu('category_id', $top_categories, $app->controller->view->filters->categories_id, 'class="form-control" onchange="applyFilter();"')}</div>{*\common\helpers\Categories::get_category_tree(0, '', $app->controller->view->filters->categories_id)*}
                </div>
            </form>
      <div class="widget-content">     
        <table class="table table-striped table-bordered table-hover table-responsive table-checkable table-selectable js-table-sortable datatable " checkable_list="0,1,2" data_ajax="{$app->urlManager->createUrl('products-global-sort/list')}">
          <thead>
            <tr>
              {foreach $app->controller->view->MainTable as $tableItem}
                <th{if $tableItem['not_important'] == 1} class="hidden-xs"{/if}>{$tableItem['title']}</th>
                {/foreach}
            </tr>
          </thead>
        </table>            

      </div>

    </div>
  </div>

<script type="text/javascript">
  $(document).ready(function(){
      $( ".js-table-sortable.datatable tbody" ).sortable({
        axis: 'y',
        start: function(event, ui) {
            var start_pos = ui.item.index();
            ui.item.data('start_pos', start_pos);
        },
        update: function( event, ui ) {
            var start_pos = ui.item.data('start_pos');
            var end_pos = ui.item.index();
            if (start_pos != end_pos) {
              var elements = $(this).children();
              var $dropped = $(ui.item);
              if (start_pos > end_pos) {
                //move down
                var $to = $(elements[end_pos+1]);
                var dir='u';
              } else {
                //move up - change with el. below
                var $to = $(elements[end_pos-1]);
                var dir='d';
              }
              var post_data = { dir:dir, id:$dropped.find('.cell_identify').val(), so:$dropped.find('.gso').val(), tid:$to.find('.cell_identify').val(), tso:$to.find('.gso').val()  };
              $.post("{Yii::$app->urlManager->createUrl(['products-global-sort/sort-order', 'platform_id' => $selected_platform_id])}", post_data, function(data, status){
                if (status == "success") {
                } else {
                    alert("Request error.");
                }
              },"html");
            }
        },
        handle: ".handle"
      }).disableSelection();

  });
  function copyGSO(from, to) {
      bootbox.confirm( "{$smarty.const.TEXT_CONFIRM_COPY_GSO|escape:'javascript'}", function(result){
        console.log(result);
        if (result){
            $.post("{Yii::$app->urlManager->createUrl('products-global-sort/copy')}", 'from_id=' + from + '&to_id=' + to, function(data) {
              if ( data && data.status && data.status=='OK' ) {
                window.location.href='{Yii::$app->urlManager->createUrl(['products-global-sort/index', 'a' => 'a'])}&platform_id=' + to;
              } else {
                  $('#exchangeExportResult').html('Request error');
                  //alert("Request error.");
              }
            },'json');
            bootbox.dialog({
                message: '<div id="exchangeExportResult">Copying...</div>',
                title: "Copying",
                buttons: {
                    done:{
                        label: "{$smarty.const.TEXT_BTN_OK}",
                        className: "btn-cancel"
                    }
                }
            });
        }
    } );

    return false;

  }
    function resetStatement() {
        var table = $('.table').DataTable();
        table.draw(false);
      //  $(window).scrollTop(0);
        return false;
    }

    function applyFilter(){
        resetStatement();
        return false;
    }

</script>

<!-- /Page Content -->
</div>
