{use class="yii\helpers\Url"}
<!--=== Page Header ===-->
<div class="page-header">
    <div class="page-title">
        <h3>{$app->controller->view->headingTitle}</h3>
    </div>
    <input type="hidden" name="group_id" value="{$app->controller->view->group_id}" />
</div>
<!-- /Page Header -->

{if $isMultiPlatforms}
    <div class="tabbable tabbable-custom" style="margin-bottom: 0;">
        <ul class="nav nav-tabs">
            {foreach $platforms as $platform}
              <li class="platform-tab {if $platform['id']== $first_platform_id} active {/if}" data-platform_id="{$platform['id']}"><a onclick="loadModules('testimonials/list?platform_id={$platform['id']}')" data-toggle="tab"><span>{$platform['text']}</span></a></li>
            {/foreach}
        </ul>
    </div>
{/if}
<!--===Group Params table===-->
<div class="order-wrap">
<form id="filterForm" name="filterForm" onsubmit="return applyFilter();">
    <input type="hidden" name="row" id="row_id" value="{$app->controller->view->filters->row}" />
</form>
    <div class="row order-box-list" id="modules_list">
        <div class="col-md-12">
              {if {$messages|@count} > 0}
			   {foreach $messages as $type => $message}
              <div class="alert alert-{$type} fade in">
                  <i data-dismiss="alert" class="icon-remove close"></i>
                  <span id="message_plce">{$message}</span>
              </div>			   
			   {/foreach}
			  {/if}        
                <div class="widget-content" id="modules_list_data">
                    <input type="hidden" name="row" id="row_id" value="0" />
                    <table class="table table-striped table-selectable table-checkable table-hover table-responsive table-bordered datatable" checkable_list="0,1" data_ajax="testimonials/list?platform_id={$first_platform_id}">
                        <thead>
                        <tr>
                            {foreach $app->controller->view->tabList as $tableItem}
                                <th{if $tableItem['not_important'] == 2} class="checkbox-column"{/if}{if $tableItem['not_important'] == 3} class="status-column"{/if}{if $tableItem['not_important'] == 1} class="hidden-xs"{/if}>{$tableItem['title']}</th>
                            {/foreach}
                        </tr>
                        </thead>
                    </table>
                </div>
        </div>
    </div>
    <!--===Actions ===-->
    <div class="row right_column" id="socials_management" style="display: none;">
            <div class="widget box">
                <div class="widget-content fields_style" id="socials_management_data">
                    <div class="scroll_col"></div>
                </div>
            </div>
    </div>
    <!--===Actions ===-->
</div>

<script type="text/javascript">
function setFilterState() {
    orig = $('#filterForm').serialize();
    var url = window.location.origin + window.location.pathname + '?' + orig.replace(/[^&]+=\.?(?:&|$)/g, '')
    window.history.replaceState({ }, '', url);
}

function itemPreview(id){
          $.get("testimonials/preview", { 'tID' : id , 'row' : $('#row_id').val() }, function(data, status){
              if (status == "success") {
                  $('#socials_management_data .scroll_col').html(data);
                  //$("#item_management").show();
                  //switchOffCollapse('status_list_collapse');
              } else {
                  alert("Request error.");
              }
          },"html");
          return false;    
}


function changeStatus(testimonials_id, status){
    $.post('testimonials/change',
			{
				'tID' :testimonials_id,
				'status' : status
			},
			function(data, status){
               if (status == "success") {
                    resetStatement();
                    alertMessage(data);
                } else {
                    alert("Request error.");
                }
			},
			'html'
		);	
    return;
}


function onClickEvent(obj, table) {

    var param_id = $(obj).find('input.cell_identify').val();
    $('#row_id').val(table.find(obj).index());  
    setFilterState();
    $(".check_on_off").bootstrapSwitch(
      {
		onSwitchChange: function () {
            param_id = $(this).parents('tr').find('input.cell_identify').val();
            $('#row_id').val(table.find($(this).parents('tr')).index());
			changeStatus(param_id, this.checked);
			return true;
		},
		onText: "{$smarty.const.SW_ON}",
        offText: "{$smarty.const.SW_OFF}",
        handleWidth: '20px',
        labelWidth: '24px'
      }
    );    
    
    itemPreview(param_id);

}

function onUnclickEvent(obj, table) {
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



function testimonialDelete(id){

            bootbox.dialog({
                message: "{$smarty.const.TEXT_TESTIMONIAL_REMOVE_CONFIRM}",
                title: "{$smarty.const.TEXT_TESTIMONIAL_REMOVE_CONFIRM_HEAD}",
                buttons: {
                    success: {
                        label: "{$smarty.const.TEXT_BTN_YES}",
                        className: "btn-delete",
                        callback: function(){
                            $.post("testimonials/delete",
                                {
                                'tID' : id , 
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