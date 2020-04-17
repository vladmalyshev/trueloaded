{use class="yii\helpers\Html"}
<form id="saveModules" name="modules" onSubmit="return updateModule('{$codeMod}');" enctype="multipart/form-data">
  <input type="hidden" name="platform_id" value="{$selected_platform_id}">
  <input type="hidden" name="module" value="{$codeMod}">
  <input type="hidden" name="set" value="{$set}">
<div class="btn-bar btn-bar-top after">
	<div class="btn-left"><a href="javascript:void(0)" onclick="return backStatement();" class="btn btn-back">{$smarty.const.IMAGE_BACK}</a></div>
	<div class="btn-right"><a href="javascript:void(0)" onClick="return changeModule('{$codeMod}', 'remove')" class="btn btn-delete">{$smarty.const.IMAGE_DELETE}</a></div>
</div>
<div class="widget box box-no-shadow">
        <div class="widget-content edit-modules">
            <div class="row">
                <div class="col-md-6">
                    <div class="wg_title">{$smarty.const.TEXT_SETTINGS}</div>
                    {$mainKey}
                </div>
                <div class="col-md-6">
                    <div class="wg_title">{$smarty.const.TEXT_RESTRICTIONS}</div>
                    {$restriction}
                </div>
            </div>
        </div>
</div>
{if {strlen($app->controller->view->extra_params)} > 0}
<div class="widget box box-no-shadow">
	<div class="widget-header"><h4>{$smarty.const.IMAGE_DETAILS}</h4></div>
        <div class="widget-content edit-modules">{$app->controller->view->extra_params}</div>
</div>
{/if}
<div class="btn-bar edit-btn-bar">
    <div class="btn-left"><a href="javascript:void(0)" class="btn btn-cancel-foot" onclick="return backStatement()">{$smarty.const.IMAGE_CANCEL}</a></div>
    <div class="btn-right"><button class="btn btn-primary">{$smarty.const.IMAGE_UPDATE}</button></div>
</div>

</form>
<script type="text/javascript">
function backStatement() { 
    window.history.back();      
    return false;
}
function changeModule( item_id, action) {
  var process_changes = function(){
    $.post("modules/change", {
      'set': '{$set}',
      'platform_id': '{$selected_platform_id}',
      'module': item_id,
      'action': action
    }, function (response, status) {
      if (status == "success") {
        global = item_id;
        if(action == 'remove'){
          backStatement();
        }
      } else {
        alert("Request error.");
      }
    }, "json");
  }
  if ( action=='remove' ) {
    bootbox.dialog({
      message: "{$smarty.const.TEXT_MODULES_REMOVE_CONFIRM}",
      title: "{$smarty.const.TEXT_MODULES_REMOVE_CONFIRM_HEAD}",
      buttons: {
        success: {
          label: "{$smarty.const.JS_BUTTON_YES}",
          className: "btn-delete",
          callback: process_changes
        },
        main: {
          label: "{$smarty.const.JS_BUTTON_NO}",
          className: "btn-cancel",
          callback: function () {
            //console.log("Primary button");
          }
        }
      }
    });
  }else{
    process_changes();
  }
  return false;
}

function Resp(item_id){
    global = item_id;
    $('body').append('<div class="popup-box-wrap pop-module pop-mess"><div class="around-pop-up"></div><div class="popup-box"><div class="pop-up-close pop-up-close-alert"></div><div class="pop-up-content"><div class="popup-heading">{$smarty.const.TEXT_NOTIFIC}</div><div class="popup-content pop-mess-cont pop-mess-cont-success">{$smarty.const.TEXT_MODULES_SUCCESS}</div></div><div class="noti-btn"><div></div><div><span class="btn btn-primary">{$smarty.const.TEXT_BTN_OK}</span></div></div></div></div>');
        $(window).scrollTop(10);
        $('.pop-mess .pop-up-close-alert, .noti-btn .btn').click(function(){
            $(this).parents('.pop-mess').remove();
        });            

}
var files = [];
function updateModule(item_id) {
    var data = $('form[name=modules]').serializeArray();
    var fD = new FormData();
    var hasFile = false;
    if (data.length){
        $.each(data, function(i, e){
            fD.append(e.name, e.value);
        })
        
        if (Array.isArray(files)){
            $.each(Object.keys(files), function (i, key ){ 
                fD.append(key, files[key], files[key].name);
            });
            hasFile = true;
        }
    }
    
    
    if (!hasFile){
        $.post("modules/save?set={$set}", fD, function (data, status) {
            if (status == "success") {
                Resp(item_id);
            } else {
                alert("Request error.");
            }
            
        }, "html");
    } else {
        xhr = new XMLHttpRequest();
        
        xhr.open( 'POST', "modules/save?set={$set}", true );
        xhr.onreadystatechange = function ( response ) {
            if (this.readyState == 4 && this.status == 200) {
                Resp(item_id);
            }
        };
        xhr.send( fD );
    }
    return false;
}
</script>