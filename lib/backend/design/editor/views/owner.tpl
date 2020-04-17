<div class="widget box box-no-shadow">
        <div class="widget-header widget-header-platform"><h4><i class="icon-frontends"></i>Order Owner</h4></div>
        <div class="widget-content">
            <div class="w-line-row w-line-row-1 wbmbp1">
                <div>This order is busy by {$name}. Do you want to assign this order to your account?</div>
            </div>
        </div>
        <div class = "noti-btn" style="margin:0px;">
            <div class="btn-left"><a href="{$cancel}"  class="btn btn-default">{$smarty.const.TEXT_BTN_NO}</a></div>
            <div class="btn-right"><a href="javascript:void(0)"  class="btn btn-primary btn-confirm-owner">{$smarty.const.TEXT_BTN_YES}</a></div>
        </div>
        <script>
            $(document).ready(function () {
                $('.pop-up-close:last').hide();
                $('.btn-confirm-owner').click(function() {
                    $.post("{\yii\helpers\Url::to(['editor/owner', 'currentCurrent' => $currentCurrent])}", {
                        'action': 'confirm'
                    }, function (data, status) {
                        if (status == 'success') {
                            if (data.reload) {
                                window.location.reload();
                            }
                        }
                    }, 'json');
                });
            })
        </script>
    </div>