{use class="yii\helpers\Html"}
<div id="affiliate_management_data">
    {Html::beginForm($url, 'post', ['name' => 'affiliate_edit', 'id' => 'affiliate_edit'])}
    <div class="box-wrap">
        <div class="create-or-wrap after create-cus-wrap">
            <div class="cbox-left">
                <div class="widget box box-no-shadow">
                    <div class="widget-header widget-header-personal"><h4>{$smarty.const.TEXT_AFFILIATE_PERSONAL_DETAILS}</h4></div>
                    <div class="widget-content">
                        <div class="w-line-row w-line-row-1">
                            <div class="wl-td">
                                <label>{field_label const="ENTRY_FIRST_NAME" configuration="ACCOUNT_FIRSTNAME"}</label>
                                {Html::input('text', 'affiliate_firstname', $model->affiliate_firstname, ['class' => 'form-control', 'required' => false])}
                            </div>
                        </div>
                        <div class="w-line-row w-line-row-1">
                            <div class="wl-td">
                                <label>{field_label const="ENTRY_LAST_NAME" configuration="ACCOUNT_LASTNAME"}</label>
                                {Html::input('text', 'affiliate_lastname', $model->affiliate_lastname, ['class' => 'form-control', 'required' => false])}
                            </div>
                        </div>
                        <div class="w-line-row w-line-row-1">
                            <div class="wl-td">
                                <label>{field_label const="ENTRY_EMAIL_ADDRESS" required_text="*"}</label>
                                {Html::input('text', 'affiliate_email_address', $model->affiliate_email_address, ['class' => 'form-control', 'required' => false])}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="widget box box-no-shadow">
                    <div class="widget-header widget-header-personal"><h4>{$smarty.const.TEXT_AFFILIATE_COMPANY_DETAILS}</h4></div>
                    <div class="widget-content">
                        <div class="w-line-row w-line-row-1">
                            <div class="wl-td">
                                <label>{field_label const="ENTRY_COMPANY" configuration="ACCOUNT_COMPANY"}</label>
                                {Html::input('text', 'affiliate_company', $model->affiliate_company, ['class' => 'form-control', 'required' => false])}
                            </div>
                        </div>
                        <div class="w-line-row w-line-row-1">
                            <div class="wl-td">
                                <label>{field_label const="ENTRY_BUSINESS" configuration="ACCOUNT_COMPANY_VAT"}</label>
                                {Html::input('text', 'affiliate_company_taxid', $model->affiliate_company_taxid, ['class' => 'form-control', 'required' => false])}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="widget box box-no-shadow">
                    <div class="widget-header widget-header-credit"><h4>{$smarty.const.TEXT_AFFILIATE_GET_MONEY}</h4></div>
                    <div class="widget-content">
                        {if AFFILIATE_USE_CHECK == 'true'}
                        <div class="w-line-row w-line-row-1">
                            <div class="wl-td">
                                <label>{$smarty.const.TEXT_AFFILIATE_CHECK}:</label>
                                {Html::input('text', 'affiliate_payment_check', $model->affiliate_payment_check, ['class' => 'form-control', 'required' => false])}
                            </div>
                        </div>
                        {/if}
                        {if AFFILIATE_USE_PAYPAL == 'true'}
                        <div class="w-line-row w-line-row-1">
                            <div class="wl-td">
                                <label>{$smarty.const.TEXT_AFFILIATE_PAYPAL}:</label>
                                {Html::input('text', 'affiliate_payment_paypal', $model->affiliate_payment_paypal, ['class' => 'form-control', 'required' => false])}
                            </div>
                        </div>
                        {/if}
                        {if AFFILIATE_USE_BANK == 'true'}
                        <div class="w-line-row w-line-row-1">
                            <div class="wl-td">
                                <label>{$smarty.const.TEXT_AFFILIATE_BANK}:</label>
                                {Html::input('text', 'affiliate_payment_bank_name', $model->affiliate_payment_bank_name, ['class' => 'form-control', 'required' => false])}
                            </div>
                        </div>
                        <div class="w-line-row w-line-row-1">
                            <div class="wl-td">
                                <label>{$smarty.const.TEXT_AFFILIATE_BRANCH}:</label>
                                {Html::input('text', 'affiliate_payment_bank_branch_number', $model->affiliate_payment_bank_branch_number, ['class' => 'form-control', 'required' => false])}
                            </div>
                        </div>
                        <div class="w-line-row w-line-row-1">
                            <div class="wl-td">
                                <label>{$smarty.const.TEXT_AFFILIATE_SWIFT}:</label>
                                {Html::input('text', 'affiliate_payment_bank_swift_code', $model->affiliate_payment_bank_swift_code, ['class' => 'form-control', 'required' => false])}
                            </div>
                        </div>
                        <div class="w-line-row w-line-row-1">
                            <div class="wl-td">
                                <label>{$smarty.const.TEXT_AFFILIATE_NAME}:</label>
                                {Html::input('text', 'affiliate_payment_bank_account_name', $model->affiliate_payment_bank_account_name, ['class' => 'form-control', 'required' => false])}
                            </div>
                        </div>
                        <div class="w-line-row w-line-row-1">
                            <div class="wl-td">
                                <label>{$smarty.const.TEXT_AFFILIATE_NUMBER}:</label>
                                {Html::input('text', 'affiliate_payment_bank_account_number', $model->affiliate_payment_bank_account_number, ['class' => 'form-control', 'required' => false])}
                            </div>
                        </div>
                        {/if}
                    </div>
                </div>
            </div>
            <div class="cbox-right">
                <div class="widget box box-no-shadow" style="min-height: 211px;">
                    <div class="widget-header widget-header-address"><h4>{$smarty.const.TEXT_AFFILIATE_ADDRESS}</h4></div>
                    <div class="widget-content">
                        <div class="w-line-row w-line-row-2">
                            <div class="wl-td">
                                <label>{$smarty.const.ENTRY_STREET_ADDRESS}</label>{Html::input('text', 'affiliate_street_address', $model->affiliate_street_address, ['class' => 'form-control'])}
                            </div>
                            <div class="wl-td">
                                <label>{$smarty.const.ENTRY_SUBURB}</label>{Html::input('text', 'affiliate_suburb', $model->affiliate_suburb, ['class' => 'form-control'])}
                            </div>
                        </div>
                        <div class="w-line-row w-line-row-2">
                            <div class="wl-td">
                                <label>{$smarty.const.ENTRY_CITY}</label>{Html::input('text', 'affiliate_city', $model->affiliate_city, ['class' => 'form-control'])}
                            </div>
                            <div class="wl-td">
                                <label>{field_label const="ENTRY_STATE" configuration="ACCOUNT_STATE"}</label>
                                <div class="f_td_state">
                                    {Html::input('text', 'affiliate_state', $model->affiliate_state, ['class' => 'form-control', 'id' => "selectState"])}
                                </div>
                            </div>
                        </div>
                        <div class="w-line-row w-line-row-2">
                            <div class="wl-td">
                                <label>{$smarty.const.ENTRY_POST_CODE}</label>{Html::input('text', 'affiliate_postcode', $model->affiliate_postcode, ['class' => 'form-control'])}
                            </div>
                            <div class="wl-td">
                                <label>{$smarty.const.ENTRY_COUNTRY}<span class="fieldRequired">*</span></label>{Html::dropDownList('affiliate_country_id', $model->affiliate_country_id, \common\helpers\Country::new_get_countries('', false), ['class' => 'form-control', 'id' => "selectCountry", 'required' => true])}
                            </div>
                        </div>
                    </div>
                    <div class="w-line-row w-line-row-1 w-line-row-req w-line-row-abs">
                              <span style="color: #f2353c; margin: 22px 0 0; display: block;">{$smarty.const.ENTRY_REQUIRED_FIELDS}</span>
                        </div>
                </div>
                <div class="widget box box-no-shadow">
                    <div class="widget-header widget-header-contact"><h4>{$smarty.const.TEXT_AFFILIATE_CONTACT}</h4></div>
                    <div class="widget-content">
                        <div class="w-line-row w-line-row-1">
                            <div class="wl-td">
                                <label>{field_label const="ENTRY_TELEPHONE_NUMBER" configuration="ACCOUNT_TELEPHONE"}</label>
                                {Html::input('text', 'affiliate_telephone', $model->affiliate_telephone, ['class' => 'form-control', 'required' => false])}
                            </div>
                        </div>
                        <div class="w-line-row w-line-row-1">
                            <div class="wl-td">
                                <label>{field_label const="ENTRY_LANDLINE" configuration="ACCOUNT_LANDLINE"}</label>
                                {Html::input('text', 'affiliate_fax', $model->affiliate_fax, ['class' => 'form-control', 'required' => false])}
                            </div>
                        </div>
                        <div class="w-line-row w-line-row-1">
                            <div class="wl-td">
                                <label>{$smarty.const.TEXT_AFFILIATE_HOMEPAGE}</label>
                                {Html::input('text', 'affiliate_homepage', $model->affiliate_homepage, ['class' => 'form-control', 'required' => false])}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="widget box box-no-shadow">
                    <div class="widget-header widget-header-contact"><h4>{$smarty.const.IMAGE_DETAILS}</h4></div>
                    <div class="widget-content">
                        <div class="w-line-row w-line-row-1">
                            <div class="wl-td">
                                <label>{$smarty.const.TEXT_AFFILIATE_ID}:</label>
                                {$model->affiliate_id}
                            </div>
                        </div>
                        <div class="w-line-row w-line-row-1">
                            <div class="wl-td">
                                <label>{$smarty.const.TEXT_AFFILIATE_URL}:</label>
                                {$platformUrl}
                            </div>
                        </div>
                        {if $model->platform_id > 0}
                        <div class="w-line-row w-line-row-1">
                            <div class="wl-td">
                                <label>{$smarty.const.BOX_REPORTS_SALES}:</label>
                                <a href="{$app->urlManager->createUrl('sales_statistics/')}?platforms[]={$model->platform_id}">{$smarty.const.TEXT_AFFILIATE_URL}</a>
                            </div>
                        </div>
                        {/if}
                    </div>
                </div>
            </div>
        </div>
    </div>
                            
                            
    <div class="btn-bar">
        <div class="btn-left"><a href="javascript:void(0)" onclick="return backStatement();" class="btn btn-cancel-foot">{$smarty.const.IMAGE_CANCEL}</a></div>
        <div class="btn-right"><button class="btn btn-confirm">{$smarty.const.IMAGE_SAVE}</button></div>
    </div>
    {Html::input('hidden', 'affiliate_id', $model->affiliate_id)}
    {Html::endForm()}
</div>
<script type="text/javascript">
function backStatement() {
    window.location.href = '{$app->urlManager->createAbsoluteUrl('affiliate/')}';
    return false;
}
</script>