
<!--=== Page Header ===-->
<div class="page-header">
  <div class="page-title">
    <h3>{$app->controller->view->headingTitle}</h3>
  </div>
  <input type="hidden" id="row_id" value="" />
</div>
<style>
.trunk{
    padding: 6px 5px 10px 10px;
    margin: 9px 0 5px 1px;
    float:right;
    }
</style>
<!-- /Page Header -->
  <!--=== Page Content ===-->
{if $isMultiPlatforms}
    <div class="tabbable tabbable-custom" style="margin-bottom: 0;">
        <ul class="nav nav-tabs">
            {foreach $platforms as $platform}
              <li class="platform-tab {if $platform['id']==$first_platform_id} active {/if}" data-platform_id="{$platform['id']}"><a onclick="loadModules('seo_redirects/list?platform_id={$platform['id']}')" data-toggle="tab"><span>{$platform['text']}</span></a></li>
            {/foreach}
        </ul>
    </div>
{/if}  
<div class="order-wrap">
<div class="row order-box-list">
    <div class="col-md-12">
            <div class="alert fade in" style="display:none;">
                  <i data-dismiss="alert" class="icon-remove close"></i>
                  <span id="message_plce"></span>
              </div>   	        
            <div class="widget-content" id="reviews_list_data">
                <table class="table table-striped table-bordered table-hover table-responsive table-checkable datatable dataTable js-seo-list"
                       checkable_list="0,1,2,3" data_ajax="seo_redirects/list?platform_id={$first_platform_id}" >
                    <thead>
                    <tr>
                        {foreach $app->controller->view->RedirectsTable as $tableItem}
                            <th{if $tableItem['not_important'] == 1} class="hidden-xs"{/if}>{$tableItem['title']}</th>
                        {/foreach}
                    </tr>
                    </thead>
                </table>
            </div>
    </div>
</div>
<script type="text/javascript">

    function onDblClickEvent (obj, table) {
        var dtable = $(table).DataTable();
        var id = dtable.row('.selected').index();
        $("#row_id").val(id);

        var event_id = $(obj).find('input.cell_identify').val();
        edit(  event_id );
    }

    function resetStatement() {
        var table = $('.table').DataTable();
        table.draw(false);
        return false;
    }
    
    function loadModules(url){
        var table = $('.table').DataTable();
         
        table.ajax.url( url ).load();
    }
    
    function redirectSave(){
        $.post("seo_redirects/submit", 
            $('form[name=redirect]').serialize(),
        function (data, status) {
            if (status == "success") {
                $('.alert #message_plce').html(data.message);
                $('.alert').addClass(data.messageType).show();
                resetStatement();
            } else {
                alert("Request error.");
            }
        }, "json");
        return false;
    }
    
    function preEditItem( item_id ) {
        $.post("seo_redirects/itempreedit", {
            'item_id': item_id
        }, function (data, status) {
            if (status == "success") {
                $('#_management_data .scroll_col').html(data);
                $("#_management").show();
               // switchOnCollapse('reviews_management_collapse');
            } else {
                alert("Request error.");
            }
        }, "html");
        return false;
    }
    
    function onClickEvent(obj, table) {

        var dtable = $(table).DataTable();
        var id = dtable.row('.selected').index();
        $("#row_id").val(id);
        
        var event_id = $(obj).find('input.cell_identify').val();

        preEditItem(  event_id );
    }

    function onUnclickEvent(obj, table) {

        var event_id = $(obj).find('input.cell_identify').val();
    }
    
    function edit(id){
       $.get("seo_redirects/edit", {
            'item_id': id,
            {if $isMultiPlatforms}
            'platform_id': $('.platform-tab.active').attr('data-platform_id'),
            {else}
            'platform_id': {$default_platform_id},
            {/if}
        }, function (data, status) {
            if (status == "success") {
                $('#_management_data .scroll_col').html(data);
                $("#_management").show();
            } else {
                alert("Request error.");
            }
        }, "html");
        return false;
    }
    
    function ajax_download(url, data) {
        var $iframe,
            iframe_doc,
            iframe_html;

        if (($iframe = $('#download_iframe')).length === 0) {
            $iframe = $("<iframe id='download_iframe'" +
                        " style='display: none' src='about:blank'></iframe>"
                       ).appendTo("body");
        }

        iframe_doc = $iframe[0].contentWindow || $iframe[0].contentDocument;
        if (iframe_doc.document) {
            iframe_doc = iframe_doc.document;
        }

        iframe_html = "<html><head></head><body><form method='POST' action='" +
                      url +"'>"

        Object.keys(data).forEach(function(key){
            iframe_html += "<input type='hidden' name='"+key+"' value='"+data[key]+"'>";

        });

            iframe_html +="</form></body></html>";

        iframe_doc.open();
        iframe_doc.write(iframe_html);
        $(iframe_doc).find('form').submit();
    }

    function exportRedirects(){

       ajax_download("easypopulate/process-export?directory_id=1", {
            {if $isMultiPlatforms}
            'filter[platform_id]': $('.platform-tab.active').attr('data-platform_id'),
            {else}
            'filter[platform_id]': {$default_platform_id},
            {/if}
              'filter[search]': $('.dataTables_filter input').val(),
              'format': 'CSV',
               '_csrf': $('meta[name=csrf-token]').attr('content'),
              'directory_id': '1',
              'export_provider': 'seo\\redirects',
        });
    }

    function checkRedirect(id){
      $.post("seo_redirects/validate",
          {
          'item_id' : id ,
          },
          function(data, status){
              if (status == "success"){
                  resetStatement();
              }
          },"html");
      return false;
    }

    function validateAll(){
        bootbox.dialog({
          message: "{$smarty.const.TEXT_BATCH_SIZE|escape:'javascript'}:<input id=\"seo_redirects_validate_length\" value=\"100\">{$smarty.const.TEXT_COULD_BE_TOO_LONG|escape:'javascript'}<br><input type=\"checkbox\" id=\"seo_redirects_autoupdate\" checked > {$smarty.const.TEXT_UPDATE_REDIRECTED|escape:'javascript'}",
          
          title: "{$smarty.const.IMAGE_VALIDATE|escape:'javascript'}",
          buttons: {
              success: {
                  label: "{$smarty.const.TEXT_BTN_YES}",
                  className: "btn-ok",
                  callback: function(){
                    $('.alert #message_plce').html("{$smarty.const.TEXT_VALIDATION_STARTED}");
                    $('.alert').addClass('alert-info').show();
                    $.post("seo_redirects/validate",
                        {
                        'update30x' : $('#seo_redirects_autoupdate:checked').length ,
                        'limit' : $('#seo_redirects_validate_length').val() ,
                        {if $isMultiPlatforms}
                        'platform_id': $('.platform-tab.active').attr('data-platform_id'),
                        {else}
                        'platform_id': {$default_platform_id},
                        {/if}
                        },
                        function(data, status){
                            if (status == "success"){
                                $('.alert #message_plce').html(data.message);
                                $('.alert').addClass(data.messageType).show();
                                resetStatement();
                            }
                        },"json");
                  }
              },
              cancel: {
                  label: "{$smarty.const.TEXT_BTN_NO}",
                  className: "btn-cancel",
                  callback: function () {
                      //console.log("Primary button");
                  }
              }
          }
      });




      return false;
    }
    
    function trunk(){
        bootbox.dialog({
                message: "{$smarty.const.TEXT_TRUNCATE_PLATFORM_REDIRECTS|escape:'javascript'}",
                title: "{$smarty.const.TEXT_TRUNCATE_PLATFORM_REDIRECTS|escape:'javascript'}",
                buttons: {
                    success: {
                        label: "{$smarty.const.TEXT_BTN_YES}",
                        className: "btn-delete",
                        callback: function(){
                            $.post("seo_redirects/trunk",
                                {
                                {if $isMultiPlatforms}
                                'platform_id': $('.platform-tab.active').attr('data-platform_id'),
                                {else}
                                'platform_id': {$default_platform_id},
                                {/if}
                                },
                                function(data, status){
                                    if (status == "success"){                
                                        resetStatement();
                                    }
                                },"html");
                        }
                    },
                    cancel: {
                        label: "{$smarty.const.TEXT_BTN_NO}",
                        className: "btn-cancel",
                        callback: function () {
                            //console.log("Primary button");
                        }
                    }
                }
            });

    return false;
    }

function deleteRedirect(id){

            bootbox.dialog({
                message: "{$smarty.const.TEXT_REDIRECT_REMOVE_CONFIRM}",
                title: "{$smarty.const.TEXT_REDIRECT_DLEETE}",
                buttons: {
                    success: {
                        label: "{$smarty.const.TEXT_BTN_YES}",
                        className: "btn-delete",
                        callback: function(){
                            $.post("seo_redirects/delete",
                                {
                                'item_id' : id , 
                                },
                                function(data, status){
                                    if (status == "success"){                
                                        resetStatement();
                                    }
                                },"html");
                        }
                    },
                    cancel: {
                        label: "{$smarty.const.TEXT_BTN_NO}",
                        className: "btn-cancel",
                        callback: function () {
                            //console.log("Primary button");
                        }
                    }
                }
            });

    return false;
}

</script>
<div class="row right_column" id="_management">
        <div class="widget box">
            <div class="widget-content fields_style" id="_management_data">
                <div class="scroll_col"></div>
            </div>
        </div>
</div>
</div>
<!--=== reviews management ===-->