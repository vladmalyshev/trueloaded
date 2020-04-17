{use class="yii\helpers\Html"}
<div>
    <form name="google_base_form" action="" method="post" onsubmit="return saveGoogleBaseData()">
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

                    <div class="widget box">
                        <div class="widget-content">
                            <div class="w-line-row w-line-row-1">
                                <div class="wl-td">
                                    <label>{field_label const="TEXT_FEED_URL"}</label><div style="display:inline-block; margin-top:4px">{$google_config[$platform['id']]['feed_url']}</div>
                                </div>
                            </div>
                            <div class="w-line-row w-line-row-1">
                                <div class="wl-td">
                                    <label>{field_label const="TEXT_PRODUCTS_PLATFORM"}</label>{Html::dropDownList('google_config['|cat:$platform['id']|cat:'][products_platform]', $google_config[$platform['id']]['products_platform'], $products_platform_items, ['class'=>'form-control'] )}
                                </div>
                            </div>
                            <div class="w-line-row w-line-row-1">
                                <div class="wl-td">
                                    <label>{field_label const="TEXT_PLATFORM_CODE"}</label>
                                    {Html::textInput('google_config['|cat:$platform['id']|cat:'][platform_code]',$google_config[$platform['id']]['platform_code'],['class'=>'form-control'])}
                                </div>
                            </div>
                            <div class="w-line-row w-line-row-1">
                                <div class="wl-td">
                                    <label>{field_label const="TEXT_GOOGLE_SHOP_PLATFORM"}</label>{Html::dropDownList('google_config['|cat:$platform['id']|cat:'][google_shop_platform]', $google_config[$platform['id']]['google_shop_platform'], $products_platform_items, ['class'=>'form-control'] )}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="widget box">
                        <div class="widget-header"><h4><i class="icon-reorder"></i><span>{$smarty.const.TEXT_SELECT_FEED_COLUMNS}</span></h4></div>

                        <div class="widget-content fields_style">
                            {foreach from=$fields item=field}
                                <label class="checkbox">
                                    <input name="google_config[{$platform['id']}][column_config][{$field}]" type="checkbox" class="uniform" value="1"{if $google_config[$platform['id']]['column_config'][$field]} checked{/if}> {$field}
                                </label>
                                {if $field=='gtin'}
                                    <div style="margin-left: 24px">
                                        <ul class="sortable">
                                            {foreach $google_config[$platform['id']]['gtin_config'] as $gtinItem}
                                            <li>
                                                <span class="handle"><i class="icon-hand-paper-o"></i></span>
                                                <div class="name">
                                                    <label>
                                                        <input name="google_config[{$platform['id']}][gtin_config][{{$gtinItem['value']}}]" type="checkbox" {if $gtinItem['checked']}checked="checked"{/if} value="{$gtinItem['value']}" class="uniform">
                                                        <input name="google_config[{$platform['id']}][gtin_config_sort][{{$gtinItem['value']}}]" type="hidden" value="{$gtinItem['value']}">
                                                        {$gtinItem['label']}
                                                    </label>
                                                </div>
                                            </li>
                                            {/foreach}
                                        </ul>
                                    </div>
                                {/if}
                            {/foreach}
                        </div>
                    </div>
                        
                </div>
            {/foreach}
        </div>
    </div>
    <div class="btn-bar">
        <div class="btn-right"><button class="btn btn-confirm">{$smarty.const.TEXT_UPDATE}</button></div>
    </div>
    </form>
</div>
<div id="google_base_data"></div>
                
<script type="text/javascript">
function saveGoogleBaseData() {
    $.post("{$app->urlManager->createUrl('google_base/save')}", $('form[name=google_base_form]').serialize(), function(data, status){
        if (status == "success") {
            $('#google_base_data').append(data);
        } else {
            alert("Request error.");
        }
    },"html");
    return false;
}
$(document).ready(function(){
    $( function() {
        $( ".sortable" ).sortable();
        $( ".sortable" ).disableSelection();
    } );
});
</script>    