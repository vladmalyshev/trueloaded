<div class="row" id="download_files" style="_display: none;">
    <div class="col-md-12">
        <div class="widget box">
            <div class="widget-header">
                <h4><i class="icon-reorder"></i><span id="cache_control_title">{$smarty.const.TEXT_FLUSH_CACHE}</span>
                </h4>

                <div class="toolbar no-padding">
                    <div class="btn-group">
                        <span id="cache_control_collapse" class="btn btn-xs widget-collapse"><i
                                    class="icon-angle-down"></i></span>
                    </div>
                </div>
            </div>
            <div id="cache_control_data">
                <div class="popup-box-wrap pop-mess" style="display: none;">
					<div class="around-pop-up"></div>
					<div class="popup-box">
						<div class="pop-up-close pop-up-close-alert"></div>
						<div class="pop-up-content">
							<div class="popup-heading">{$smarty.const.TEXT_NOTIFIC}</div>
							<div class="popup-content popup-content-data">
								
							</div>  
						</div>     
						<div class="noti-btn noti-btn-ok">
							<div><span class="btn btn-primary">{$smarty.const.TEXT_BTN_OK}</span></div>
						</div>
					</div>  
					<script>
						$('body').scrollTop(0);
						$('.pop-mess .pop-up-close-alert, .noti-btn .btn').click(function(){
							$(this).parents('.pop-mess').hide();
						});
					</script>
				</div>
            </div>
            <div class="widget-content fields_style">
                <form name="cache_control_form" action="" method="post" onsubmit="return flushCache()">

                    <label class="checkbox">
                        <input name="system" type="checkbox" class="uniform" value="1"> {$smarty.const.TEXT_SYSTEM}
                    </label>
                    <label class="checkbox">
                            <input name="smarty" type="checkbox" class="uniform" value="1"> {$smarty.const.TEXT_SMARTY}
                    </label>
                    <label class="checkbox">
                            <input name="debug"  type="checkbox" class="uniform" value="1"> {$smarty.const.TEXT_DEBUG}
                    </label>
                    <label class="checkbox">
                            <input name="logs"  type="checkbox" class="uniform" value="1"> {$smarty.const.TEXT_LOGS}
                    </label>
                    <label class="checkbox">
                        <input name="image_cache"  type="checkbox" class="uniform" value="1"> {$smarty.const.TEXT_IMAGE_CACHE}
                    </label>

                    <input type="submit" class="btn btn-primary" value="{$smarty.const.TEXT_FLUSH}" >
                    
                    
                </form>
            </div>
        </div>
    </div>
</div>
                
<script type="text/javascript">
function flushCache() {
    $.post("{$app->urlManager->createUrl('cache_control/flush')}", $('form[name=cache_control_form]').serialize(), function(data, status){
        if (status == "success") {
			$('#cache_control_data .popup-content-data').empty();
            $('#cache_control_data .popup-content-data').append(data);
			$('#cache_control_data .popup-box-wrap').show();
        } else {
            alert("Request error.");
        }
    },"html");
    return false;
}
</script>    