{use class="yii\helpers\Html"}
{use class="common\helpers\Acl"}
{\backend\assets\BDPAsset::register($this)|void}
<!--=== Page Content ===-->
<div id="platforms_management_data">
<!--===Customers List ===-->
<form name="save_item_form" id="save_item_form" enctype="multipart/form-data" onSubmit="return saveItem();">
<div class="box-wrap">
    <div class="create-or-wrap after create-cus-wrap">
        <div class="cbox-left">
            <div class="widget box box-no-shadow">
                <div class="widget-header widget-header-personal"><h4>{$smarty.const.CATEGORY_GENERAL}</h4></div>
                <div class="widget-content">
                    <div class="w-line-row w-line-row-1">
                        <div style="display:inline-block;">
                            <label>{$smarty.const.ENTRY_STATUS}</label>
                            {Html::checkbox('status', $pInfo->status, ['value'=>'1', 'class' => 'js_check_status'])}
                        </div>
                        <div class="marketplace_switcher" style="display:inline-block;margin-left:10px;">
                            <label>{$smarty.const.ENTRY_IS_MARKETPLACE}</label>
                            {Html::checkbox('is_marketplace', $pInfo->is_marketplace, ['value'=>'1', 'class' => 'js_check_is_marketplace'])}
                        </div>
                        <div class="virtual_switcher" style="display:inline-block;margin-left:10px;">
                            <label>{$smarty.const.ENTRY_IS_VIRTUAL}</label>
                            {Html::checkbox('is_virtual', $pInfo->is_virtual, ['value'=>'1', 'class' => 'js_check_is_virtual'])}
                        </div>
                    </div>
                    <div class="w-line-row w-line-row-1">
                        <div class="wl-td">
                            <label>{$smarty.const.ENTRY_PLATFORM_OWNER}<span class="fieldRequired">*</span></label>{Html::input('text', 'platform_owner', $pInfo->platform_owner, ['class' => 'form-control', 'required' => true])}
                        </div>
                    </div>
                    <div class="w-line-row w-line-row-1">
                        <div class="wl-td">
                            <label>{$smarty.const.ENTRY_PLATFORM_NAME}<span class="fieldRequired">*</span></label>{Html::input('text', 'platform_name', $pInfo->platform_name, ['class' => 'form-control', 'required' => true])}
                        </div>
                    </div>
                    <div class="no_marketplace_or_virtual" {if $pInfo->is_marketplace == 1 || $pInfo->is_virtual == 1} style="display: none;"{/if}>
                        <div class="w-line-row w-line-row-1">
                            <div class="wl-td">
                                <label>{$smarty.const.ENTRY_PLATFORM_URL}<span class="fieldRequired">*</span></label>{Html::input('text', 'platform_url', $pInfo->platform_url, ['class' => 'form-control marketplace_no_check', 'required' => true])}
                            </div>
                        </div>
                        <div class="w-line-row w-line-row-1">
                            <div class="wl-td">
                                <label>{$smarty.const.ENTRY_SSL_ENABLED}</label>
                                {*Html::checkbox('ssl_enabled', !!$pInfo->ssl_enabled, ['value'=>'1', 'class' => 'js_check_ssl_enabled'])*}
                                {Html::radio('ssl_enabled', ($pInfo->ssl_enabled == 0), ['value'=> '0', 'class' => 'js_check_ssl_enabled'])} NoSSL
                                {Html::radio('ssl_enabled', ($pInfo->ssl_enabled == 1), ['value'=> '1', 'class' => 'js_check_ssl_enabled'])} SSL
                                {Html::radio('ssl_enabled', ($pInfo->ssl_enabled == 2), ['value'=> '2', 'class' => 'js_check_ssl_enabled'])} FullSSL
                            </div>
                        </div>
                        <div class="w-line-row w-line-row-1 js_check_ssl_enabled_true">
                            <div class="wl-td">
                                <label>{$smarty.const.ENTRY_PLATFORM_URL_SECURE}</label>{Html::input('text', 'platform_url_secure', $pInfo->platform_url_secure, ['class' => 'form-control', 'placeholder' => PLACEHOLDER_PLATFORM_URL_SECURE])}
                            </div>
                        </div>
                        <div class="w-line-row w-line-row-1">
                            <div class="wl-td">
                                <label>{$smarty.const.ENTRY_PLATFORM_PREFIX}</label>{Html::input('text', 'platform_prefix', $pInfo->platform_prefix, ['class' => 'form-control'])}
                            </div>
                        </div>
                        {if \common\helpers\Acl::checkExtension('BusinessToBusiness', 'frontendBlock')}
                          {\common\extensions\BusinessToBusiness\BusinessToBusiness::frontendBlock($pInfo)}
                        {else}
                        <div class="w-line-row w-line-row-1 dis_module">
                            <div class="wl-td">
                                <label>{$smarty.const.ENTRY_NEED_LOGIN}</label>
                                <input class="js_check_need_login" type="checkbox" disabled>
                            </div>
                        </div>
                        {/if}
                        
                        <div class="w-line-row w-line-row-1">
                            <div class="wl-td">
                                <label>{$smarty.const.ENTRY_PLATFORM_PLEASE_LOGIN}</label>
                                {Html::checkbox('platform_please_login', !!$pInfo->platform_please_login, ['value'=>'1', 'class' => 'js_check_need_login'])}
                            </div>
                        </div>
                        
                        <div class="w-line-row w-line-row-1">
                            <div class="wl-td">
                                <label>{$smarty.const.USE_SOCIAL_LOGIN}</label>
                                {Html::checkbox('use_social_login', !!$pInfo->use_social_login, ['value'=>'1', 'class' => 'js_check_use_social_login'])}
                            </div>
                        </div>
                        {if $have_more_then_one_platform}
                        <div class="w-line-row w-line-row-1 is_default">
                            <div class="wl-td">
                                <label>{$smarty.const.ENTRY_IS_DEFAULT_PLATFORM}</label>
                                {Html::checkbox('is_default', !!$pInfo->is_default, array_merge(['value'=>'1', 'class' => 'js_check_default_platform'],$checkbox_default_platform_attr))}
                                {Html::hiddenInput('present_is_default','1')}
                            </div>
                        </div>
                        {else}
                            {Html::hiddenInput('is_default','1')}
                            {Html::hiddenInput('present_is_default','1')}
                        {/if}
                    </div>
                    
                    <div class="w-line-row w-line-row-1 default_shop_selector">
                        <div class="wl-td">
                            <label>{$smarty.const.ENTRY_IS_DEFAULT_PLATFORM}</label>
                        {Html::dropDownList('default_platform_id', $pInfo->default_platform_id, $sattelits, ['class' => 'form-control'])}
                        </div>
                    </div> 
                    
                    <div class="w-line-row w-line-row-1 yes_virtual" {if !$pInfo->is_virtual}style="display:none;"{/if}>
                        <div class="wl-td">
                            <label>{$smarty.const.TEXT_PHYSICAL} {$smarty.const.TABLE_HEADING_PLATFORM}</label>
                            {Html::dropDownList('sattelit_id', $pInfo->sattelite_platform_id, $sattelits, ['class' => 'form-control'])}
                            <span><a href="" class="virtual_link" target="_blank" ></a></span>
                        </div>
                    </div>
                    <div class="w-line-row w-line-row-1 yes_virtual" {if !$pInfo->is_virtual}style="display:none;"{/if}>
                        <div class="wl-td input-group">
                            <label>{$smarty.const.TEXT_PLATFORM_CODE}<span class="fieldRequired">*</span></label>
                            {Html::input('text', 'platform_code', $pInfo->platform_code, ['class' => 'form-control platform_code'])}
                            <div class="input-group-addon" id="lnCodeGenerate" title="{$smarty.const.TEXT_GENERATE|escape:'html'}"><i class="icon-refresh"></i></div>
                        </div>
                    </div>
                    <div class="w-line-row w-line-row-1 w-line-row-req">
                          <span style="color: #f2353c; margin: 22px 0 0; display: block;">{$smarty.const.ENTRY_REQUIRED_FIELDS}</span>
                    </div>
                </div>
            </div>
            <div class="widget box box-no-shadow">
                <div class="widget-header widget-header-contact"><h4>{$smarty.const.CATEGORY_CONTACT}</h4></div>
                <div class="widget-content">
                    <div class="w-line-row w-line-row-1 contact_switcher">
                        <div class="wl-td">
                            <label>{$smarty.const.ENTRY_SAME_AS_DEFAULT}</label>
                            {Html::checkbox('is_default_contact', $pInfo->is_default_contact, ['value'=>'1', 'class' => 'js_check_is_default_contact'])}
                        </div>
                    </div>
                        <div class="no_default_contact">
                    <div class="w-line-row w-line-row-2">
                        <div class="wl-td">
                            <label>{$smarty.const.ENTRY_EMAIL_ADDRESS}<span class="fieldRequired">*</span></label>{Html::input('text', 'platform_email_address', $pInfo->platform_email_address, ['class' => 'form-control default_contact_no_check', 'id'=>'txtPlatformEmail', 'required' => true])}
                        </div>
                        <div class="wl-td">
                            <label>{$smarty.const.ENTRY_EMAIL_FROM}<span class="fieldRequired">*</span></label>{Html::input('text', 'platform_email_from', $pInfo->platform_email_from, ['class' => 'form-control default_contact_no_check', 'required' => true])}
                        </div>
                    </div>
                    <div class="w-line-row w-line-row-1">
                        <div class="wl-td">
                            <label>{$smarty.const.ENTRY_EMAIL_EXTRA}</label>{Html::input('text', 'platform_email_extra', $pInfo->platform_email_extra, ['class' => 'form-control default_contact_no_check'])}
                        </div>
                    </div>
                    <div class="w-line-row w-line-row-1">
                        <div class="wl-td">
                            <label>{$smarty.const.TEXT_CONTACT_US_EMAIL}</label>{Html::input('text', 'contact_us_email', $pInfo->contact_us_email, ['class' => 'form-control default_contact_no_check js-override-platform-email'])}
                        </div>
                    </div>
                    <div class="w-line-row w-line-row-1">
                        <div class="wl-td">
                            <label>{$smarty.const.TEXT_LANDING_FORM_EMAIL}</label>{Html::input('text', 'landing_contact_email', $pInfo->landing_contact_email, ['class' => 'form-control default_contact_no_check js-override-platform-email'])}
                        </div>
                    </div>
                    <div class="w-line-row w-line-row-2">
                        <div class="wl-td">
                            <label>{$smarty.const.ENTRY_TELEPHONE_NUMBER}</label>{Html::input('text', 'platform_telephone', $pInfo->platform_telephone, ['class' => 'form-control default_contact_no_check'])}
                        </div>
                        <div class="wl-td">
                            <label>{$smarty.const.ENTRY_LANDLINE}</label>{Html::input('text', 'platform_landline', $pInfo->platform_landline, ['class' => 'form-control default_contact_no_check'])}
                        </div>
                    </div>
                        </div>
                    <div class="w-line-row w-line-row-1 w-line-row-req">
                          <span style="color: #f2353c; margin: 22px 0 0; display: block;">{$smarty.const.ENTRY_REQUIRED_FIELDS}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="cbox-right">
            <div class="widget box box-no-shadow" style="padding-bottom: 30px;">
                <div class="widget-header widget-header-address"><h4>{$smarty.const.CATEGORY_ADDRESS}</h4></div>
                <div class="widget-content">
                    
                    <div class="w-line-row w-line-row-1 contact_switcher">
                        <div class="wl-td">
                            <label>{$smarty.const.ENTRY_SAME_AS_DEFAULT}</label>
                            {Html::checkbox('is_default_address', $pInfo->is_default_address, ['value'=>'1', 'class' => 'js_check_is_default_address'])}
                        </div>
                    </div>
<div class="no_default_address">
                    <div class="w-line-row w-line-row-1">
                        <div class="wl-td">
                            <label>{$smarty.const.ENTRY_COMPANY}<span class="fieldRequired">*</span></label>{Html::input('text', 'entry_company[]', $addresses->entry_company, ['class' => 'form-control default_address_no_check'])}
                        </div>
                    </div>
                    <div class="w-line-row w-line-row-2">
                        <div class="wl-td">
                            <label>{$smarty.const.ENTRY_BUSINESS}<span class="fieldRequired">*</span></label>{Html::input('text', 'entry_company_vat[]', $addresses->entry_company_vat, ['class' => 'form-control default_address_no_check'])}
                        </div>
                        <div class="wl-td">
                            <label>{$smarty.const.ENTRY_BUSINESS_REG_NUMBER}</label>{Html::input('text', 'entry_company_reg_number[]', $addresses->entry_company_reg_number, ['class' => 'form-control default_address_no_check'])}
                        </div>
                    </div>
            
                    
                    <div class="w-line-row w-line-row-2">
                        <div class="wl-td">
                            <label>{$smarty.const.ENTRY_POST_CODE}<span class="fieldRequired">*</span></label>{Html::input('text', 'entry_postcode[]', $addresses->entry_postcode, ['class' => 'form-control default_address_no_check'])}
                        </div>
                        <div class="wl-td">
                            <label>{$smarty.const.ENTRY_STREET_ADDRESS}<span class="fieldRequired">*</span></label>{Html::input('text', 'entry_street_address[]', $addresses->entry_street_address, ['class' => 'form-control default_address_no_check'])}
                        </div>
                    </div>
                    <div class="w-line-row w-line-row-2">
                        <div class="wl-td">
                            <label>{$smarty.const.ENTRY_SUBURB}</label>{Html::input('text', 'entry_suburb[]', $addresses->entry_suburb, ['class' => 'form-control default_address_no_check'])}
                        </div>
                        <div class="wl-td">
                            <label>{$smarty.const.ENTRY_CITY}<span class="fieldRequired">*</span></label>{Html::input('text', 'entry_city[]', $addresses->entry_city, ['class' => 'form-control default_address_no_check'])}
                        </div>
                    </div>
                    <div class="w-line-row w-line-row-2">
                        <div class="wl-td">
                            <label>{field_label const="ENTRY_STATE" configuration="ACCOUNT_STATE"}</label>
                            <div class="f_td_state">
                                {Html::input('text', 'entry_state[]', $addresses->entry_state, ['class' => 'form-control default_address_no_check', 'id' => "selectState"])}
                            </div>
                        </div>
                        <div class="wl-td">
                            <label>{$smarty.const.ENTRY_COUNTRY}<span class="fieldRequired">*</span></label>{Html::dropDownList('entry_country_id[]', $addresses->entry_country_id, \common\helpers\Country::new_get_countries('', false), ['class' => 'form-control default_address_no_check', 'id' => "selectCountry", 'required' => true])}
                        </div>
                    </div>

                    <div class="w-line-row w-line-row-2">
                        <div class="wl-td">
                            <label>{$smarty.const.TEXT_INFO_LATITUDE}:</label>
                            <div class="f_td_state">
                                {Html::input('text', 'lat[]', $addresses->lat, ['class' => 'form-control'])}
                            </div>
                        </div>
                        <div class="wl-td">
                            <label>{$smarty.const.TEXT_INFO_LANGITUTE}:</label>
                            {Html::input('text', 'lng[]', $addresses->lng, ['class' => 'form-control'])}
                        </div>
                    </div>
</div>                      
                    <!--<div class="w-line-row w-line-row-1">
                        <div class="wl-td">
                            <input type="checkbox" /> <b>Make default address</b>
                        </div>
                    </div>!-->
                </div>
                <div class="w-line-row w-line-row-1 w-line-row-req w-line-row-abs">
                          <span style="color: #f2353c; margin: 22px 0 0; display: block;">{$smarty.const.ENTRY_REQUIRED_FIELDS}</span>
                    </div>
            </div>
            {Html::input('hidden', 'platforms_address_book_id[]', $addresses->platforms_address_book_id)}
            {include $pass|cat: '/themes/basic/platforms/edit/restriction.tpl'}
            {include $pass|cat: '/themes/basic/platforms/edit/organization.tpl'}
        </div>        
    </div>
        <div class="create-or-wrap after create-cus-wrap no_marketplace_or_virtual" {if $pInfo->is_marketplace == 1 || $pInfo->is_virtual == 1 }style="display: none;"{/if}>
        <div class="cbox-left">
    <div class="widget box box-no-shadow" style="min-height:183px;">
        <div class="widget-header widget-header-company"><h4>{$smarty.const.CATEGORY_OPEN_HOURS}</h4></div>
        <div class="widget-content">
            <div id="opening_hours_list">
            {foreach $open_hours as $open_key => $open_hour}
            <div class="w-line-row opening_hours">
                    <div>
                        <div class="hours_table">
                                        <label>{$smarty.const.ENTRY_DAYS}<span class="fieldRequired">*</span></label>
                                        <div class="col-md-10">
                                        {Html::dropDownList('open_days_'|cat:"$open_key", $open_hour->open_days, $days, ['class' => 'multiselect form-control', 'multiple' => 'multiple', 'data-role' => 'multiselect'])}
                                        </div>
                        </div>
                    </div>
                    <div class="time_int"><div class="time_int_1">
                        <label>{$smarty.const.ENTRY_TIME}<span class="fieldRequired">*</span></label>
                        <span class="time_title">{$smarty.const.ENTRY_TIME_FROM}</span>{Html::input('text', 'open_time_from[]', $open_hour->open_time_from, ['class' => 'pt-time form-control'])}</div>
                        <div class="time_int_2">
                            <span class="time_title">{$smarty.const.ENTRY_TIME_TO}</span>{Html::input('text', 'open_time_to[]', $open_hour->open_time_to, ['class' => 'pt-time form-control'])}</div>
                        <div class="time_int_3">
                            <a href="javascript:void(0)" onclick="return removeOpenHours(this);" class="btn">-</a>
                        </div>
                    </div>                              
                {Html::input('hidden', 'platforms_open_hours_id[]', $open_hour->platforms_open_hours_id)}
                {Html::input('hidden', 'platforms_open_hours_key[]', $open_key)}
            </div>
            {/foreach}
            </div>
            <div class="buttons_hours">
                <a href="javascript:void(0)" onclick="return addOpenHours();" class="btn">{$smarty.const.BUTTON_ADD_MORE}</a>
            </div>
        </div>
    </div>
        
    <div class="widget box box-no-shadow" style="min-height:183px;">
        <div class="widget-header widget-header-company"><h4>{$smarty.const.CATEGORY_CUT_OFF_TIMES}</h4></div>
        <div class="widget-content">
            <div id="cut_off_times_list">
            {foreach $cut_off_times as $cut_key => $cut_hour}
            <div class="w-line-row opening_hours">
                    <div>
                        <div class="hours_table">
                            <label>{$smarty.const.ENTRY_DAYS}<span class="fieldRequired">*</span></label>
                            <div class="col-md-10">
                            {Html::dropDownList('cut_off_times_days_'|cat:"$cut_key", $cut_hour->cut_off_times_days, $days, ['class' => 'multiselect form-control', 'multiple' => 'multiple', 'data-role' => 'multiselect'])}
                            </div>
                        </div>
                    </div>
                    <div class="time_int">
                        <div class="time_int_1">
                            <label>{$smarty.const.DAY_OF_WEEK}<span class="fieldRequired">*</span></label>
                            <span class="time_title">{$smarty.const.TODAY_DELIVERY}</span>{Html::input('text', 'cut_off_times_today[]', $cut_hour->cut_off_times_today, ['class' => 'pt-time form-control'])}
                        </div>
                        <div class="time_int_2">
                            <span class="time_title">{$smarty.const.NEXT_DAY_DELIVERY}</span>{Html::input('text', 'cut_off_times_next_day[]', $cut_hour->cut_off_times_next_day, ['class' => 'pt-time form-control'])}</div>
                        <div class="time_int_3">
                            <a href="javascript:void(0)" onclick="return removeCutOffTimes(this);" class="btn">-</a>
                        </div>
                    </div>                              
                {Html::input('hidden', 'platforms_cut_off_times_id[]', $cut_hour->platforms_cut_off_times_id)}
                {Html::input('hidden', 'platforms_cut_off_times_key[]', $cut_key)}
            </div>
            {/foreach}
            </div>
            <div class="buttons_hours">
                <a href="javascript:void(0)" onclick="return addCutOffTimes();" class="btn">{$smarty.const.BUTTON_ADD_MORE}</a>
            </div>
        </div>
        
    </div>
    
     <div class="widget box box-no-shadow" style="min-height:80px;">
        <div class="widget-header widget-header-company"><h4>{$smarty.const.BANK_HOLIDAYS}</h4></div>
        <div class="widget-content">
            <div class="buttons_holidays">
                <a href="{\yii\helpers\Url::to(['platforms/holidays', 'platform_id' => $pInfo->platform_id])}" class="btn popup">{$smarty.const.BANK_HOLIDAYS}</a>
            </div>
        </div>        
    </div>

    <div class="widget box box-no-shadow">
        <div class="widget-header widget-header-theme"><h4>{$smarty.const.GROUP_PLATFORM_URLS}</h4></div>
        <div class="widget-content">
            <table class="tl-grid js-platform-urls">
                <thead>
                <tr>
                    <th style="width:30px">{$smarty.const.HEAD_PLATFORM_URL_STATUS}</th>
                    <th>{$smarty.const.HEAD_PLATFORM_URL_TYPE}</th>
                    <th colspan="2">{$smarty.const.HEAD_PLATFORM_URL}</th>
                    <th style="width: 30px">&nbsp;</th>
                </tr>
                </thead>
                <tbody data-rows-count="{count($pInfo->platform_urls)}">
                {foreach from=$pInfo->platform_urls item=platform_url key=idx}
                    {assign var="row_index" value=$idx+1}
                <tr>
                    <td>{Html::checkbox('platform_urls['|cat:$row_index|cat:'][status]', $platform_url['status'], ['value'=>'1', 'class' => 'js-url_status'])}</td>
                    <td>{Html::dropDownList('platform_urls['|cat:$row_index|cat:'][url_type]', $platform_url['url_type'], $cdn_url_types, ['class' => 'form-control'])}</td>
                    <td>{Html::dropDownList('platform_urls['|cat:$row_index|cat:'][ssl_enabled]',$platform_url['ssl_enabled'],['0'=>'NoSSL','1'=>'SSL','2'=>'Full SSL'], ['class' => 'form-control'])}</td>
                    <td>
                        {Html::textInput('platform_urls['|cat:$row_index|cat:'][url]', $platform_url['url'], ['class' => 'form-control'])}
                        {Html::hiddenInput('platform_urls['|cat:$row_index|cat:'][platforms_url_id]', $platform_url['platforms_url_id'])}
                    </td>
                    <td><button type="button" class="btn js-remove-platform-url">-</button></td>
                </tr>
                {/foreach}
                </tbody>
                <tfoot style="display: none">
                <tr>
                    <td>{Html::checkbox('_unhide_platform_urls[%idx%][status]', 1, ['value'=>'1', 'class' => 'js-url_status_skel'])}</td>
                    <td>{Html::dropDownList('_unhide_platform_urls[%idx%][url_type]', 1, $cdn_url_types, ['value'=>'1', 'class' => 'form-control'])}</td>
                    <td>{Html::dropDownList('platform_urls[%idx%][ssl_enabled]',0,['0'=>'NoSSL','1'=>'SSL','2'=>'Full SSL'], ['class' => 'form-control'])}</td>
                    <td>
                        {Html::textInput('_unhide_platform_urls[%idx%][url]', '', ['class' => 'form-control'])}
                        {Html::hiddenInput('_unhide_platform_urls[%idx%][platforms_url_id]', '')}
                    </td>
                    <td><button type="button" class="btn js-remove-platform-url">-</button></td>
                </tr>
                </tfoot>
            </table>
            <input type="hidden" name="platform_urls_present" value="1">
            &nbsp;
            <div class="buttons_hours">
                <button type="button" class="btn js-add-platform-url">{$smarty.const.TEXT_ADD_MORE}</button>
            </div>
            <!--div class="w-line-row w-line-row-1">
                <div class="wl-td">
                    <label>{$smarty.const.ENTRY_IMAGES_CDN_MODE}</label>
                    {Html::radio('platform_images_cdn_status', ($pInfo->platform_images_cdn_status != 'non_ssl' && $pInfo->platform_images_cdn_status != 'ssl_supported'), ['value'=> 'off', 'class' => 'js_switch_images_cdn_status'])} {$smarty.const.LABEL_IMAGE_CDN_OFF}
                    {Html::radio('platform_images_cdn_status', ($pInfo->platform_images_cdn_status == 'non_ssl'), ['value'=> 'non_ssl', 'class' => 'js_switch_images_cdn_status'])} {$smarty.const.LABEL_IMAGE_CDN_NON_SSL}
                    {Html::radio('platform_images_cdn_status', ($pInfo->platform_images_cdn_status == 'ssl_supported'), ['value'=> 'ssl_supported', 'class' => 'js_switch_images_cdn_status'])} {$smarty.const.LABEL_IMAGE_CDN_SSL_SUPPORTED}
                </div>
            </div>
            <div class="w-line-row w-line-row-1 js-cdn_url_row">
                <div class="wl-td">
                    <label>{$smarty.const.ENTRY_IMAGES_CDN_URL}</label>
                    {Html::input('text','platform_images_cdn_url', $pInfo->platform_images_cdn_url, ['class' => 'form-control'])}
                </div>
            </div-->
        </div>
    </div>

    <div class="widget box box-no-shadow" style="min-height:183px;">
        <div class="widget-header widget-header-theme"><h4>{$smarty.const.TEXT_WATERMARK}</h4></div>
        <div class="widget-content">
            <div class="w-line-row w-line-row-1">
                <div class="wl-td">
                    <label>{$smarty.const.ENTRY_STATUS}</label>
                    {Html::checkbox('watermark_status', $pInfo->watermark_status, ['value'=>'1', 'class' => 'js_check_watermark_status'])}
                </div>
            </div>
            
            <div class="can_set_watermark">
                <div class="tabbable tabbable-custom">
                    <ul class="nav nav-tabs">
                        <li class="active"><a href="#tab_1" data-toggle="tab">{$smarty.const.TEXT_BIG}</a></li>
                        <li><a href="#tab_2" data-toggle="tab">{$smarty.const.TEXT_MEDIUM}</a></li>
                        <li><a href="#tab_3" data-toggle="tab">{$smarty.const.TEXT_SMALL}</a></li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active topTabPane tabbable-custom" id="tab_1">
                            <div class="wrap_watermark after">
                                <div class="top_left_watermark300{if $pInfo->top_left_watermark300 != ''} upl{/if}">
                                    <img width="100" height="100"{if $pInfo->top_left_watermark300 == ''} style="display: none;"{/if} src="{DIR_WS_CATALOG}images/stamp/{$pInfo->top_left_watermark300}">
                                    <a href="javascript:void(0)" class="btn-wat-up" onclick="click_watermark('top_left_watermark300')"></a>
                                    <div class="upload-remove"{if $pInfo->top_left_watermark300 == ''} style="display: none;"{/if} onclick="delete_watermark('top_left_watermark300')"></div>
                                    {Html::hiddenInput('top_left_watermark300', $pInfo->top_left_watermark300)}
                                </div>
                                <div class="top_watermark300{if $pInfo->top_watermark300 != ''} upl{/if}">
                                    <img width="100" height="100"{if $pInfo->top_watermark300 == ''} style="display: none;"{/if} src="{DIR_WS_CATALOG}images/stamp/{$pInfo->top_watermark300}">
                                    <a href="javascript:void(0)" class="btn-wat-up" onclick="click_watermark('top_watermark300')"></a>
                                    <div class="upload-remove"{if $pInfo->top_watermark300 == ''} style="display: none;"{/if} onclick="delete_watermark('top_watermark300')"></div>
                                    {Html::hiddenInput('top_watermark300', $pInfo->top_watermark300)}
                                </div>
                                <div class="top_right_watermark300{if $pInfo->top_right_watermark300 != ''} upl{/if}">
                                    <img width="100" height="100"{if $pInfo->top_right_watermark300 == ''} style="display: none;"{/if} src="{DIR_WS_CATALOG}images/stamp/{$pInfo->top_right_watermark300}">
                                    <a href="javascript:void(0)" class="btn-wat-up" onclick="click_watermark('top_right_watermark300')"></a>
                                    <div class="upload-remove"{if $pInfo->top_right_watermark300 == ''} style="display: none;"{/if} onclick="delete_watermark('top_right_watermark300')"></div>
                                    {Html::hiddenInput('top_right_watermark300', $pInfo->top_right_watermark300)}
                                </div>
                                <div class="left_watermark300{if $pInfo->left_watermark300 != ''} upl{/if}">
                                    <img width="100" height="100"{if $pInfo->left_watermark300 == ''} style="display: none;"{/if} src="{DIR_WS_CATALOG}images/stamp/{$pInfo->left_watermark300}">
                                    <a href="javascript:void(0)" class="btn-wat-up" onclick="click_watermark('left_watermark300')"></a>
                                    <div class="upload-remove"{if $pInfo->left_watermark300 == ''} style="display: none;"{/if} onclick="delete_watermark('left_watermark300')"></div>
                                    {Html::hiddenInput('left_watermark300', $pInfo->left_watermark300)}
                                </div>
                                <div class="watermark300{if $pInfo->watermark300 != ''} upl{/if}">
                                    <img width="100" height="100"{if $pInfo->watermark300 == ''} style="display: none;"{/if} src="{DIR_WS_CATALOG}images/stamp/{$pInfo->watermark300}">
                                    <a href="javascript:void(0)" class="btn-wat-up" onclick="click_watermark('watermark300')"></a>
                                    <div class="upload-remove"{if $pInfo->watermark300 == ''} style="display: none;"{/if} onclick="delete_watermark('watermark300')"></div>
                                    {Html::hiddenInput('watermark300', $pInfo->watermark300)}
                                </div>
                                <div class="right_watermark300{if $pInfo->right_watermark300 != ''} upl{/if}">
                                    <img width="100" height="100"{if $pInfo->right_watermark300 == ''} style="display: none;"{/if} src="{DIR_WS_CATALOG}images/stamp/{$pInfo->right_watermark300}">
                                    <a href="javascript:void(0)" class="btn-wat-up" onclick="click_watermark('right_watermark300')"></a>
                                    <div class="upload-remove"{if $pInfo->right_watermark300 == ''} style="display: none;"{/if} onclick="delete_watermark('right_watermark300')"></div>
                                    {Html::hiddenInput('right_watermark300', $pInfo->right_watermark300)}
                                </div>                            
                                <div class="bottom_left_watermark300{if $pInfo->bottom_left_watermark300 != ''} upl{/if}">
                                    <img width="100" height="100"{if $pInfo->bottom_left_watermark300 == ''} style="display: none;"{/if} src="{DIR_WS_CATALOG}images/stamp/{$pInfo->bottom_left_watermark300}">
                                    <a href="javascript:void(0)" class="btn-wat-up" onclick="click_watermark('bottom_left_watermark300')"></a>
                                    <div class="upload-remove"{if $pInfo->bottom_left_watermark300 == ''} style="display: none;"{/if} onclick="delete_watermark('bottom_left_watermark300')"></div>
                                    {Html::hiddenInput('bottom_left_watermark300', $pInfo->bottom_left_watermark300)}
                                </div>
                                <div class="bottom_watermark300{if $pInfo->bottom_watermark300 != ''} upl{/if}">
                                    <img width="100" height="100"{if $pInfo->bottom_watermark300 == ''} style="display: none;"{/if} src="{DIR_WS_CATALOG}images/stamp/{$pInfo->bottom_watermark300}">
                                    <a href="javascript:void(0)" class="btn-wat-up" onclick="click_watermark('bottom_watermark300')"></a>
                                    <div class="upload-remove"{if $pInfo->bottom_watermark300 == ''} style="display: none;"{/if} onclick="delete_watermark('bottom_watermark300')"></div>
                                    {Html::hiddenInput('bottom_watermark300', $pInfo->bottom_watermark300)}
                                </div>
                                <div class="bottom_right_watermark300{if $pInfo->bottom_right_watermark300 != ''} upl{/if}">
                                    <img width="100" height="100"{if $pInfo->bottom_right_watermark300 == ''} style="display: none;"{/if} src="{DIR_WS_CATALOG}images/stamp/{$pInfo->bottom_right_watermark300}">
                                    <a href="javascript:void(0)" class="btn-wat-up" onclick="click_watermark('bottom_right_watermark300')"></a>
                                    <div class="upload-remove"{if $pInfo->bottom_right_watermark300 == ''} style="display: none;"{/if} onclick="delete_watermark('bottom_right_watermark300')"></div>
                                    {Html::hiddenInput('bottom_right_watermark300', $pInfo->bottom_right_watermark300)}
                                </div> 
                            </div>
<div class="watermark-info">
    <h4>{$smarty.const.TEXT_MIN_WIDTH} - 300px</h4>
    <div class="about-image-text">
        {$smarty.const.IMAGES_BIGGER_THAN|sprintf:300}
        <ul>
            {$smarty.const.IF_YOUD_LIKE_TO_UPLOAD|sprintf:300}
        </ul>
    </div>
</div>
                        </div>
                        <div class="tab-pane topTabPane tabbable-custom" id="tab_2">
                            <div class="wrap_watermark after wrap_watermark170">
                                <div class="top_left_watermark170{if $pInfo->top_left_watermark170 != ''} upl{/if}">
                                    <img width="100" height="100"{if $pInfo->top_left_watermark170 == ''} style="display: none;"{/if} src="{DIR_WS_CATALOG}images/stamp/{$pInfo->top_left_watermark170}">
                                    <a href="javascript:void(0)" class="btn-wat-up" onclick="click_watermark('top_left_watermark170')"></a>
                                    <div class="upload-remove"{if $pInfo->top_left_watermark170 == ''} style="display: none;"{/if} onclick="delete_watermark('top_left_watermark170')"></div>
                                    {Html::hiddenInput('top_left_watermark170', $pInfo->top_left_watermark170)}
                                </div>
                                <div class="top_watermark170{if $pInfo->top_watermark170 != ''} upl{/if}">
                                    <img width="100" height="100"{if $pInfo->top_watermark170 == ''} style="display: none;"{/if} src="{DIR_WS_CATALOG}images/stamp/{$pInfo->top_watermark170}">
                                    <a href="javascript:void(0)" class="btn-wat-up" onclick="click_watermark('top_watermark170')"></a>
                                    <div class="upload-remove"{if $pInfo->top_watermark170 == ''} style="display: none;"{/if} onclick="delete_watermark('top_watermark170')"></div>
                                    {Html::hiddenInput('top_watermark170', $pInfo->top_watermark170)}
                                </div>
                                <div class="top_right_watermark170{if $pInfo->top_right_watermark170 != ''} upl{/if}">
                                    <img width="100" height="100"{if $pInfo->top_right_watermark170 == ''} style="display: none;"{/if} src="{DIR_WS_CATALOG}images/stamp/{$pInfo->top_right_watermark170}">
                                    <a href="javascript:void(0)" class="btn-wat-up" onclick="click_watermark('top_right_watermark170')"></a>
                                    <div class="upload-remove"{if $pInfo->top_right_watermark170 == ''} style="display: none;"{/if} onclick="delete_watermark('top_right_watermark170')"></div>
                                    {Html::hiddenInput('top_right_watermark170', $pInfo->top_right_watermark170)}
                                </div>
                                <div class="left_watermark170{if $pInfo->left_watermark170 != ''} upl{/if}">
                                    <img width="100" height="100"{if $pInfo->left_watermark170 == ''} style="display: none;"{/if} src="{DIR_WS_CATALOG}images/stamp/{$pInfo->left_watermark170}">
                                    <a href="javascript:void(0)" class="btn-wat-up" onclick="click_watermark('left_watermark170')"></a>
                                    <div class="upload-remove"{if $pInfo->left_watermark170 == ''} style="display: none;"{/if} onclick="delete_watermark('left_watermark170')"></div>
                                    {Html::hiddenInput('left_watermark170', $pInfo->left_watermark170)}
                                </div>
                                <div class="watermark170{if $pInfo->watermark170 != ''} upl{/if}">
                                    <img width="100" height="100"{if $pInfo->watermark170 == ''} style="display: none;"{/if} src="{DIR_WS_CATALOG}images/stamp/{$pInfo->watermark170}">
                                    <a href="javascript:void(0)" class="btn-wat-up" onclick="click_watermark('watermark170')"></a>
                                    <div class="upload-remove"{if $pInfo->watermark170 == ''} style="display: none;"{/if} onclick="delete_watermark('watermark170')"></div>
                                    {Html::hiddenInput('watermark170', $pInfo->watermark170)}
                                </div>
                                <div class="right_watermark170{if $pInfo->right_watermark170 != ''} upl{/if}">
                                    <img width="100" height="100"{if $pInfo->right_watermark170 == ''} style="display: none;"{/if} src="{DIR_WS_CATALOG}images/stamp/{$pInfo->right_watermark170}">
                                    <a href="javascript:void(0)" class="btn-wat-up" onclick="click_watermark('right_watermark170')"></a>
                                    <div class="upload-remove"{if $pInfo->right_watermark170 == ''} style="display: none;"{/if} onclick="delete_watermark('right_watermark170')"></div>
                                    {Html::hiddenInput('right_watermark170', $pInfo->right_watermark170)}
                                </div>                            
                                <div class="bottom_left_watermark170{if $pInfo->bottom_left_watermark170 != ''} upl{/if}">
                                    <img width="100" height="100"{if $pInfo->bottom_left_watermark170 == ''} style="display: none;"{/if} src="{DIR_WS_CATALOG}images/stamp/{$pInfo->bottom_left_watermark170}">
                                    <a href="javascript:void(0)" class="btn-wat-up" onclick="click_watermark('bottom_left_watermark170')"></a>
                                    <div class="upload-remove"{if $pInfo->bottom_left_watermark170 == ''} style="display: none;"{/if} onclick="delete_watermark('bottom_left_watermark170')"></div>
                                    {Html::hiddenInput('bottom_left_watermark170', $pInfo->bottom_left_watermark170)}
                                </div>
                                <div class="bottom_watermark170{if $pInfo->bottom_watermark170 != ''} upl{/if}">
                                    <img width="100" height="100"{if $pInfo->bottom_watermark170 == ''} style="display: none;"{/if} src="{DIR_WS_CATALOG}images/stamp/{$pInfo->bottom_watermark170}">
                                    <a href="javascript:void(0)" class="btn-wat-up" onclick="click_watermark('bottom_watermark170')"></a>
                                    <div class="upload-remove"{if $pInfo->bottom_watermark170 == ''} style="display: none;"{/if} onclick="delete_watermark('bottom_watermark170')"></div>
                                    {Html::hiddenInput('bottom_watermark170', $pInfo->bottom_watermark170)}
                                </div>
                                <div class="bottom_right_watermark170{if $pInfo->bottom_right_watermark170 != ''} upl{/if}">
                                    <img width="100" height="100"{if $pInfo->bottom_right_watermark170 == ''} style="display: none;"{/if} src="{DIR_WS_CATALOG}images/stamp/{$pInfo->bottom_right_watermark170}">
                                    <a href="javascript:void(0)" class="btn-wat-up" onclick="click_watermark('bottom_right_watermark170')"></a>
                                    <div class="upload-remove"{if $pInfo->bottom_right_watermark170 == ''} style="display: none;"{/if} onclick="delete_watermark('bottom_right_watermark170')"></div>
                                    {Html::hiddenInput('bottom_right_watermark170', $pInfo->bottom_right_watermark170)}
                                </div> 
                            </div>
                            <div class="watermark-info">
                                <h4>{$smarty.const.TEXT_MIN_WIDTH} - 170px</h4>
                                <div class="about-image-text">
                                    {$smarty.const.IMAGES_BIGGER_THAN|sprintf:170}
                                    <ul>
                                        {$smarty.const.IF_YOUD_LIKE_TO_UPLOAD|sprintf:170}
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane topTabPane tabbable-custom" id="tab_3">
                            <div class="wrap_watermark after wrap_watermark30">
                                <div class="top_left_watermark30{if $pInfo->top_left_watermark30 != ''} upl{/if}">
                                    <img width="100" height="100"{if $pInfo->top_left_watermark30 == ''} style="display: none;"{/if} src="{DIR_WS_CATALOG}images/stamp/{$pInfo->top_left_watermark30}">
                                    <a href="javascript:void(0)" class="btn-wat-up" onclick="click_watermark('top_left_watermark30')"></a>
                                    <div class="upload-remove"{if $pInfo->top_left_watermark30 == ''} style="display: none;"{/if} onclick="delete_watermark('top_left_watermark30')"></div>
                                    {Html::hiddenInput('top_left_watermark30', $pInfo->top_left_watermark30)}
                                </div>
                                <div class="top_watermark30{if $pInfo->top_watermark30 != ''} upl{/if}">
                                    <img width="100" height="100"{if $pInfo->top_watermark30 == ''} style="display: none;"{/if} src="{DIR_WS_CATALOG}images/stamp/{$pInfo->top_watermark30}">
                                    <a href="javascript:void(0)" class="btn-wat-up" onclick="click_watermark('top_watermark30')"></a>
                                    <div class="upload-remove"{if $pInfo->top_watermark30 == ''} style="display: none;"{/if} onclick="delete_watermark('top_watermark30')"></div>
                                    {Html::hiddenInput('top_watermark30', $pInfo->top_watermark30)}
                                </div>
                                <div class="top_right_watermark30{if $pInfo->top_right_watermark30 != ''} upl{/if}">
                                    <img width="100" height="100"{if $pInfo->top_right_watermark30 == ''} style="display: none;"{/if} src="{DIR_WS_CATALOG}images/stamp/{$pInfo->top_right_watermark30}">
                                    <a href="javascript:void(0)" class="btn-wat-up" onclick="click_watermark('top_right_watermark30')"></a>
                                    <div class="upload-remove"{if $pInfo->top_right_watermark30 == ''} style="display: none;"{/if} onclick="delete_watermark('top_right_watermark30')"></div>
                                    {Html::hiddenInput('top_right_watermark30', $pInfo->top_right_watermark30)}
                                </div>
                                <div class="left_watermark30{if $pInfo->left_watermark30 != ''} upl{/if}">
                                    <img width="100" height="100"{if $pInfo->left_watermark30 == ''} style="display: none;"{/if} src="{DIR_WS_CATALOG}images/stamp/{$pInfo->left_watermark30}">
                                    <a href="javascript:void(0)" class="btn-wat-up" onclick="click_watermark('left_watermark30')"></a>
                                    <div class="upload-remove"{if $pInfo->left_watermark30 == ''} style="display: none;"{/if} onclick="delete_watermark('left_watermark30')"></div>
                                    {Html::hiddenInput('left_watermark30', $pInfo->left_watermark30)}
                                </div>
                                <div class="watermark30{if $pInfo->watermark30 != ''} upl{/if}">
                                    <img width="100" height="100"{if $pInfo->watermark30 == ''} style="display: none;"{/if} src="{DIR_WS_CATALOG}images/stamp/{$pInfo->watermark30}">
                                    <a href="javascript:void(0)" class="btn-wat-up" onclick="click_watermark('watermark30')"></a>
                                    <div class="upload-remove"{if $pInfo->watermark30 == ''} style="display: none;"{/if} onclick="delete_watermark('watermark30')"></div>
                                    {Html::hiddenInput('watermark30', $pInfo->watermark30)}
                                </div>
                                <div class="right_watermark30{if $pInfo->right_watermark30 != ''} upl{/if}">
                                    <img width="100" height="100"{if $pInfo->right_watermark30 == ''} style="display: none;"{/if} src="{DIR_WS_CATALOG}images/stamp/{$pInfo->right_watermark30}">
                                    <a href="javascript:void(0)" class="btn-wat-up" onclick="click_watermark('right_watermark30')"></a>
                                    <div class="upload-remove"{if $pInfo->right_watermark30 == ''} style="display: none;"{/if} onclick="delete_watermark('right_watermark30')"></div>
                                    {Html::hiddenInput('right_watermark30', $pInfo->right_watermark30)}
                                </div>                            
                                <div class="bottom_left_watermark30{if $pInfo->bottom_left_watermark30 != ''} upl{/if}">
                                    <img width="100" height="100"{if $pInfo->bottom_left_watermark30 == ''} style="display: none;"{/if} src="{DIR_WS_CATALOG}images/stamp/{$pInfo->bottom_left_watermark30}">
                                    <a href="javascript:void(0)" class="btn-wat-up" onclick="click_watermark('bottom_left_watermark30')"></a>
                                    <div class="upload-remove"{if $pInfo->bottom_left_watermark30 == ''} style="display: none;"{/if} onclick="delete_watermark('bottom_left_watermark30')"></div>
                                    {Html::hiddenInput('bottom_left_watermark30', $pInfo->bottom_left_watermark30)}
                                </div>
                                <div class="bottom_watermark30{if $pInfo->bottom_watermark30 != ''} upl{/if}">
                                    <img width="100" height="100"{if $pInfo->bottom_watermark30 == ''} style="display: none;"{/if} src="{DIR_WS_CATALOG}images/stamp/{$pInfo->bottom_watermark30}">
                                    <a href="javascript:void(0)" class="btn-wat-up" onclick="click_watermark('bottom_watermark30')"></a>
                                    <div class="upload-remove"{if $pInfo->bottom_watermark30 == ''} style="display: none;"{/if} onclick="delete_watermark('bottom_watermark30')"></div>
                                    {Html::hiddenInput('bottom_watermark30', $pInfo->bottom_watermark30)}
                                </div>
                                <div class="bottom_right_watermark30{if $pInfo->bottom_right_watermark30 != ''} upl{/if}">
                                    <img width="100" height="100"{if $pInfo->bottom_right_watermark30 == ''} style="display: none;"{/if} src="{DIR_WS_CATALOG}images/stamp/{$pInfo->bottom_right_watermark30}">
                                    <a href="javascript:void(0)" class="btn-wat-up" onclick="click_watermark('bottom_right_watermark30')"></a>
                                    <div class="upload-remove"{if $pInfo->bottom_right_watermark30 == ''} style="display: none;"{/if} onclick="delete_watermark('bottom_right_watermark30')"></div>
                                    {Html::hiddenInput('bottom_right_watermark30', $pInfo->bottom_right_watermark30)}
                                </div> 
                            </div>
                            <div class="watermark-info">
                                <h4>{$smarty.const.TEXT_MIN_WIDTH} - 30px</h4>
                                <div class="about-image-text">
                                    {$smarty.const.IMAGES_BIGGER_THAN|sprintf:30}
                                    <ul>
                                        {$smarty.const.IF_YOUD_LIKE_TO_UPLOAD|sprintf:30}
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </div>
  <div class="cbox-right">
      <div class="widget box box-no-shadow">
        <div class="widget-header widget-header-theme"><h4>{$smarty.const.CATEGORY_ASSIGNED_THEME}</h4></div>
        <div class="widget-content">
            <div class="w-line-row w-line-row-2-big">
                <div class="theme_wr">
                    {foreach $theme_array as $res} 
                    <div class="theme_title act">
                        <img width="100" height="80" src="{DIR_WS_CATALOG}themes/{$res.theme_name}/screenshot.png">
                        <div class="theme_title2">{$res.title}</div>
                    </div>
                    {foreachelse}
                    <div class="theme_title">{$smarty.const.TEXT_NOT_CHOOSEN}</div>
                    {/foreach}
                    <a href="{Yii::$app->urlManager->createUrl('platforms/addtheme')}" class="btn popup">{$smarty.const.TEXT_CHOOSE_THEME}</a>
                </div>                
            </div>
        </div>
      </div>
    {if $pInfo->platform_id }
      <div class="widget box box-no-shadow">
        <div class="widget-header widget-header-company"><h4>{$smarty.const.CATEGORY_FORMATS}</h4></div>
        <div class="widget-content">
            <div class="w-line-row w-line-row-2-big">
                <div class="format_wr">
                    <center><a href="{Yii::$app->urlManager->createUrl(['platforms/define-formats', 'id'=>$pInfo->platform_id])}" class="btn popup">{$smarty.const.TEXT_DEFINE_FORMATS}</a></center>
                </div>                
            </div>            
        </div>
      </div>
    {/if}
    
      <div class="widget box box-no-shadow">
          <div class="widget-header widget-header-theme"><h4>{$smarty.const.TEXT_LANGUAGES_} &amp; {$smarty.const.TEXT_CURRENCIES}</h4></div>
          <div class="widget-content">
              {include $pass|cat: '/themes/basic/platforms/edit/language.tpl'}
              {include $pass|cat: '/themes/basic/platforms/edit/currency.tpl'}
          </div>
      </div>
              
      <div class="widget box box-no-shadow">
          <div class="widget-header widget-header-theme"><h4>{$smarty.const.TEXT_LOCATION_SERVICE}</h4></div>
          <div class="widget-content">
              <table class="tl-grid js-platform-locations">
                <thead>
                <tr>
                    <th>{$smarty.const.BOX_TAXES_COUNTRIES}</th>
                    <th>{$smarty.const.BOX_LOCALIZATION_LANGUAGES}</th>
                    <th>{$smarty.const.BOX_LOCALIZATION_CURRENCIES}</th>
                    <th style="width: 30px">&nbsp;</th>
                </tr>
                </thead>
                <tbody data-rows-count="{count($pInfo->platform_locations)}">
                {foreach from=$pInfo->platform_locations item=platform_location key=idx}
                    {assign var="row_index" value=$idx+1}
                <tr>
                    <td>{Html::hiddenInput('platform_locations['|cat:$row_index|cat:'][platforms_locations_id]', $platform_location['platforms_locations_id'])}
                        {Html::dropDownList('platform_locations['|cat:$row_index|cat:'][country]', $platform_location['country'], \common\helpers\Country::new_get_countries('', false), ['class' => 'form-control'])}</td>
                    <td>{Html::dropDownList('platform_locations['|cat:$row_index|cat:'][language]',$platform_location['language'],$app->controller->view->languages, ['class' => 'form-control'])}</td>
                    <td>{Html::dropDownList('platform_locations['|cat:$row_index|cat:'][currency]',$platform_location['currency'],$app->controller->view->currencies, ['class' => 'form-control'])}</td>
                    <td><button type="button" class="btn js-remove-platform-location">-</button></td>
                </tr>
                {/foreach}
                </tbody>
                <tfoot style="display: none">
                <tr>
                    <td>{Html::dropDownList('_unhide_platform_locations[%idx%][country]', $addresses->entry_country_id, \common\helpers\Country::new_get_countries('', false), ['class' => 'form-control'])}</td>
                    <td>{Html::dropDownList('_unhide_platform_locations[%idx%][language]',$smarty.const.DEFAULT_LANGUAGE,$app->controller->view->languages, ['class' => 'form-control'])}</td>
                    <td>{Html::dropDownList('_unhide_platform_locations[%idx%][currency]',$smarty.const.DEFAULT_CURRENCY,$app->controller->view->currencies, ['class' => 'form-control'])}</td>
                    <td><button type="button" class="btn js-remove-platform-location">-</button></td>
                </tr>
                </tfoot>
            </table>
            &nbsp;
            <div class="buttons_hours">
                <button type="button" class="btn js-add-platform-location">{$smarty.const.TEXT_ADD_MORE}</button>
            </div>
          </div>
      </div>
      
    </div>
  </div>
  <div class="create-or-wrap after create-cus-wrap">
      <div class="cbox-left">
          {if (\common\helpers\Acl::checkExtension('PlatformSoapServer', 'isPresent'))}
              {\common\extensions\PlatformSoapServer\PlatformSoapServer::onPlatformEditView($pInfo)}
          {/if}
      </div>
      <div class="cbox-right">
        {if $price_settings}
      <div class="widget box box-no-shadow can_set_marketplace">
          <div class="widget-header widget-header-theme"><h4>{$smarty.const.TEXT_PRICE_SETTINGS}</h4></div>
          <div class="widget-content">
              {if $pInfo->is_default}
                 {$smarty.const.TEXT_USED_OWN_PRICES}
              {else}
                <div class="w-line-row w-line-row-1">
                {Html::checkBox('use_own_prices', $pInfo->platform_settings->use_own_prices, ['class'=> 'use_own_prices', 'label' =>$smarty.const.TEXT_USE_OWN_PRICES])}
                </div>
                <div class="use-owner-prices-box" {if $pInfo->platform_settings->use_own_prices}style="display:none"{/if}>
                    <label>{$smarty.const.TEXT_SELECT_PLATFROM_OWNER}</label>
                    {Html::dropDownList('use_owner_prices', $pInfo->platform_settings->use_owner_prices, $nvPlatforms, ['class' => 'form-control'])}
                </div>
              {/if}
          </div>
      </div>
      {/if}
      <div class="widget box box-no-shadow can_set_marketplace">
          <div class="widget-header widget-header-theme"><h4>{$smarty.const.TEXT_DESCRIPTIONS_SETTINGS}</h4></div>
          <div class="widget-content">
              {if $pInfo->is_default}
                 {$smarty.const.TEXT_USED_OWN_DESCRIPTIONS}
              {else}
                <div class="w-line-row w-line-row-1">
                {Html::checkBox('use_own_descriptions', $pInfo->platform_settings->use_own_descriptions, ['class'=> 'use_own_descriptions', 'label' =>$smarty.const.TEXT_USE_OWN_DESCRIPTIONS])}
                </div>
                <div class="use-owner-desc-box" {if $pInfo->platform_settings->use_own_descriptions}style="display:none"{/if}>
                    <label>{$smarty.const.TEXT_SELECT_PLATFROM_OWNER}</label>
                    {Html::dropDownList('use_owner_descriptions', $pInfo->platform_settings->use_owner_descriptions, $nvPlatforms, ['class' => 'form-control'])}
                </div>
              {/if}
          </div>
      </div>
      {include $pass|cat: '/themes/basic/platforms/edit/warehouses.tpl'}
      </div>
  </div>
</div>
<div class="btn-bar">
    <div class="btn-left"><a href="javascript:void(0)" onclick="return backStatement();" class="btn btn-cancel-foot">{$smarty.const.IMAGE_CANCEL}</a></div>
    <div class="btn-right"><button class="btn btn-confirm">{$smarty.const.IMAGE_SAVE}</button></div>
</div>
{Html::input('hidden', 'id', $pInfo->platform_id)}
{Html::input('hidden', 'theme_id', $pInfo->theme_id)}
</form>
<div style="display: none;">
<form id="fileupload_top_left_watermark300" action="#" method="POST" enctype="multipart/form-data">
    <input name="files" id="top_left_watermark300" multiple="" type="file">
</form>
<form id="fileupload_top_watermark300" action="#" method="POST" enctype="multipart/form-data">
    <input name="files" id="top_watermark300" multiple="" type="file">
</form>
<form id="fileupload_top_right_watermark300" action="#" method="POST" enctype="multipart/form-data">
    <input name="files" id="top_right_watermark300" multiple="" type="file">
</form>
<form id="fileupload_left_watermark300" action="#" method="POST" enctype="multipart/form-data">
    <input name="files" id="left_watermark300" multiple="" type="file">
</form>
<form id="fileupload_watermark300" action="#" method="POST" enctype="multipart/form-data">
    <input name="files" id="watermark300" multiple="" type="file">
</form>
<form id="fileupload_right_watermark300" action="#" method="POST" enctype="multipart/form-data">
    <input name="files" id="right_watermark300" multiple="" type="file">
</form>
<form id="fileupload_bottom_left_watermark300" action="#" method="POST" enctype="multipart/form-data">
    <input name="files" id="bottom_left_watermark300" multiple="" type="file">
</form>
<form id="fileupload_bottom_watermark300" action="#" method="POST" enctype="multipart/form-data">
    <input name="files" id="bottom_watermark300" multiple="" type="file">
</form>
<form id="fileupload_bottom_right_watermark300" action="#" method="POST" enctype="multipart/form-data">
    <input name="files" id="bottom_right_watermark300" multiple="" type="file">
</form>
    
<form id="fileupload_top_left_watermark170" action="#" method="POST" enctype="multipart/form-data">
    <input name="files" id="top_left_watermark170" multiple="" type="file">
</form>
<form id="fileupload_top_watermark170" action="#" method="POST" enctype="multipart/form-data">
    <input name="files" id="top_watermark170" multiple="" type="file">
</form>
<form id="fileupload_top_right_watermark170" action="#" method="POST" enctype="multipart/form-data">
    <input name="files" id="top_right_watermark170" multiple="" type="file">
</form>
<form id="fileupload_left_watermark170" action="#" method="POST" enctype="multipart/form-data">
    <input name="files" id="left_watermark170" multiple="" type="file">
</form>
<form id="fileupload_watermark170" action="#" method="POST" enctype="multipart/form-data">
    <input name="files" id="watermark170" multiple="" type="file">
</form>
<form id="fileupload_right_watermark170" action="#" method="POST" enctype="multipart/form-data">
    <input name="files" id="right_watermark170" multiple="" type="file">
</form>
<form id="fileupload_bottom_left_watermark170" action="#" method="POST" enctype="multipart/form-data">
    <input name="files" id="bottom_left_watermark170" multiple="" type="file">
</form>
<form id="fileupload_bottom_watermark170" action="#" method="POST" enctype="multipart/form-data">
    <input name="files" id="bottom_watermark170" multiple="" type="file">
</form>
<form id="fileupload_bottom_right_watermark170" action="#" method="POST" enctype="multipart/form-data">
    <input name="files" id="bottom_right_watermark170" multiple="" type="file">
</form>
    
<form id="fileupload_top_left_watermark30" action="#" method="POST" enctype="multipart/form-data">
    <input name="files" id="top_left_watermark30" multiple="" type="file">
</form>
<form id="fileupload_top_watermark30" action="#" method="POST" enctype="multipart/form-data">
    <input name="files" id="top_watermark30" multiple="" type="file">
</form>
<form id="fileupload_top_right_watermark30" action="#" method="POST" enctype="multipart/form-data">
    <input name="files" id="top_right_watermark30" multiple="" type="file">
</form>
<form id="fileupload_left_watermark30" action="#" method="POST" enctype="multipart/form-data">
    <input name="files" id="left_watermark30" multiple="" type="file">
</form>
<form id="fileupload_watermark30" action="#" method="POST" enctype="multipart/form-data">
    <input name="files" id="watermark30" multiple="" type="file">
</form>
<form id="fileupload_right_watermark30" action="#" method="POST" enctype="multipart/form-data">
    <input name="files" id="right_watermark30" multiple="" type="file">
</form>
<form id="fileupload_bottom_left_watermark30" action="#" method="POST" enctype="multipart/form-data">
    <input name="files" id="bottom_left_watermark30" multiple="" type="file">
</form>
<form id="fileupload_bottom_watermark30" action="#" method="POST" enctype="multipart/form-data">
    <input name="files" id="bottom_watermark30" multiple="" type="file">
</form>
<form id="fileupload_bottom_right_watermark30" action="#" method="POST" enctype="multipart/form-data">
    <input name="files" id="bottom_right_watermark30" multiple="" type="file">
</form>
    
</div>
<script>
function click_watermark (name) {
    $('#'+name).click(); 
}
function delete_watermark (name) {
    $('input[name='+name+']').val('');
    $('div.'+name+'').children('div.upload-remove').hide();
    $('div.'+name+'').children('img').hide();
    $('div.'+name+'').removeClass('upl');
}
</script>
<div id="opening_hours_template" style="display: none;">
    <div class="w-line-row opening_hours">
        <div>
            <div class="hours_table">
                <label>{$smarty.const.ENTRY_DAYS}<span class="fieldRequired">*</span></label>
                <div class="col-md-10">
                {Html::dropDownList('open_days_', '', $days, ['class' => 'form-control', 'multiple' => 'multiple', 'data-role' => 'multiselect-new'])}
                </div>
            </div>
        </div>
        <div class="time_int">
            <div class="time_int_1">
                <label>{$smarty.const.ENTRY_TIME}<span class="fieldRequired">*</span></label>
                <span class="time_title">{$smarty.const.ENTRY_TIME_FROM}</span>{Html::input('text', 'open_time_from[]', '', ['class' => 'pt-time-new form-control'])}
            </div>
            <div class="time_int_2">
                <span class="time_title">{$smarty.const.ENTRY_TIME_TO}</span>{Html::input('text', 'open_time_to[]', '', ['class' => 'pt-time-new form-control'])}
            </div>
            <div class="time_int_3">
                <a href="javascript:void(0)" onclick="return removeOpenHours(this);" class="btn">-</a>
            </div>
        </div>                              
        {Html::input('hidden', 'platforms_open_hours_id[]', '')}
        {Html::input('hidden', 'platforms_open_hours_key[]', '')}
    </div>
</div>
<div id="cut_off_times_template" style="display: none;">
    <div class="w-line-row opening_hours">
        <div>
            <div class="hours_table">
                <label>{$smarty.const.ENTRY_DAYS}<span class="fieldRequired">*</span></label>
                <div class="col-md-10">
                {Html::dropDownList('cut_off_times_days_', '', $days, ['class' => 'form-control', 'multiple' => 'multiple', 'data-role' => 'multiselect-new'])}
                </div>
            </div>
        </div>
        <div class="time_int">
            <div class="time_int_1">
                <label>{$smarty.const.DAY_OF_WEEK}<span class="fieldRequired">*</span></label>
                <span class="time_title">{$smarty.const.TODAY_DELIVERY}</span>{Html::input('text', 'cut_off_times_today[]', '', ['class' => 'pt-time-new form-control'])}
            </div>
            <div class="time_int_2">
                <span class="time_title">{$smarty.const.NEXT_DAY_DELIVERY}</span>{Html::input('text', 'cut_off_times_next_day[]', '', ['class' => 'pt-time-new form-control'])}
            </div>
            <div class="time_int_3">
                <a href="javascript:void(0)" onclick="return removeCutOffTimes(this);" class="btn">-</a>
            </div>
        </div>                              
        {Html::input('hidden', 'platforms_cut_off_times_id[]', '')}
        {Html::input('hidden', 'platforms_cut_off_times_key[]', '')}
    </div>
</div>
<script type="text/javascript" src="{$app->request->baseUrl}/plugins/fileupload/jquery.fileupload.js"></script>
<script>
$('#selectState').autocomplete({
    source: function(request, response) {
        $.ajax({
            url: "{$app->urlManager->createUrl('customers/states')}",
            dataType: "json",
            data: {
                term : request.term,
                country : $("#selectCountry{$keyvar}").val()
            },
            success: function(data) {
                response(data);
            }
        });
    },
    minLength: 0,
    autoFocus: true,
    delay: 0,
    appendTo: '.f_td_state',
    open: function (e, ui) {
      if ($(this).val().length > 0) {
        var acData = $(this).data('ui-autocomplete');
        acData.menu.element.find('a').each(function () {
          var me = $(this);
          var keywords = acData.term.split(' ').join('|');
          me.html(me.text().replace(new RegExp("(" + keywords + ")", "gi"), '<b>$1</b>'));
        });
      }
    },
    select: function(event, ui) {
        $('input[name="city"]').prop('disabled', true);
        if(ui.item.value != null){ 
            $('input[name="city"]').prop('disabled', false);
        }
    }
}).focus(function () {
  $(this).autocomplete("search");
});
var nextKey = {$count_open_hours};
function removeOpenHours(obj) {
    $(obj).parent('div').parent('div').parent('div.opening_hours').remove();
    return false;
}
function addOpenHours() {
    nextKey = nextKey +1;
    $('#opening_hours_template').find('select[name*="open_days"]').attr('name', 'open_days_'+nextKey+'[]');
    $('#opening_hours_template').find('input[name="platforms_open_hours_key[]"]').val(nextKey);
    $('#opening_hours_list').append($('#opening_hours_template').html());
    $("form select[data-role=multiselect-new]").attr('data-role', 'multiselect');
    $("form select[data-role=multiselect]").multiselect({
        selectedList: 1 // 0-based index
     });
    $('form .pt-time-new').ptTimeSelect();
    
    return false;
}
var nextDeliveryKey = {$count_cut_off_times};
function addCutOffTimes() {
    nextDeliveryKey = nextDeliveryKey +1;
    $('#cut_off_times_template').find('select[name*="cut_off_times_days"]').attr('name', 'cut_off_times_days_'+nextDeliveryKey+'[]');
    $('#cut_off_times_template').find('input[name="platforms_cut_off_times_key[]"]').val(nextDeliveryKey);
    $('#cut_off_times_list').append($('#cut_off_times_template').html());
    $("form select[data-role=multiselect-new]").attr('data-role', 'multiselect');
    $("form select[data-role=multiselect]").multiselect({
        selectedList: 1 // 0-based index
     });
    $('form .pt-time-new').ptTimeSelect();
    return false;
}
function removeCutOffTimes(obj) {
    $(obj).parent('div').parent('div').parent('div.opening_hours').remove();
    return false;
}
function saveItem() {
    $.post("{$app->urlManager->createUrl('platforms/submit')}", $('#save_item_form').serialize(), function (data, status) {
        if (status == "success") {
            $('#platforms_management_data').html(data);
        } else {
            alert("Request error.");
        }
    }, "html");

    return false;
}
function backStatement() {
    window.history.back();
    return false;
}
var shop_statement = '';
{if $pInfo->is_default == 1}
shop_statement = 'default_shop';
{else}
    {if $pInfo->is_marketplace != 1 && $pInfo->is_virtual != 1}
    shop_statement = 'shop';
    {/if}
    {if $pInfo->is_marketplace == 1}
    shop_statement = 'marketplace';
    {/if}
    {if $pInfo->is_virtual == 1}
    shop_statement = 'virtual';
    {/if}
        
{/if}
var default_address_current_state = {if $pInfo->is_default_address == 1}true{else}false{/if};
var default_contact_current_state = {if $pInfo->is_default_contact == 1}true{else}false{/if};

function fn_default_contact_enable_switch (state) {
    if (state) {
        $('.no_default_contact').hide();
        $('.default_contact_no_check').prop('disabled', true);
    }else {
        $('.no_default_contact').show();
        $('.default_contact_no_check').prop('disabled', false);
    }
    default_contact_current_state = state;
}
function fn_default_address_enable_switch(state) {
    if (state) {
        $('.no_default_address').hide();
        $('.default_address_no_check').prop('disabled', true);
    }else {
        $('.no_default_address').show();
        $('.default_address_no_check').prop('disabled', false);
    }
    default_address_current_state = state;
}

function show_platform_boxes() {
    switch (shop_statement) {
        case 'default_shop':
            $('.marketplace_switcher').hide();
            $('.virtual_switcher').hide();
            $('.no_marketplace_or_virtual').show();
            $('.default_shop_selector').hide();
            $('.yes_virtual').hide();
            $('.contact_switcher').hide();
            $('.js_check_is_default_contact').bootstrapSwitch('state', false, true);
            fn_default_contact_enable_switch(false);
            $('.js_check_is_default_address').bootstrapSwitch('state', false, true);
            fn_default_address_enable_switch(false);
          break;
        case 'marketplace':
            $('.marketplace_switcher').show();
            $('.virtual_switcher').hide();
            $('.no_marketplace_or_virtual').hide();
            $('.default_shop_selector').show();
            $('.yes_virtual').hide();
            $('.contact_switcher').show();
            
            $('.js_check_is_default_contact').bootstrapSwitch('state', default_contact_current_state, true);
            fn_default_contact_enable_switch(default_contact_current_state);
            $('.js_check_is_default_address').bootstrapSwitch('state', default_address_current_state, true);
            fn_default_address_enable_switch(default_address_current_state);
          break;
        case 'virtual':
            $('.marketplace_switcher').hide();
            $('.virtual_switcher').show();
            $('.no_marketplace_or_virtual').hide();
            $('.default_shop_selector').hide();
            $('.yes_virtual').show();
            $('.contact_switcher').show();
            
            $('.js_check_is_default_contact').bootstrapSwitch('state', default_contact_current_state, true);
            fn_default_contact_enable_switch(default_contact_current_state);
            $('.js_check_is_default_address').bootstrapSwitch('state', default_address_current_state, true);
            fn_default_address_enable_switch(default_address_current_state);
          break;
        case 'shop':
        default:
            $('.marketplace_switcher').show();
            $('.virtual_switcher').show();
            $('.no_marketplace_or_virtual').show();
            $('.default_shop_selector').show();
            $('.yes_virtual').hide();
            $('.contact_switcher').show();

            $('.js_check_is_default_contact').bootstrapSwitch('state', default_contact_current_state, true);
            fn_default_contact_enable_switch(default_contact_current_state);
            $('.js_check_is_default_address').bootstrapSwitch('state', default_address_current_state, true);
            fn_default_address_enable_switch(default_address_current_state);

          break;
    }
}
$(document).ready(function(){
    $('.theme_wr .popup').popUp({		
      box: "<div class='popup-box-wrap'><div class='around-pop-up'></div><div class='popup-box theme_popup'><div class='pop-up-close pop-up-close-alert'></div><div class='popup-heading theme_choose'>{$smarty.const.TEXT_CHOOSE_THEME}</div><div class='pop-up-content'><div class='preloader'></div></div></div></div>"
    });
    $('.format_wr .popup').popUp({		
      box: "<div class='popup-box-wrap'><div class='around-pop-up'></div><div class='popup-box theme_popup'><div class='pop-up-close pop-up-close-alert'></div><div class='popup-heading theme_choose'>{$smarty.const.CATEGORY_FORMATS}</div><div class='pop-up-content'><div class='preloader'></div></div></div></div>"
    });
    $('.buttons_holidays .popup').popUp({		
      box: "<div class='popup-box-wrap'><div class='around-pop-up'></div><div class='popup-box'><div class='pop-up-close pop-up-close-alert'></div><div class='popup-heading theme_choose'>{$smarty.const.BANK_HOLIDAYS}</div><div class='pop-up-content'><div class='preloader'></div></div></div></div>"
    })
    
    $('.pt-time').ptTimeSelect();
    
    $('select[name=sattelit_id]').change(function(){
        if ($(this).val() > 0){
            $('input[name=platform_url]').prop('required', false);
        } else {
            $('input[name=platform_url]').prop('required', true);
        }
    })

    $("select[data-role=multiselect]").multiselect({
        selectedList: 1, // 0-based index
        click: function(e, ui){ 
            console.log($(this).multiselect("widget").find("input:checked"));
            if(ui['value'] > 0){ 
                
            }
        }
    });

    var platformEmailUpdated = function(){
        var email = $(this).val();
        $('.js-override-platform-email').each(function(){ $(this).attr('placeholder',email) });
    };
    $('#txtPlatformEmail').on('keyup', platformEmailUpdated);
    platformEmailUpdated.apply($('#txtPlatformEmail').get(0));

    $(window).resize();
    $('.js-rate-margin').on('keyup keydown', function(){
        var rate_margin = $(this).val()+ $(this).next().val();
        $('.currency_'+$(this).data('code')).find('.currency_margin').text(rate_margin);
    })
    $('.js-rate-margin').on('focusout', function(){
        if($(this).val() == ''){
            $(this).val(0);
            $('.currency_'+$(this).data('code')).find('.currency_margin').text(0+$(this).next().val());
        }

    })
    $('.margin_type').change(function(){
        var rate_margin = $(this).val()+ $(this).prev().val();
        $('.currency_'+$(this).prev().data('code')).find('.currency_margin').text(rate_margin);
    })
    $('.js_check_status').bootstrapSwitch({
        onText: "{$smarty.const.SW_ON}",
        offText: "{$smarty.const.SW_OFF}",
        handleWidth: '20px',
        labelWidth: '24px'
    });
    $('.js_check_watermark_status').bootstrapSwitch({
        onText: "{$smarty.const.SW_ON}",
        offText: "{$smarty.const.SW_OFF}",
        handleWidth: '20px',
        labelWidth: '24px'
    });
    var fn_set_watermark_switch = function(state) {
        if (state) {
            $('.can_set_watermark').show();
        }else {
            $('.can_set_watermark').hide();
        }
    }
    $('.js_check_watermark_status').on('click switchChange.bootstrapSwitch',function(){
        fn_set_watermark_switch(this.checked);
    });
    $('.js_check_watermark_status').each(function() {
        fn_set_watermark_switch.apply(this,[this.checked]);
    });
    $('.js_switch_images_cdn_status').on('click',function(){
        if ( this.value=='off' ) {
            $('.js-cdn_url_row').hide();
        }else{
            $('.js-cdn_url_row').show();
        }
    });
    $('.js_switch_images_cdn_status:checked').trigger('click');
    
    $('.js_check_default_platform').bootstrapSwitch({
        onText: "{$smarty.const.SW_ON}",
        offText: "{$smarty.const.SW_OFF}",
        handleWidth: '20px',
        labelWidth: '24px'
    });
    var fn_set_shop_switch = function(state) {
        if (state) {
            shop_statement = 'default_shop';
        } else if (shop_statement == 'default_shop') {
            shop_statement = 'shop';
        }
        show_platform_boxes();
    }
    $('.js_check_default_platform').on('click switchChange.bootstrapSwitch',function(){
        fn_set_shop_switch(this.checked);
    });
    /*$('.js_check_default_platform').each(function() {
        fn_set_shop_switch.apply(this,[this.checked]);
    });*/
    
    
    var fn_ssl_enable_switch = function(state) {
        if (state) {
            $('.js_check_ssl_enabled_true').show();
        }else {
            $('.js_check_ssl_enabled_true').hide();
        }
    }
    $('.js_check_ssl_enabled').on('change',function(){
        fn_ssl_enable_switch( ($('.js_check_ssl_enabled:checked').val() > 0)  );
    });
    $('.js_check_ssl_enabled').each(function() {
        fn_ssl_enable_switch.apply(this,[ ($('.js_check_ssl_enabled:checked').val() > 0) ]);
    });
    $('.js_check_need_login').bootstrapSwitch({
        onText: "{$smarty.const.SW_ON}",
        offText: "{$smarty.const.SW_OFF}",
        handleWidth: '20px',
        labelWidth: '24px'
    });
    
    $('.js_check_use_social_login').bootstrapSwitch({
        onText: "{$smarty.const.SW_ON}",
        offText: "{$smarty.const.SW_OFF}",
        handleWidth: '20px',
        labelWidth: '24px'
    });
    
    $('.js_check_is_marketplace').bootstrapSwitch({
        onText: "{$smarty.const.SW_ON}",
        offText: "{$smarty.const.SW_OFF}",
        handleWidth: '20px',
        labelWidth: '24px'
    });
    var fn_marketplace_enable_switch = function(state) {
        if (state) {
            shop_statement = 'marketplace';
        } else if (shop_statement != 'default_shop') {
            shop_statement = 'shop';
        }
        show_platform_boxes();
    }
    $('.js_check_is_marketplace').on('click switchChange.bootstrapSwitch',function(){
        fn_marketplace_enable_switch(this.checked);
    });
    /*$('.js_check_is_marketplace').each(function() {
        fn_marketplace_enable_switch.apply(this,[this.checked], true);
    });*/
    
    
    
    $('.js_check_is_virtual').bootstrapSwitch({
        onText: "{$smarty.const.SW_ON}",
        offText: "{$smarty.const.SW_OFF}",
        handleWidth: '20px',
        labelWidth: '24px'
    });
    
    var buildLink = function(platform_id){
        var ulrs = {json_encode($sattelites_url)};
        if (ulrs[platform_id]){
            $('.virtual_link').attr('href', ulrs[platform_id]+'/?code=' + $('.platform_code').val());
            $('.virtual_link').html(ulrs[platform_id]+'/?code=' + $('.platform_code').val());
        }        
    }
    
    buildLink.call(this, $('select[name=sattelit_id]').val());
    
    $('select[name=sattelit_id]').change(function(){
        buildLink.call(this, $(this).val());
    })
    
    $('input[name=platform_code]').keyup(function(){
        buildLink.call(this, $('select[name=sattelit_id]').val());
    })
    
    $('#lnCodeGenerate').on('click',function(){
        $.getJSON('{\Yii::$app->urlManager->createUrl('platforms/generate-key')}',function( data ) {
            if (data.platform_code) {
                $('.platform_code').val(data.platform_code);
                buildLink.call(this, $('select[name=sattelit_id]').val());
            }
        });
    });
    
    $('.use_own_prices').bootstrapSwitch({
        onText: "{$smarty.const.SW_ON}",
        offText: "{$smarty.const.SW_OFF}",
        handleWidth: '20px',
        labelWidth: '24px',
    });
    var fn_set_prices_switch = function(state) {
        if (state) {
            $('.use-owner-prices-box').hide();
        }else {
            $('.use-owner-prices-box').show();
        }
    }
    $('.use_own_prices').on('switchChange.bootstrapSwitch',function(){
        fn_set_prices_switch(this.checked);
    });
    
    $('.use_own_descriptions').bootstrapSwitch({
        onText: "{$smarty.const.SW_ON}",
        offText: "{$smarty.const.SW_OFF}",
        handleWidth: '20px',
        labelWidth: '24px',
    });
    var fn_set_desc_switch = function(state) {
        if (state) {
            $('.use-owner-desc-box').hide();
        }else {
            $('.use-owner-desc-box').show();
        }
    }
    $('.use_own_descriptions').on('switchChange.bootstrapSwitch',function(){
        fn_set_desc_switch(this.checked);
    });
    
    var fn_virtual_enable_switch = function(state) {
        if (state) {
            shop_statement = 'virtual';
        } else if (shop_statement != 'default_shop') {
            shop_statement = 'shop';
        }
        show_platform_boxes();
    }

    $('.js_check_is_virtual').on('click switchChange.bootstrapSwitch',function(){
        fn_virtual_enable_switch(this.checked);
    });
    
    $('.js_check_is_default_contact').bootstrapSwitch({
        onText: "{$smarty.const.SW_ON}",
        offText: "{$smarty.const.SW_OFF}",
        handleWidth: '20px',
        labelWidth: '24px'
    });
    
    $('.js_check_is_default_contact').on('click switchChange.bootstrapSwitch',function(){
        fn_default_contact_enable_switch(this.checked);
    });
    $('.js_check_is_default_contact').each(function() {
        fn_default_contact_enable_switch.apply(this,[this.checked]);
    });
    
    $('.js_check_is_default_address').bootstrapSwitch({
        onText: "{$smarty.const.SW_ON}",
        offText: "{$smarty.const.SW_OFF}",
        handleWidth: '20px',
        labelWidth: '24px'
    });
    
    $('.js_check_is_default_address').on('click switchChange.bootstrapSwitch',function(){
        fn_default_address_enable_switch(this.checked);
    });
    $('.js_check_is_default_address').each(function() {
        fn_default_address_enable_switch.apply(this,[this.checked]);
    });
    
    $('.p_languages, .d_languages').bootstrapSwitch({
        onText: "{$smarty.const.SW_ON}",
        offText: "{$smarty.const.SW_OFF}",
        handleWidth: '20px',
        labelWidth: '24px',
    });
    
    $('.d_languages').on('switchChange.bootstrapSwitch',function(e, data){
        if(!$(this).parents('tr').find('.p_languages').is(':checked')){
            $(this).parents('tr').find('.p_languages').trigger('click');
        }
        $('.p_languages').bootstrapSwitch('disabled', false);
        $(this).parents('tr').find('.p_languages').bootstrapSwitch('disabled', false);
        $('.countries-table .default_check').html('');
        $('.lang_'+$(this).val()).find('.default_check').html('<span class="check"></span>');
        $('.lang_'+$(this).val()).removeClass('hide_row');
    });
    
    var fn_default_language_enable_switch = function(obj){
        if (obj.checked) {
          $(':radio[name=default_language][value='+$(obj).val()+']').prop('disabled', false);
          if ( $('input[name=default_language]:radio:checked').size() ==0 )
            $(':radio[name=default_language][value='+$(obj).val()+']').prop('checked', true);
          $('.lang_'+$(obj).val()).removeClass('hide_row');
          if(!$('.lang_'+$(obj).val()).hasClass('hide_row')){
              $('.lang_'+$(obj).val()).find('.lang_status').html('<span class="check"></span>');
          }
        }else {
          if ($('.p_languages').size() && $(':radio[name=default_language]:checked').val() == $(obj).val()){
            var _ch = $('.p_languages:checked')[0];
            $(':radio[name=default_language][value='+$(_ch).val()+']').prop('checked', true);
          }
          $(':radio[name=default_language][value='+$(obj).val()+']').prop('checked', false);
          $(':radio[name=default_language][value='+$(obj).val()+']').prop('disabled', true);
          $('.lang_'+$(obj).val()).addClass('hide_row');
        }    
    }
    
    $.each($('.p_languages'), function(i, e){
      if(!$(e).prop('checked')){
        $(':radio[name=default_language][value='+$(e).val()+']').prop('disabled', true);
      }
    });
    
    $('.p_languages').on('click switchChange.bootstrapSwitch',function(){
        fn_default_language_enable_switch(this);
    });   
    
    $('.p_currencies, .d_currencies').bootstrapSwitch({
        onText: "{$smarty.const.SW_ON}",
        offText: "{$smarty.const.SW_OFF}",
        handleWidth: '20px',
        labelWidth: '24px',
    });
    
    var fn_default_currency_enable_switch = function(obj){
        if (obj.checked) {
          $(':radio[name=default_currency][value='+$(obj).val()+']').prop('disabled', false);
          if ( $('input[name=default_currency]:radio:checked').size() ==0 )
            $(':radio[name=default_currency][value='+$(obj).val()+']').prop('checked', true);
          $('.currency_'+$(obj).val()).removeClass('hide_row');
            if($(obj).parents('tr').find('.js-custom-rate').is(':visible')){
                var js_custom = $(obj).parents('tr').find('.js-custom-rate').val();
            }else{
                var js_custom = $(obj).parents('tr').find('.form-group-sm label').text();
            }
            $('.currency_'+$(obj).val()).find('.currency_value label').text(js_custom);
            var js_rate = $(obj).parents('tr').find('td:last-child input').val() + $(obj).parents('tr').find('td:last-child select').val();
            $('.currency_'+$(obj).val()).find('.currency_margin').text(js_rate);
            //console.log($(obj));
        }else {
          if ($('.p_currencies').size() && $(':radio[name=default_currency]:checked').val() == $(obj).val()){
            var _ch = $('.p_currencies:checked')[0];
            $(':radio[name=default_currency][value='+$(_ch).val()+']').prop('checked', true);
          }
          $(':radio[name=default_currency][value='+$(obj).val()+']').prop('checked', false);
          $(':radio[name=default_currency][value='+$(obj).val()+']').prop('disabled', true);
          $('.currency_'+$(obj).val()).addClass('hide_row');
        }    
    }
    
    $.each($('.p_currencies'), function(i, e){
      if(!$(e).prop('checked')){
        $(':radio[name=default_currency][value='+$(e).val()+']').prop('disabled', true);
      }
    });    
    
    $('.p_currencies').on('click switchChange.bootstrapSwitch',function(){
        fn_default_currency_enable_switch(this);
    });
    
    $('#fileupload_top_left_watermark300').fileupload();
    $('#fileupload_top_left_watermark300').fileupload('option', {
        url: '{Yii::$app->urlManager->createUrl('platforms/file-manager-upload')}',
        maxFileSize: 2097152,
        maxNumberOfFiles: 100,
        downloadTemplateId: false,
        autoUpload: true,
        acceptFileTypes: /\.+/    }).bind('fileuploaddone', function (e, data) { 
            $('input[name=top_left_watermark300]').val(data.result);
            $('div.top_left_watermark300').addClass('upl');
            $('div.top_left_watermark300').children('div.upload-remove').show();
            $('div.top_left_watermark300').children('img').attr('src', '{DIR_WS_CATALOG}images/stamp/'+data.result).show();
        } );
        
    $('#fileupload_top_watermark300').fileupload();
    $('#fileupload_top_watermark300').fileupload('option', {
        url: '{Yii::$app->urlManager->createUrl('platforms/file-manager-upload')}',
        maxFileSize: 2097152,
        maxNumberOfFiles: 100,
        downloadTemplateId: false,
        autoUpload: true,
        acceptFileTypes: /\.+/    }).bind('fileuploaddone', function (e, data) {
            $('div.top_watermark300').addClass('upl');
            $('input[name=top_watermark300]').val(data.result);
            $('div.top_watermark300').children('div.upload-remove').show();
            $('div.top_watermark300').children('img').attr('src', '{DIR_WS_CATALOG}images/stamp/'+data.result).show();
        } );
        
    $('#fileupload_top_right_watermark300').fileupload();
    $('#fileupload_top_right_watermark300').fileupload('option', {
        url: '{Yii::$app->urlManager->createUrl('platforms/file-manager-upload')}',
        maxFileSize: 2097152,
        maxNumberOfFiles: 100,
        downloadTemplateId: false,
        autoUpload: true,
        acceptFileTypes: /\.+/    }).bind('fileuploaddone', function (e, data) { 
            $('div.top_right_watermark300').addClass('upl');
            $('input[name=top_right_watermark300]').val(data.result);
            $('div.top_right_watermark300').children('div.upload-remove').show();
            $('div.top_right_watermark300').children('img').attr('src', '{DIR_WS_CATALOG}images/stamp/'+data.result).show();
        } );
    
    $('#fileupload_left_watermark300').fileupload();
    $('#fileupload_left_watermark300').fileupload('option', {
        url: '{Yii::$app->urlManager->createUrl('platforms/file-manager-upload')}',
        maxFileSize: 2097152,
        maxNumberOfFiles: 100,
        downloadTemplateId: false,
        autoUpload: true,
        acceptFileTypes: /\.+/    }).bind('fileuploaddone', function (e, data) { 
            $('div.left_watermark300').addClass('upl');
            $('input[name=left_watermark300]').val(data.result);
            $('div.left_watermark300').children('div.upload-remove').show();
            $('div.left_watermark300').children('img').attr('src', '{DIR_WS_CATALOG}images/stamp/'+data.result).show();
        } );
        
    $('#fileupload_watermark300').fileupload();
    $('#fileupload_watermark300').fileupload('option', {
        url: '{Yii::$app->urlManager->createUrl('platforms/file-manager-upload')}',
        maxFileSize: 2097152,
        maxNumberOfFiles: 100,
        downloadTemplateId: false,
        autoUpload: true,
        acceptFileTypes: /\.+/    }).bind('fileuploaddone', function (e, data) { 
            $('div.watermark300').addClass('upl');
            $('input[name=watermark300]').val(data.result);
            $('div.watermark300').children('div.upload-remove').show();
            $('div.watermark300').children('img').attr('src', '{DIR_WS_CATALOG}images/stamp/'+data.result).show();
        } );
        
    $('#fileupload_right_watermark300').fileupload();
    $('#fileupload_right_watermark300').fileupload('option', {
        url: '{Yii::$app->urlManager->createUrl('platforms/file-manager-upload')}',
        maxFileSize: 2097152,
        maxNumberOfFiles: 100,
        downloadTemplateId: false,
        autoUpload: true,
        acceptFileTypes: /\.+/    }).bind('fileuploaddone', function (e, data) { 
            $('div.right_watermark300').addClass('upl');
            $('input[name=right_watermark300]').val(data.result);
            $('div.right_watermark300').children('div.upload-remove').show();
            $('div.right_watermark300').children('img').attr('src', '{DIR_WS_CATALOG}images/stamp/'+data.result).show();
        } );
    
    $('#fileupload_bottom_left_watermark300').fileupload();
    $('#fileupload_bottom_left_watermark300').fileupload('option', {
        url: '{Yii::$app->urlManager->createUrl('platforms/file-manager-upload')}',
        maxFileSize: 2097152,
        maxNumberOfFiles: 100,
        downloadTemplateId: false,
        autoUpload: true,
        acceptFileTypes: /\.+/    }).bind('fileuploaddone', function (e, data) { 
            $('div.bottom_left_watermark300').addClass('upl');
            $('input[name=bottom_left_watermark300]').val(data.result);
            $('div.bottom_left_watermark300').children('div.upload-remove').show();
            $('div.bottom_left_watermark300').children('img').attr('src', '{DIR_WS_CATALOG}images/stamp/'+data.result).show();
        } );
        
    $('#fileupload_bottom_watermark300').fileupload();
    $('#fileupload_bottom_watermark300').fileupload('option', {
        url: '{Yii::$app->urlManager->createUrl('platforms/file-manager-upload')}',
        maxFileSize: 2097152,
        maxNumberOfFiles: 100,
        downloadTemplateId: false,
        autoUpload: true,
        acceptFileTypes: /\.+/    }).bind('fileuploaddone', function (e, data) { 
            $('div.bottom_watermark300').addClass('upl');
            $('input[name=bottom_watermark300]').val(data.result);
            $('div.bottom_watermark300').children('div.upload-remove').show();
            $('div.bottom_watermark300').children('img').attr('src', '{DIR_WS_CATALOG}images/stamp/'+data.result).show();
        } );
        
    $('#fileupload_bottom_right_watermark300').fileupload();
    $('#fileupload_bottom_right_watermark300').fileupload('option', {
        url: '{Yii::$app->urlManager->createUrl('platforms/file-manager-upload')}',
        maxFileSize: 2097152,
        maxNumberOfFiles: 100,
        downloadTemplateId: false,
        autoUpload: true,
        acceptFileTypes: /\.+/    }).bind('fileuploaddone', function (e, data) { 
            $('div.bottom_right_watermark300').addClass('upl');
            $('input[name=bottom_right_watermark300]').val(data.result);
            $('div.bottom_right_watermark300').children('div.upload-remove').show();
            $('div.bottom_right_watermark300').children('img').attr('src', '{DIR_WS_CATALOG}images/stamp/'+data.result).show();
        } );
    
    $('#fileupload_top_left_watermark170').fileupload();
    $('#fileupload_top_left_watermark170').fileupload('option', {
        url: '{Yii::$app->urlManager->createUrl('platforms/file-manager-upload')}',
        maxFileSize: 2097152,
        maxNumberOfFiles: 100,
        downloadTemplateId: false,
        autoUpload: true,
        acceptFileTypes: /\.+/    }).bind('fileuploaddone', function (e, data) { 
            $('div.top_left_watermark170').addClass('upl');
            $('input[name=top_left_watermark170]').val(data.result);
            $('div.top_left_watermark170').children('div.upload-remove').show();
            $('div.top_left_watermark170').children('img').attr('src', '{DIR_WS_CATALOG}images/stamp/'+data.result).show();
        } );
        
    $('#fileupload_top_watermark170').fileupload();
    $('#fileupload_top_watermark170').fileupload('option', {
        url: '{Yii::$app->urlManager->createUrl('platforms/file-manager-upload')}',
        maxFileSize: 2097152,
        maxNumberOfFiles: 100,
        downloadTemplateId: false,
        autoUpload: true,
        acceptFileTypes: /\.+/    }).bind('fileuploaddone', function (e, data) { 
            $('div.top_watermark170').addClass('upl');
            $('input[name=top_watermark170]').val(data.result);
            $('div.top_watermark170').children('div.upload-remove').show();
            $('div.top_watermark170').children('img').attr('src', '{DIR_WS_CATALOG}images/stamp/'+data.result).show();
        } );
        
    $('#fileupload_top_right_watermark170').fileupload();
    $('#fileupload_top_right_watermark170').fileupload('option', {
        url: '{Yii::$app->urlManager->createUrl('platforms/file-manager-upload')}',
        maxFileSize: 2097152,
        maxNumberOfFiles: 100,
        downloadTemplateId: false,
        autoUpload: true,
        acceptFileTypes: /\.+/    }).bind('fileuploaddone', function (e, data) { 
            $('div.top_right_watermark170').addClass('upl');
            $('input[name=top_right_watermark170]').val(data.result);
            $('div.top_right_watermark170').children('div.upload-remove').show();
            $('div.top_right_watermark170').children('img').attr('src', '{DIR_WS_CATALOG}images/stamp/'+data.result).show();
        } );
    
    $('#fileupload_left_watermark170').fileupload();
    $('#fileupload_left_watermark170').fileupload('option', {
        url: '{Yii::$app->urlManager->createUrl('platforms/file-manager-upload')}',
        maxFileSize: 2097152,
        maxNumberOfFiles: 100,
        downloadTemplateId: false,
        autoUpload: true,
        acceptFileTypes: /\.+/    }).bind('fileuploaddone', function (e, data) { 
            $('div.left_watermark170').addClass('upl');
            $('input[name=left_watermark170]').val(data.result);
            $('div.left_watermark170').children('div.upload-remove').show();
            $('div.left_watermark170').children('img').attr('src', '{DIR_WS_CATALOG}images/stamp/'+data.result).show();
        } );
        
    $('#fileupload_watermark170').fileupload();
    $('#fileupload_watermark170').fileupload('option', {
        url: '{Yii::$app->urlManager->createUrl('platforms/file-manager-upload')}',
        maxFileSize: 2097152,
        maxNumberOfFiles: 100,
        downloadTemplateId: false,
        autoUpload: true,
        acceptFileTypes: /\.+/    }).bind('fileuploaddone', function (e, data) { 
            $('div.watermark170').addClass('upl');
            $('input[name=watermark170]').val(data.result);
            $('div.watermark170').children('div.upload-remove').show();
            $('div.watermark170').children('img').attr('src', '{DIR_WS_CATALOG}images/stamp/'+data.result).show();
        } );
        
    $('#fileupload_right_watermark170').fileupload();
    $('#fileupload_right_watermark170').fileupload('option', {
        url: '{Yii::$app->urlManager->createUrl('platforms/file-manager-upload')}',
        maxFileSize: 2097152,
        maxNumberOfFiles: 100,
        downloadTemplateId: false,
        autoUpload: true,
        acceptFileTypes: /\.+/    }).bind('fileuploaddone', function (e, data) { 
            $('div.right_watermark170').addClass('upl');
            $('input[name=right_watermark170]').val(data.result);
            $('div.right_watermark170').children('div.upload-remove').show();
            $('div.right_watermark170').children('img').attr('src', '{DIR_WS_CATALOG}images/stamp/'+data.result).show();
        } );
    
    $('#fileupload_bottom_left_watermark170').fileupload();
    $('#fileupload_bottom_left_watermark170').fileupload('option', {
        url: '{Yii::$app->urlManager->createUrl('platforms/file-manager-upload')}',
        maxFileSize: 2097152,
        maxNumberOfFiles: 100,
        downloadTemplateId: false,
        autoUpload: true,
        acceptFileTypes: /\.+/    }).bind('fileuploaddone', function (e, data) { 
            $('div.bottom_left_watermark170').addClass('upl');
            $('input[name=bottom_left_watermark170]').val(data.result);
            $('div.bottom_left_watermark170').children('div.upload-remove').show();
            $('div.bottom_left_watermark170').children('img').attr('src', '{DIR_WS_CATALOG}images/stamp/'+data.result).show();
        } );
        
    $('#fileupload_bottom_watermark170').fileupload();
    $('#fileupload_bottom_watermark170').fileupload('option', {
        url: '{Yii::$app->urlManager->createUrl('platforms/file-manager-upload')}',
        maxFileSize: 2097152,
        maxNumberOfFiles: 100,
        downloadTemplateId: false,
        autoUpload: true,
        acceptFileTypes: /\.+/    }).bind('fileuploaddone', function (e, data) { 
            $('div.bottom_watermark170').addClass('upl');
            $('input[name=bottom_watermark170]').val(data.result);
            $('div.bottom_watermark170').children('div.upload-remove').show();
            $('div.bottom_watermark170').children('img').attr('src', '{DIR_WS_CATALOG}images/stamp/'+data.result).show();
        } );
        
    $('#fileupload_bottom_right_watermark170').fileupload();
    $('#fileupload_bottom_right_watermark170').fileupload('option', {
        url: '{Yii::$app->urlManager->createUrl('platforms/file-manager-upload')}',
        maxFileSize: 2097152,
        maxNumberOfFiles: 100,
        downloadTemplateId: false,
        autoUpload: true,
        acceptFileTypes: /\.+/    }).bind('fileuploaddone', function (e, data) { 
            $('div.bottom_right_watermark170').addClass('upl');
            $('input[name=bottom_right_watermark170]').val(data.result);
            $('div.bottom_right_watermark170').children('div.upload-remove').show();
            $('div.bottom_right_watermark170').children('img').attr('src', '{DIR_WS_CATALOG}images/stamp/'+data.result).show();
        } );
    
    $('#fileupload_top_left_watermark30').fileupload();
    $('#fileupload_top_left_watermark30').fileupload('option', {
        url: '{Yii::$app->urlManager->createUrl('platforms/file-manager-upload')}',
        maxFileSize: 2097152,
        maxNumberOfFiles: 100,
        downloadTemplateId: false,
        autoUpload: true,
        acceptFileTypes: /\.+/    }).bind('fileuploaddone', function (e, data) { 
            $('div.top_left_watermark30').addClass('upl');
            $('input[name=top_left_watermark30]').val(data.result);
            $('div.top_left_watermark30').children('div.upload-remove').show();
            $('div.top_left_watermark30').children('img').attr('src', '{DIR_WS_CATALOG}images/stamp/'+data.result).show();
        } );
        
    $('#fileupload_top_watermark30').fileupload();
    $('#fileupload_top_watermark30').fileupload('option', {
        url: '{Yii::$app->urlManager->createUrl('platforms/file-manager-upload')}',
        maxFileSize: 2097152,
        maxNumberOfFiles: 100,
        downloadTemplateId: false,
        autoUpload: true,
        acceptFileTypes: /\.+/    }).bind('fileuploaddone', function (e, data) { 
            $('div.top_watermark30').addClass('upl');
            $('input[name=top_watermark30]').val(data.result);
            $('div.top_watermark30').children('div.upload-remove').show();
            $('div.top_watermark30').children('img').attr('src', '{DIR_WS_CATALOG}images/stamp/'+data.result).show();
        } );
        
    $('#fileupload_top_right_watermark30').fileupload();
    $('#fileupload_top_right_watermark30').fileupload('option', {
        url: '{Yii::$app->urlManager->createUrl('platforms/file-manager-upload')}',
        maxFileSize: 2097152,
        maxNumberOfFiles: 100,
        downloadTemplateId: false,
        autoUpload: true,
        acceptFileTypes: /\.+/    }).bind('fileuploaddone', function (e, data) { 
            $('div.top_right_watermark30').addClass('upl');
            $('input[name=top_right_watermark30]').val(data.result);
            $('div.top_right_watermark30').children('div.upload-remove').show();
            $('div.top_right_watermark30').children('img').attr('src', '{DIR_WS_CATALOG}images/stamp/'+data.result).show();
        } );
    
    $('#fileupload_left_watermark30').fileupload();
    $('#fileupload_left_watermark30').fileupload('option', {
        url: '{Yii::$app->urlManager->createUrl('platforms/file-manager-upload')}',
        maxFileSize: 2097152,
        maxNumberOfFiles: 100,
        downloadTemplateId: false,
        autoUpload: true,
        acceptFileTypes: /\.+/    }).bind('fileuploaddone', function (e, data) { 
            $('div.left_watermark30').addClass('upl');
            $('input[name=left_watermark30]').val(data.result);
            $('div.left_watermark30').children('div.upload-remove').show();
            $('div.left_watermark30').children('img').attr('src', '{DIR_WS_CATALOG}images/stamp/'+data.result).show();
        } );
        
    $('#fileupload_watermark30').fileupload();
    $('#fileupload_watermark30').fileupload('option', {
        url: '{Yii::$app->urlManager->createUrl('platforms/file-manager-upload')}',
        maxFileSize: 2097152,
        maxNumberOfFiles: 100,
        downloadTemplateId: false,
        autoUpload: true,
        acceptFileTypes: /\.+/    }).bind('fileuploaddone', function (e, data) { 
            $('div.watermark30').addClass('upl');
            $('input[name=watermark30]').val(data.result);
            $('div.watermark30').children('div.upload-remove').show();
            $('div.watermark30').children('img').attr('src', '{DIR_WS_CATALOG}images/stamp/'+data.result).show();
        } );
        
    $('#fileupload_right_watermark30').fileupload();
    $('#fileupload_right_watermark30').fileupload('option', {
        url: '{Yii::$app->urlManager->createUrl('platforms/file-manager-upload')}',
        maxFileSize: 2097152,
        maxNumberOfFiles: 100,
        downloadTemplateId: false,
        autoUpload: true,
        acceptFileTypes: /\.+/    }).bind('fileuploaddone', function (e, data) { 
            $('div.right_watermark30').addClass('upl');
            $('input[name=right_watermark30]').val(data.result);
            $('div.right_watermark30').children('div.upload-remove').show();
            $('div.right_watermark30').children('img').attr('src', '{DIR_WS_CATALOG}images/stamp/'+data.result).show();
        } );
    
    $('#fileupload_bottom_left_watermark30').fileupload();
    $('#fileupload_bottom_left_watermark30').fileupload('option', {
        url: '{Yii::$app->urlManager->createUrl('platforms/file-manager-upload')}',
        maxFileSize: 2097152,
        maxNumberOfFiles: 100,
        downloadTemplateId: false,
        autoUpload: true,
        acceptFileTypes: /\.+/    }).bind('fileuploaddone', function (e, data) { 
            $('div.bottom_left_watermark30').addClass('upl');
            $('input[name=bottom_left_watermark30]').val(data.result);
            $('div.bottom_left_watermark30').children('div.upload-remove').show();
            $('div.bottom_left_watermark30').children('img').attr('src', '{DIR_WS_CATALOG}images/stamp/'+data.result).show();
        } );
        
    $('#fileupload_bottom_watermark30').fileupload();
    $('#fileupload_bottom_watermark30').fileupload('option', {
        url: '{Yii::$app->urlManager->createUrl('platforms/file-manager-upload')}',
        maxFileSize: 2097152,
        maxNumberOfFiles: 100,
        downloadTemplateId: false,
        autoUpload: true,
        acceptFileTypes: /\.+/    }).bind('fileuploaddone', function (e, data) { 
            $('div.bottom_watermark30').addClass('upl');
            $('input[name=bottom_watermark30]').val(data.result);
            $('div.bottom_watermark30').children('div.upload-remove').show();
            $('div.bottom_watermark30').children('img').attr('src', '{DIR_WS_CATALOG}images/stamp/'+data.result).show();
        } );
        
    $('#fileupload_bottom_right_watermark30').fileupload();
    $('#fileupload_bottom_right_watermark30').fileupload('option', {
        url: '{Yii::$app->urlManager->createUrl('platforms/file-manager-upload')}',
        maxFileSize: 2097152,
        maxNumberOfFiles: 100,
        downloadTemplateId: false,
        autoUpload: true,
        acceptFileTypes: /\.+/    }).bind('fileuploaddone', function (e, data) { 
            $('div.bottom_right_watermark30').addClass('upl');
            $('input[name=bottom_right_watermark30]').val(data.result);
            $('div.bottom_right_watermark30').children('div.upload-remove').show();
            $('div.bottom_right_watermark30').children('img').attr('src', '{DIR_WS_CATALOG}images/stamp/'+data.result).show();
        } );
        
        $('.js-currency-table').on('click','.js-use_default',function(){
        var $refControl = $(this).parents('td').find('.js-custom-rate');
        if ( this.checked ){
            $refControl.hide();
            $('.currency_'+$(this).data('code')).find('.currency_value').text($(this).parent('label').text());
        }else{
            $refControl.val($refControl.attr('data-default-value'));
            $refControl.show();
            $refControl.on('keyup keydown', function(){
                $('.currency_'+$(this).data('code')).find('.currency_value').text($(this).val());
            })
            $refControl.on('focusout', function(){
                if($(this).val() == $(this).attr('data-default-value')){
                    $(this).hide();
                    $(this).prev().find('.js-use_default').prop('checked',true);
                }
            })
        }
    });
    $('.js-currency-table').find('.js-use_default:checked').each(function(){
        $(this).parents('tr').find('.js-custom-rate').hide();
    });


    $('.js-url_status').bootstrapSwitch({
        onText: "{$smarty.const.SW_ON}",
        offText: "{$smarty.const.SW_OFF}",
        handleWidth: '20px',
        labelWidth: '24px'
    });
    $('.js-platform-urls').on('add_row',function(){
        var skelHtml = $(this).find('tfoot').html();
        var $body = $(this).find('tbody');
        var counter = parseInt($body.attr('data-rows-count'),10)+1;
        $body.attr('data-rows-count',counter);
        skelHtml = skelHtml.replace(/_unhide_/g,'',skelHtml);
        skelHtml = skelHtml.replace(/%idx%/g, counter,skelHtml);
        $body.append(skelHtml);
        $body.find('.js-url_status_skel').removeClass('js-url_status_skel').addClass('js-url_status').bootstrapSwitch({
            onText: "{$smarty.const.SW_ON}",
            offText: "{$smarty.const.SW_OFF}",
            handleWidth: '20px',
            labelWidth: '24px'
        });
    });
    $('.js-platform-urls').on('click', '.js-remove-platform-url',function(event){
        $(event.target).parents('tr').remove();
    });

    $('.js-add-platform-url').on('click',function(){
        $('.js-platform-urls').trigger('add_row');
    });
    
    $('.js-platform-locations').on('add_row',function(){
        var skelHtml = $(this).find('tfoot').html();
        var $body = $(this).find('tbody');
        var counter = parseInt($body.attr('data-rows-count'),10)+1;
        $body.attr('data-rows-count',counter);
        skelHtml = skelHtml.replace(/_unhide_/g,'',skelHtml);
        skelHtml = skelHtml.replace(/%idx%/g, counter,skelHtml);
        $body.append(skelHtml);
    });
    $('.js-platform-locations').on('click', '.js-remove-platform-location',function(event){
        $(event.target).parents('tr').remove();
    });
    $('.js-add-platform-location').on('click',function(){
        $('.js-platform-locations').trigger('add_row');
    });
    
    show_platform_boxes();
});
</script>
<link href="{$app->request->baseUrl}/plugins/jquery-ui/multiple-select.css" rel="stylesheet" type="text/css" />
    <script type="text/javascript" src="{$app->request->baseUrl}/plugins/jquery-ui/multiple-select.js"></script>
    <script type="text/javascript">
        $(document).ready(function(){
            function muliCheckedSel(popup){
                var items = [];
                $('.multiselect option:selected', popup).each(function(){
                    if($(this).text() != ''){
                        items.push($(this).text());
                    }
                });
                var result = items.join(', ');
                popup.parents('.wl-td').find('.geo_zones').text(result);
            }
            muliCheckedSel($('#geo_zones'));
            muliCheckedSel($('#countries'));
            function muliChecked(popup){
                var items1 = [];

                $('.ms-drop .selected', popup).not('.group, .ms-select-all').each(function(){
                    if($(this).text() != ''){
                        items1.push($(this).text());
                    }
                });
                var result1 = items1.join(', ');
                popup.parents('.wl-td').find('.geo_zones').text(result1);
            }

            $('.popup_zones').off().on('click',function(){
                var popup = $($(this).attr('href'));
                popup.removeClass('hide_popup');
                $('#content, .content-container').css({ 'position': 'relative', 'z-index': '100'});
                var prev_zones = {};
                var prev_countries = {};
                var prev_zones = $('select[name="zones[]"]').val();
                var prev_countries = $('select[name="countries[]"]').val();

                var height = function(){
                    var h = $(window).height() - $('.popup-heading', popup).height() - $('.popup-buttons', popup).height() - 120;
                    $('.popup-content', popup).css('max-height', h);
                };
                height();
                $(window).on('resize', height);

                $('.pop-up-close-page, .apply-popup', popup).off().on('click', function(){
                    popup.addClass('hide_popup');
                    $(window).off('resize', height);
                    $('#content, .content-container').css({ 'position': '', 'z-index': ''});
                    return false;
                });

                $('.cancel-popup', popup).off().on('click', function(){
                    if(!$.isEmptyObject(prev_zones)){
                        if($('.geo_zones_block',popup).length > 0) {
                            $('select[name="zones[]"]').multipleSelect('setSelects', prev_zones);
                        }
                    }else{
                        if($('.geo_zones_block',popup).length > 0) {
                            $('select[name="zones[]"]').multipleSelect('setSelects', '');
                        }
                    }
                    if(!$.isEmptyObject(prev_countries)){
                        if ($('.countries_block', popup).length > 0) {
                            $('select[name="countries[]"]').multipleSelect('setSelects', prev_countries);
                        }
                    }else{
                        if ($('.countries_block', popup).length > 0) {
                            $('select[name="countries[]"]').multipleSelect('setSelects', '');
                        }
                    }
                    popup.addClass('hide_popup');
                    $(window).off('resize', height);
                    $('#content, .content-container').css({ 'position': '', 'z-index': ''});
                    muliChecked(popup);
                    return false;
                })
                $('.ui-multiselect',popup).remove();
                if(!$('.ms-parent',popup).length){
                    $('.multiselect', popup).multipleSelect({
                        filter: true,
                        place:'{$smarty.const.TEXT_SEARCH_ITEMS}',
                        isOpen: true,
                        onClick: function (element) {
                            muliChecked(popup);
                        },
                        onOptgroupClick:function(element){
                            muliChecked(popup);
                        },
                        onCheckAll: function () {
                            muliChecked(popup);
                        },
                        close: function (event, ui) {
                            $('.multiselect', popup).multipleSelect("open");
                        }
                    });
                }
                return false;
            })
            $('.popup_lang').off().on('click',function(){
                var popup = $($(this).attr('href'));
                popup.removeClass('hide_popup');
                var popup_class = $('.'+$(this).data('class'));
                $('#content, .content-container').css({ 'position': 'relative', 'z-index': '100'});

                var height = function(){
                    var h = $(window).height() - $('.popup-heading', popup).height() - $('.popup-buttons', popup).height() - 120;
                    $('.popup-content', popup).css('max-height', h);
                };
                height();
                $(window).on('resize', height);
                var plang = {};
                $('.popup-content .countries-table tr').not(':first').each(function(){
                    var lang_code = $(this).find('.p_languages').val();
                    var lang_checked = $(this).find('.p_languages').prop('checked');
                    var lang_default = $(this).find('.d_languages').prop('checked');
                    plang[lang_code] = {
                        'p_languages': lang_checked,
                        'd_languages': lang_default
                    }
                })

                var currncy_array = {};
                $('.popup-content .currency-table tr').not(':first').each(function(){
                    var _cur = $(this).find('.p_currencies').val();
                    var _cur_checked = $(this).find('.p_currencies').prop('checked');
                    var d_cur = $(this).find('.d_currencies').prop('checked');
                    var default_cur = $(this).find('.js-use_default').prop('checked');
                    var custom_cur = $(this).find('.js-custom-rate').data('default-value');
                    var rate_cur = $(this).find('.js-rate-margin').val();
                    var type_cur = $(this).find('.margin_type').val();
                    currncy_array[_cur] = {
                        'p_currencies':_cur_checked,
                        'd_currencies':d_cur,
                        'js-use_default':default_cur,
                        'js-custom-rate':custom_cur,
                        'js-rate-margin':rate_cur,
                        'margin_type':type_cur
                    };
                })
                popup.find('.cancel-popup').off().on('click', function(){
                    if($('.countries-table',popup).length > 0) {
                        $.each(plang, function (key, value) {
                            $.each(value, function (lang_key, lang_value) {
                                $('tr.popup_lang_' + key).find('.' + lang_key).bootstrapSwitch('state', lang_value);
                            })
                        })
                    }
                    if($('.currency-table',popup).length > 0) {
                        $.each(currncy_array, function (key, value) {
                            $.each(value, function (k, l) {
                                if (k == 'p_currencies' || k == 'd_currencies') {
                                    $('tr.popup_cur_' + key).find('.' + k).bootstrapSwitch('state', l);
                                } else if (k == 'js-use_default') {
                                    $('tr.popup_cur_' + key).find('.' + k).prop('checked', l);
                                    if (l == true) {
                                        $('tr.popup_cur_' + key).find('.js-custom-rate').hide();
                                    } else {
                                        $('tr.popup_cur_' + key).find('.js-custom-rate').show();
                                    }
                                }else if(k == 'js-rate-margin'){
                                    $('.currency_'+key).find('.currency_margin').text(l);
                                    $('tr.popup_cur_' + key).find('.' + k).val(l);
                                }else if(k == 'margin_type'){
                                    $('.currency_'+key).find('.currency_margin').append(l);
                                    $('tr.popup_cur_' + key).find('.' + k).val(l);
                                }else {
                                    $('tr.popup_cur_' + key).find('.' + k).val(l);

                                }
                            })
                        })
                    }
                    popup.addClass('hide_popup');
                    $(window).off('resize', height);
                    $('#content, .content-container').css({ 'position': '', 'z-index': ''});
                    return false;
                })

                $('.pop-up-close-page, .apply-popup', popup).off().on('click', function(){
                    popup.addClass('hide_popup');
                    $(window).off('resize', height);
                    $('#content, .content-container').css({ 'position': '', 'z-index': ''});
                    return false;
                });

                return false;
            })
        })
    </script>
</div>
<!-- /Page Content -->
