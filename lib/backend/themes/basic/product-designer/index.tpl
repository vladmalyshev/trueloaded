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
<div class="order-wrap">
  <!--=== Page Content ===-->
  <div class="row order-box-list">
    <div class="col-md-12">
      <div class="widget-content">     
        {if {$messages|@count} > 0}
          {foreach $messages as $message}
            <div class="alert fade in {$message['messageType']}">
              <i data-dismiss="alert" class="icon-remove close"></i>
              <span id="message_plce">{$message['message']}</span>
            </div>               
          {/foreach}
        {/if}
        
        <table class="table table-striped table-bordered table-hover table-responsive table-checkable datatable table-suppliers" checkable_list="0,1,2" data_ajax="{$app->urlManager->createUrl('product-designer/list')}">
          <thead>
            <tr>
               
            </tr>
          </thead>
        </table>            

      </div>

    </div>
  </div>

<!--===Actions ===-->
<div class="row right_column" id="suppliers_management">
  <div class="widget box">
    <div class="widget-content fields_style" id="suppliers_management_data">
      <div class="scroll_col"></div>
    </div>
  </div>
</div>
<!--===Actions ===-->
<!-- /Page Content -->
</div>
