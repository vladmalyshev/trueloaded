{use class="\yii\helpers\Html"}
{use class="frontend\design\Info"}
{\frontend\design\Info::addBoxToCss('info')}
{Info::addBoxToCss('info')}
{Info::addBoxToCss('form')}
{Info::addBoxToCss('pass-strength')}
{Info::addBoxToCss('info-popup')}
{Info::addBoxToCss('switch')}
{Info::addBoxToCss('datepicker')}
{assign var=re1 value='.{'}
{assign var=re2 value='}'}
<h1>{$smarty.const.TEXT_AFFILIATE_SIGNUP}</h1>
<div class="middle-form">
    
{Html::beginForm('affiliate/signup', 'post', ['name' => $model->formName(), 'id' => $model->formName()])}

    <div class="messages"></div>

    <div class="heading-4">{$smarty.const.TEXT_AFFILIATE_PERSONAL_DETAILS}</div>

    <div class="col-left">
        <label for="{$model->formName()}-affiliate_firstname">{field_label const="ENTRY_FIRST_NAME" configuration="ACCOUNT_FIRSTNAME"}</label>
        {Html::activeTextInput($model, 'affiliate_firstname', ['data-pattern' => "{$re1}{$smarty.const.ENTRY_FIRST_NAME_MIN_LENGTH}{$re2}", 'data-required' => "{sprintf($smarty.const.ENTRY_FIRST_NAME_ERROR, $smarty.const.ENTRY_FIRST_NAME_MIN_LENGTH)}"])}
    </div>
    <div class="col-right">
        <label for="{$model->formName()}-affiliate_lastname">{field_label const="ENTRY_LAST_NAME" configuration="ACCOUNT_LASTNAME"}</label>
        {Html::activeTextInput($model, 'affiliate_lastname', ['data-pattern' => "{$re1}{$smarty.const.ENTRY_LAST_NAME_MIN_LENGTH}{$re2}", 'data-required' => "{sprintf($smarty.const.ENTRY_LAST_NAME_ERROR, $smarty.const.ENTRY_LAST_NAME_MIN_LENGTH)}"])}
    </div>
    
    <div class="col-full">
        <label for="{$model->formName()}-affiliate_email_address">{field_label const="ENTRY_EMAIL_ADDRESS" required_text="*"}</label>
        {Html::activeInput('email', $model, 'affiliate_email_address', ['data-required' => "{$smarty.const.EMAIL_REQUIRED}", 'data-pattern' => "email", 'class' => 'form-control'])}
    </div>
    
    <div class="heading-4">{$smarty.const.TEXT_AFFILIATE_COMPANY_DETAILS}</div>
    
    <div class="col-left">
        <label for="{$model->formName()}-affiliate_company">{field_label const="ENTRY_COMPANY" configuration="ACCOUNT_COMPANY"}</label>                        
        {if in_array(ACCOUNT_COMPANY, ['required_register', 'required'])}
            {Html::activeTextInput($model, 'affiliate_company', ['data-pattern' => "{$re1}1{$re2}", 'data-required' => "{$smarty.const.ENTRY_COMPANY_ERROR}"])}
        {else}
            {Html::activeTextInput($model, 'affiliate_company')}
        {/if}
    </div>
    <div class="col-right">
        <label for="{$model->formName()}-affiliate_company_taxid">{field_label const="ENTRY_BUSINESS" configuration="ACCOUNT_COMPANY_VAT"}</label>
        {if in_array(ACCOUNT_COMPANY_VAT, ['required_register', 'required'])}
            {Html::activeTextInput($model, 'affiliate_company_taxid', ['data-pattern' => "{$re1}1{$re2}", 'data-required' => "{$smarty.const.ENTRY_VAT_ID_ERROR}"])}
        {else}
            {Html::activeTextInput($model, 'affiliate_company_taxid')}
        {/if}
    </div>
    
    <div class="heading-4">{$smarty.const.TEXT_AFFILIATE_GET_MONEY}</div>
    {if AFFILIATE_USE_CHECK == 'true'}
    <div class="col-full">
        <label for="{$model->formName()}-affiliate_payment_check">{$smarty.const.TEXT_AFFILIATE_CHECK}:</label>
        {Html::activeTextInput($model, 'affiliate_payment_check')}
    </div>
    {/if}
    {if AFFILIATE_USE_PAYPAL == 'true'}
    <div class="col-full">
        <label for="{$model->formName()}-affiliate_payment_paypal">{$smarty.const.TEXT_AFFILIATE_PAYPAL}:</label>
        {Html::activeTextInput($model, 'affiliate_payment_paypal')}
    </div>
    {/if}
    {if AFFILIATE_USE_BANK == 'true'}
    <div class="col-full">
        <label for="{$model->formName()}-affiliate_payment_bank_name">{$smarty.const.TEXT_AFFILIATE_BANK}:</label>
        {Html::activeTextInput($model, 'affiliate_payment_bank_name')}
    </div>
    <div class="col-full">
        <label for="{$model->formName()}-affiliate_payment_bank_branch_number">{$smarty.const.TEXT_AFFILIATE_BRANCH}):</label>
        {Html::activeTextInput($model, 'affiliate_payment_bank_branch_number')}
    </div>
    <div class="col-full">
        <label for="{$model->formName()}-affiliate_payment_bank_swift_code">{$smarty.const.TEXT_AFFILIATE_SWIFT}:</label>
        {Html::activeTextInput($model, 'affiliate_payment_bank_swift_code')}
    </div>
    <div class="col-full">
        <label for="{$model->formName()}-affiliate_payment_bank_account_name">{$smarty.const.TEXT_AFFILIATE_NAME}:</label>
        {Html::activeTextInput($model, 'affiliate_payment_bank_account_name')}
    </div>
    <div class="col-full">
        <label for="{$model->formName()}-affiliate_payment_bank_account_number">{$smarty.const.TEXT_AFFILIATE_NUMBER}:</label>
        {Html::activeTextInput($model, 'affiliate_payment_bank_account_number')}
    </div>
    {/if}
    <div class="heading-4">{$smarty.const.TEXT_AFFILIATE_ADDRESS}</div>
    
    <div class="col-left">
        <label for="{$model->formName()}-affiliate_street_address">{field_label const="ENTRY_STREET_ADDRESS" configuration="ACCOUNT_STREET_ADDRESS"}</label>
        {if in_array(ACCOUNT_STREET_ADDRESS, ['required_register', 'required'])}
            {Html::activeTextInput($model, 'affiliate_street_address', ['data-required' => "{sprintf($smarty.const.ENTRY_STREET_ADDRESS_ERROR, $smarty.const.ENTRY_STREET_ADDRESS_MIN_LENGTH)}", 'data-pattern' => "{$re1}{$smarty.const.ENTRY_STREET_ADDRESS_MIN_LENGTH}{$re2}"])}
        {else}
            {Html::activeTextInput($model, 'affiliate_street_address')}
        {/if}
    </div>
    <div class="col-right">
        <label for="{$model->formName()}-affiliate_suburb">{field_label const="ENTRY_SUBURB" configuration="ACCOUNT_SUBURB"}</label>
        {if in_array(ACCOUNT_SUBURB, ['required_register', 'required'])}
            {Html::activeTextInput($model, 'affiliate_suburb', ['data-required' => "{$smarty.const.ENTRY_SUBURB_ERROR}", 'data-pattern' => "{$re1}1{$re2}"])}                                
        {else}
            {Html::activeTextInput($model, 'affiliate_suburb')}                                
        {/if}
    </div>
    
    <div class="col-left">
        <label for="{$model->formName()}-affiliate_city">{field_label const="ENTRY_CITY" configuration="ACCOUNT_CITY"}</label>
        {if in_array(ACCOUNT_CITY, ['required_register', 'required'])}
            {Html::activeTextInput($model, 'affiliate_city', ['data-required' => "{sprintf($smarty.const.ENTRY_CITY_ERROR, $smarty.const.ENTRY_CITY_MIN_LENGTH)}", 'data-pattern' => "{$re1}{$smarty.const.ENTRY_CITY_MIN_LENGTH}{$re2}"])}
        {else}
            {Html::activeTextInput($model, 'affiliate_city')}
        {/if}
    </div>
    <div class="col-right">
        <label for="{$model->formName()}-affiliate_state">{field_label const="ENTRY_STATE" configuration="ACCOUNT_STATE"}</label>
        {if in_array(ACCOUNT_STATE, ['required_register', 'required'])}
            {Html::activeTextInput($model, 'affiliate_state', ['class' => 'state', 'data-required' => "{sprintf($smarty.const.ENTRY_STATE_ERROR, $smarty.const.ENTRY_STATE_MIN_LENGTH)}", 'data-pattern' => "{$re1}{$smarty.const.ENTRY_STATE_MIN_LENGTH}{$re2}"])}
        {else}
            {Html::activeTextInput($model, 'affiliate_state')}
        {/if}
    </div>
    
    <div class="col-left">
        <label for="{$model->formName()}-affiliate_postcode">{field_label const="ENTRY_POST_CODE" configuration="ACCOUNT_POSTCODE"}</label>
        {if in_array(ACCOUNT_POSTCODE, ['required_register', 'required'])}
             {Html::activeTextInput($model, 'affiliate_postcode', ['data-required' => "{sprintf($smarty.const.ENTRY_POST_CODE_ERROR, $smarty.const.ENTRY_POSTCODE_MIN_LENGTH)}", 'data-pattern' => "{$re1}{$smarty.const.ENTRY_POSTCODE_MIN_LENGTH}{$re2}"])}
        {else}
            {Html::activeTextInput($model, 'affiliate_postcode')}
        {/if}
    </div>
    <div class="col-right">
        <label for="{$model->formName()}-affiliate_country_id">{field_label const="ENTRY_COUNTRY" configuration="ACCOUNT_COUNTRY"}</label>
        {Html::activedropDownList($model, 'affiliate_country_id', \common\helpers\Country::new_get_countries('', false), ['class' => 'country', 'required' => (ACCOUNT_COUNTRY == 'required_register'), 'value' => $model->getDefaultCountryId()])}
    </div>
    
    <div class="heading-4">{$smarty.const.TEXT_AFFILIATE_CONTACT}</div>
    
    <div class="col-left">
        <label for="{$model->formName()}-affiliate_telephone">{field_label const="ENTRY_TELEPHONE_NUMBER" configuration="ACCOUNT_TELEPHONE"}</label>
        {if in_array(ACCOUNT_TELEPHONE, ['required_register', 'required'])}
            {Html::activeTextInput($model, 'affiliate_telephone', ['data-required' => "{sprintf($smarty.const.ENTRY_TELEPHONE_NUMBER_ERROR, $smarty.const.ENTRY_TELEPHONE_MIN_LENGTH)}", 'data-pattern' => "{$re1}{$smarty.const.ENTRY_TELEPHONE_MIN_LENGTH}{$re2}"])}
        {else}
            {Html::activeTextInput($model, 'affiliate_telephone')}                            
        {/if}
    </div>
    <div class="col-right">
        <label for="{$model->formName()}-affiliate_fax">{field_label const="ENTRY_LANDLINE" configuration="ACCOUNT_LANDLINE"}</label>
        {if in_array(ACCOUNT_LANDLINE, ['required_register', 'required'])}
            {Html::activeTextInput($model, 'affiliate_fax', ['data-required' => "{sprintf($smarty.const.ENTRY_LANDLINE_NUMBER_ERROR, $smarty.const.ENTRY_LANDLINE_MIN_LENGTH)}", 'data-pattern' => "{$re1}{$smarty.const.ENTRY_LANDLINE_MIN_LENGTH}{$re2}"])}
        {else}
            {Html::activeTextInput($model, 'affiliate_fax')}                            
        {/if}
    </div>
    
    <div class="col-full">
        <label for="{$model->formName()}-affiliate_homepage">{$smarty.const.TEXT_AFFILIATE_HOMEPAGE}:&nbsp;&nbsp;<span class="required">{$smarty.const.TEXT_AFFILIATE_REQUIRED}</span>  </label>
        {Html::activeTextInput($model, 'affiliate_homepage')}
    </div>
    
    <div class="heading-4">{$smarty.const.TEXT_AFFILIATE_PASSWORD}</div>
    
    <div class="password-row">
        <div class="col-left">
            <label for="{$model->formName()}-password" class="password-info">
                <div class="info-popup top-left"><div>{sprintf($smarty.const.TEXT_HELP_PASSWORD, $smarty.const.STORE_NAME)}</div></div>
                {field_label const="PASSWORD" required_text="*"}
            </label>
            {Html::activePasswordInput($model, 'password', ['class' => "password", 'autocomplete' => "off", 'data-pattern' => "{$re1}{$smarty.const.ENTRY_PASSWORD_MIN_LENGTH}{$re2}", 'data-required' => "{sprintf($smarty.const.ENTRY_PASSWORD_ERROR, $smarty.const.ENTRY_PASSWORD_MIN_LENGTH)}"])}
        </div>
        <div class="col-right">
            <label for="confirmation">{field_label const="PASSWORD_CONFIRMATION" required_text="*"}</label>
            {Html::activePasswordInput($model, 'confirmation', ['class' => "confirmation", 'autocomplete' => "off", 'data-required' => "{$smarty.const.ENTRY_PASSWORD_ERROR_NOT_MATCHING}", 'data-confirmation' => "#affiliate_registration-password"])}
        </div>
    </div>

    <div class="col-full privacy-row">
        <div class="terms-login">
            {Html::activeCheckbox($model, 'terms', ['class' => 'terms-conditions', 'value' => '1', 'label' => '', 'checked' => false])}{$smarty.const.TEXT_TERMS_CONDITIONS}
        </div>
    </div>
    <div class="center-buttons">
        <button class="btn-2 disabled-area" type="submit">{$smarty.const.CREATE}</button>
    </div>        

{Html::endForm()}
</div>
<script type="text/javascript">
    tl([
        '{Info::themeFile('/js/main.js')}',
        '{Info::themeFile('/js/password-strength.js')}',
        '{Info::themeFile('/js/bootstrap-switch.js')}',
        '{Info::themeFile('/js/hammer.js')}',
        '{Info::themeFile('/js/candlestick.js')}',
        '{Info::themeFile('/js/bootstrap.min.js')}',       
    ], function(){
        var form = $('#{$model->formName()}');
        $('input', form).validate();
        form.on('submit', function(){
            if ($('.required-error', form).length === 0){
                $.post(form.attr('action'), form.serialize(), function(data){
                    var messages = '';
                    $.each(data.messages, function(type, val){
                        messages += '<div class="message error">'+val+'</div>';
                    });
                    $('.messages', form).html(messages);
                    jQuery("html, body").animate({ scrollTop: 0 }, 1000);
                    if (data.status === 'success') {
                        window.location.href = '{$app->urlManager->createAbsoluteUrl('affiliate/signup-ok')}';
                    }
                }, 'json')
            }
            return false;
        });
        
        $('.password', form).passStrength({
            shortPassText: "{$smarty.const.TEXT_TOO_SHORT|strip}",
            badPassText: "{$smarty.const.TEXT_WEAK|strip}",
            goodPassText: "{$smarty.const.TEXT_GOOD|strip}",
            strongPassText: "{$smarty.const.TEXT_STRONG|strip}",
            samePasswordText: "{$smarty.const.TEXT_USERNAME_PASSWORD_IDENTICAL|strip}",
            userid: "#firstname"
        });

        $('.confirmation, .password', form).on('keyup', function () {
            var confirmation = $('.confirmation', form);
            if (confirmation.val() !== $('.password', form).val() && confirmation.val()) {
                confirmation.prev(".pass-strength").remove();
                confirmation.before('<span class="pass-strength pass-no-match"><span>{$smarty.const.TEXT_NO_MATCH|strip}</span></span>');
            } else if (confirmation.val() === '') {
                confirmation.prev(".pass-strength").remove();
            } else {
                confirmation.prev(".pass-strength").remove();
                confirmation.before('<span class="pass-strength pass-match"><span>{$smarty.const.TEXT_MATCH|strip}</span></span>');
            }
        });
        
        
        
        var disableButton = function(e){
            e.preventDefault();
            return false;
        };

        $('.disabled-area', form).on('click', disableButton);

        $(".check-on-off", form).bootstrapSwitch({
            offText: '{$smarty.const.TEXT_NO}',
            onText: '{$smarty.const.TEXT_YES}',
            onSwitchChange: function () {
                $(this).closest('form').trigger('cart-change')
            }
        });
        
        $(".terms-conditions", form).bootstrapSwitch({
            offText: '{$smarty.const.TEXT_NO}',
            onText: '{$smarty.const.TEXT_YES}',
            onSwitchChange: function (d, e) {
                var form = $(this).closest('form');
                form.trigger('cart-change');
                if(e){
                    $('button[type="submit"]', form).removeClass('disabled-area').off('click', disableButton);
                }else{
                    $('button[type="submit"]', form).addClass('disabled-area').on('click', disableButton);
                }
            }
        });
        
        var count = 0;
        $(form).on('submit', function(e){
            if (!document.querySelector('.terms-conditions').checked){
                alertMessage('{$smarty.const.TEXT_PLEASE_TERMS}');
                return false;
            }  
    
            if (count > 0){
                setTimeout(function(){
                    count = 0
                }, 1000);
                e.preventDefault();
                return false;
            }
            count++;
            return true;
        });
        
        
        
    });
</script>