{use class="yii\helpers\Html"}
{use class="common\helpers\Acl"}
<!--=== Page Header ===-->
<div class="page-header">
    <div class="page-title">
        <h3>{$app->controller->view->headingTitle}</h3>
    </div>
</div>
<!-- /Page Header -->
<!--=== Page Content ===-->
<div class="widget-content">
    <form action="{$form_action}" method="post" id="frmConfig">
    <div class="tabbable tabbable-custom">
        {if $isMultiPlatform}
            <ul class="nav nav-tabs -tab-light-gray">
                {foreach $platforms as $platform}
                    <li {if $selected_platform_id==$platform['id']} class="active"{/if}><a class="js_link_platform_select" href="#pl_{$platform['id']}" data-toggle="tab" data-platform_id="{$platform['id']}" {if $platform['id']==$selected_platform_id} onclick="return false" {/if}><span>{$platform['text']}</span></a></li>
                {/foreach}
            </ul>
        {/if}
        <div class="tab-content {if $isMultiPlatform}tab-content1{/if}">
            {foreach $platforms as $platform}
                <div id="pl_{$platform['id']}" class="tab-pane {if $selected_platform_id==$platform['id']}active{/if}">
                    
                    {if $TrustpilotClass = Acl::checkExtension('Trustpilot', 'viewPlatformConfigEdit')}
                        {$TrustpilotClass::viewPlatformConfigEdit($platform['id'])}
                    {/if}
                    <br>
                    <div class="widget box">
                        <div class="widget-header">
                            <h4><span>{$smarty.const.HEADING_TRUSTPILOT_EXPORT}</span></h4>
                        </div>
                        <div class="widget-content">
                            <div class="row">
                                <div class="col-md-6">
                                    
                                    <div class="wl-td">
                                        <label>{$smarty.const.TEXT_STATUS}</label>
                                        {Html::dropDownList('export['|cat:$platform['id']|cat:'][order_status]', $filters['order_status']['value'][$platform['id']], $filters['order_status']['items'], ['class'=>'form-control'])}
                                    </div>

                                    <div class="tl_filters_title tl_filters_title_border">{$smarty.const.TEXT_ORDER_PLACED}</div>
                                    <div class="wl-td w-tdc">
                                        <label class="radio_label"><input type="radio" class="js-disable-date-ctrl" name="export[{$platform['id']}][date_type_range]" value="presel" {if $filters['date_type_range']['value'][$platform['id']]=='presel'}checked{/if} /> {$smarty.const.TEXT_PRE_SELECTED}</label>
                                        {Html::dropDownList('export['|cat:$platform['id']|cat:'][interval]', $filters['interval']['value'][$platform['id']], $filters['interval']['items'], ['class'=>'form-control'])}
                                    </div>
                                    <div class="wl-td wl-td-from w-tdc">
                                        <label class="radio_label">
                                            <input type="radio" class="js-disable-date-ctrl" name="export[{$platform['id']}][date_type_range]" value="exact" {if $filters['date_type_range']['value'][$platform['id']]=='exact'}checked{/if} /> {$smarty.const.TEXT_EXACT_DATES}</label>
                                            <table cellpadding="0" cellspacing="0"><tr><td><span>{$smarty.const.TEXT_FROM}</span><input type="text" value="{$filters['date_from']['value'][$platform['id']]}" autocomplete="off" name="export[{$platform['id']}][date_from]" class="datepicker form-control form-control-small" /></td><td><span class="sp_marg">{$smarty.const.TEXT_TO}</span><input type="text" value="{$filters['date_from']['value'][$platform['id']]}" autocomplete="off" name="export[{$platform['id']}][date_to]" class="datepicker form-control form-control-small"/></td></tr></table>
                                    </div>
                                    <div class="wl-td wl-td-from w-tdc">
                                        <label class="radio_label">{$smarty.const.TEXT_AMOUNT_FILTER}</label>
                                        <!--input type="hidden" name="by_totals" value="ot_subtotal" -->{Html::dropDownList('export['|cat:$platform['id']|cat:'][by_totals]', $filters['by_totals']['value'][$platform['id']], $filters['by_totals']['items'], ['class'=>'form-control'])}
                                            <table cellpadding="0" cellspacing="0"><tr><td><span>{$smarty.const.TEXT_FROM}</span><input type="text" value="{$filters['by_totals_val_from']['value'][$platform['id']]}" autocomplete="off" name="export[{$platform['id']}][by_totals_val_from]" class="form-control form-control-small" /></td><td><span class="sp_marg">{$smarty.const.TEXT_TO}</span><input type="text" value="{$filters['by_totals_val_to']['value'][$platform['id']]}" autocomplete="off" name="export[{$platform['id']}][by_totals_val_to]" class="form-control form-control-small"/></td></tr></table>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div>
                                        <input type="checkbox" name="export[{$platform['id']}][re_export]" value="1"> {$smarty.const.ENTRY_TRUSTPILOT_RE_EXPORT}
                                    </div>
                                    <div>
                                        <input type="checkbox" name="export[{$platform['id']}][add_totals]" value="1"> {$smarty.const.ENTRY_TRUSTPILOT_ADD_TOTALS}
                                    </div>
                                    <div>
                                        <input type="checkbox" name="export[{$platform['id']}][all_orders]" value="1"> {$smarty.const.ENTRY_TRUSTPILOT_INCLUDE_MULTIPLE_ORDERS}
                                    </div>
                                    <button type="button" class="btn btn-2 js-export_orders" data-platform_id="{$platform['id']}">{$smarty.const.TEXT_EXPORT}</button>
                                </div>
                            </div>

                            
                        </div>
                    </div>
                </div>
            {/foreach}
        </div>
    </div>
    <div class="btn-bar">
        <div class="btn-right"><button class="btn btn-confirm">{$smarty.const.IMAGE_SAVE}</button></div>
    </div>
    </form>
</div>
<div id="bExport" style="display: none;"></div>
<!--=== Page Content ===-->
<script type="text/javascript">
    $(document).ready(function(){
        $( ".datepicker" ).datepicker({
            changeMonth: true,
            changeYear: true,
            showOtherMonths:true,
            autoSize: false,
            dateFormat: '{$smarty.const.DATE_FORMAT_DATEPICKER}',
            onSelect: function() { 
                if ($(this).val().length > 0) { 
                    $(this).siblings('span').addClass('active_options');
                }else{ 
                    $(this).siblings('span').removeClass('active_options');
                }
            }
        });
        $('.js-export_orders').on('click',function(){
            var target_platform_id = $(this).attr('data-platform_id');
            $('#bExport').html('<form id="frmExport" target="_blank" action="{$urlExport}&platform_id='+target_platform_id+'" method="post"></form>');
            var data = $('#frmConfig [name^="export['+target_platform_id+']"]').serializeArray();
            for( var i=0; i<data.length; i++ ) {
                $('#frmExport').append('<input type="hidden" name="'+data[i].name+'" value="'+data[i].value+'">');
            }
            $('#frmExport').trigger('submit');
            return false;
        });
        
        var check_date_ctrl = function(event)
        {
            
        }
        $('.js-disable-date-ctrl').on('click',check_date_ctrl);
    });
</script>