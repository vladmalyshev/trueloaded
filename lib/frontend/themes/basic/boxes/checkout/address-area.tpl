{use class="\common\helpers\Html"}
{use class="frontend\design\Info"}
{\frontend\design\Info::addBoxToCss('autocomplete')}

    {assign var=re1 value='.{'}
    {assign var=re2 value='}'}
    {Html::activeHiddenInput($model, 'address_book_id')}
    {if $model->has('GENDER')}
        <div class="col-full genders-title">
            <div class="">{field_label const="ENTRY_GENDER" configuration=$model->get('GENDER')}</div>
            {Html::activeRadioList($model, 'gender', $model->getGendersList(), ['unselect' => null])}
        </div>
    {/if}
    <div class="columns form-inputs">
        {if $model->has('FIRSTNAME')}
            <div class="col-2">
                <label>
                    <span>{field_label const="ENTRY_FIRST_NAME" configuration=$model->get('FIRSTNAME')}</span>
                    {if $model->has('FIRSTNAME', false)}
                        {Html::activeTextInput($model, 'firstname', ['data-pattern' => "{$re1}{$smarty.const.ENTRY_FIRST_NAME_MIN_LENGTH}{$re2}", 'data-required' => "{sprintf($smarty.const.ENTRY_FIRST_NAME_ERROR, $smarty.const.ENTRY_FIRST_NAME_MIN_LENGTH)}"])}
                    {else}
                        {Html::activeTextInput($model, 'firstname')}
                    {/if}
                </label>
            </div>
        {/if}
        {if $model->has('LASTNAME')}
            <div class="col-2">
                <label>
                    <span>{field_label const="ENTRY_LAST_NAME" configuration=$model->get('LASTNAME')}</span>
                    {if $model->has('LASTNAME', false)}
                        {Html::activeTextInput($model, 'lastname', ['data-pattern' => "{$re1}{$smarty.const.ENTRY_LAST_NAME_MIN_LENGTH}{$re2}", 'data-required' => "{sprintf($smarty.const.ENTRY_LAST_NAME_ERROR, $smarty.const.ENTRY_LAST_NAME_MIN_LENGTH)}"])}
                    {else}
                        {Html::activeTextInput($model, 'lastname')}
                    {/if}
                </label>
            </div>
        {/if}
        {if $model->has('STREET_ADDRESS')}
            <div class="col-2">
                <label>
                    <span>{field_label const="ENTRY_STREET_ADDRESS" configuration=$model->get('STREET_ADDRESS')}</span>
                    {if $model->has('STREET_ADDRESS', false)}
                        {Html::activeTextInput($model, 'street_address', ['data-pattern' => "{$re1}{$smarty.const.ENTRY_STREET_ADDRESS_MIN_LENGTH}{$re2}", 'data-required' => "{sprintf($smarty.const.ENTRY_STREET_ADDRESS_ERROR, ENTRY_STREET_ADDRESS_MIN_LENGTH)}"])}
                    {else}
                        {Html::activeTextInput($model, 'street_address')}
                    {/if}
                </label>
            </div>
        {/if}
        {if $model->has('SUBURB')}
            <div class="col-2">
                <label>
                    <span>{field_label const="ENTRY_SUBURB" configuration=$model->get('SUBURB')}</span>
                    {if $model->has('SUBURB', false)}
                        {Html::activeTextInput($model, 'suburb', ['data-pattern' => "{$re1}1{$re2}", 'data-required' => "{$smarty.const.ENTRY_SUBURB_ERROR}"])}
                    {else}
                        {Html::activeTextInput($model, 'suburb')}
                    {/if}
                </label>
            </div>
        {/if}
        {if $model->has('POSTCODE')}
            <div class="col-2">
                <label>
                    <span>{field_label const="ENTRY_POST_CODE" configuration=$model->get('POSTCODE')}</span>
                    {if $model->has('POSTCODE', false)}
                        {Html::activeTextInput($model, 'postcode', ['data-pattern' => "{$re1}{$smarty.const.ENTRY_POSTCODE_MIN_LENGTH}{$re2}", 'data-required' => "{sprintf($smarty.const.ENTRY_POST_CODE_ERROR, ENTRY_POSTCODE_MIN_LENGTH)}"])}
                    {else}
                        {Html::activeTextInput($model, 'postcode')}
                    {/if}
                </label>
            </div>
        {/if}
        {if $model->has('CITY')}
            <div class="col-2">
                <label>
                    <span>{field_label const="ENTRY_CITY" configuration=$model->get('CITY')}</span>
                    {if $model->has('CITY', false)}
                        {Html::activeTextInput($model, 'city', ['data-pattern' => "{$re1}{$smarty.const.ENTRY_CITY_MIN_LENGTH}{$re2}", 'data-required' => "{sprintf($smarty.const.ENTRY_CITY_ERROR, ENTRY_CITY_MIN_LENGTH)}"])}
                    {else}
                        {Html::activeTextInput($model, 'city')}
                    {/if}
                </label>
            </div>
        {/if}
        {if $model->has('STATE')}
            <div class="col-2">
                <label>
                    <span>{field_label const="ENTRY_STATE" configuration=$model->get('STATE')}</span>
                    {if $model->has('STATE', false)}
                        {Html::activeTextInput($model, 'state', ['data-pattern' => "{$re1}{$smarty.const.ENTRY_STATE_MIN_LENGTH}{$re2}", 'data-required' => "{sprintf($smarty.const.ENTRY_STATE_ERROR, ENTRY_STATE_MIN_LENGTH)}"])}
                    {else}
                        {Html::activeTextInput($model, 'state')}
                    {/if}
                </label>
            </div>
        {/if}
        {if $model->has('COUNTRY')}
            <div class="col-2">
                <label>
                    <span>{field_label const="ENTRY_COUNTRY" configuration=$model->get('COUNTRY')}</span>
                    {Html::activeDropDownList($model, 'country', $model->getAllowedCountries(), ['data-required' => "{$smarty.const.ENTRY_COUNTRY_ERROR}", 'class' => ' select2 select2-offscreen', 'data-iso' => $model->getAllowedCountriesISO() ])}
                </label>
            </div>
        {/if}
        {if $model->has('COMPANY')}
            <div class="col-2">
                <label>
                    <span>{field_label const="ENTRY_COMPANY" configuration=$model->get('COMPANY')}</span>
                    {if $model->has('COMPANY', false)}
                        {Html::activeTextInput($model, 'company', ['data-pattern' => "{$re1}1{$re2}", 'data-required' => "{$smarty.const.ENTRY_COMPANY_ERROR}"])}
                    {else}
                        {Html::activeTextInput($model, 'company')}
                    {/if}
                </label>
            </div>
        {/if}
        {if $model->has('COMPANY_VAT')}
            <div class="col-2">
                <label>
                    <span>{field_label const="ENTRY_BUSINESS" configuration=$model->get('COMPANY_VAT')}</span>
                    {if $model->has('COMPANY_VAT', false)}
                        {Html::activeTextInput($model, 'company_vat', ['data-pattern' => "{$re1}1{$re2}", 'data-required' => "{$smarty.const.ENTRY_VAT_ID_ERROR}"])}
                    {else}
                        {Html::activeTextInput($model, 'company_vat')}
                    {/if}
                    <i class="company_vat_status"></i>
                </label>
            </div>
        {/if}
        {if $model->has('TELEPHONE')}
            <div class="col-2">
                <label>
                    <span>{field_label const="ENTRY_TELEPHONE_ADRESS_BOOK" configuration=$model->get('TELEPHONE')}</span>
                    {if $model->has('TELEPHONE', false)}
                        {Html::activeTextInput($model, 'telephone', ['data-pattern' => "{$re1}1{$re2}", 'data-required' => "{$smarty.const.ENTRY_TELEPHONE_ADRESS_BOOK_ERROR}"])}
                    {else}
                        {Html::activeTextInput($model, 'telephone')}
                    {/if}
                    <i class="company_vat_status"></i>
                </label>
            </div>
        {/if}
        <div class="col-2" style="display: none">
            <label>
                {if ($model::SHIPPING_ADDRESS == $model->addressType && $model->address_book_id != $manager->getSendto()) 
                ||
                ($model::BILLING_ADDRESS == $model->addressType && $model->address_book_id != $manager->getBillto()) }
                {Html::activeCheckbox($model, 'as_preferred')}
                {/if}
            </label>
        </div>
    </div>
    {$postcoder->drawCheckoutPostcodeHelper($model, "checkout.data_changed('recalculation')")}
    <script>
        tl(['{Info::themeFile('/js/jquery-ui.min.js')}', '{Info::themeFile('/js/address.js')}', '{Info::themeFile('/js/bootstrap-switch.js')}'], function(){
            {\frontend\design\Info::addBoxToCss('autocomplete')}
            $('#{Html::getInputId($model, 'state')}').setStateCountryDependency({
                'country': '#{Html::getInputId($model, 'country')}',
                'url': "{Yii::$app->urlManager->createUrl('account/address-state')}",
            });
            
            $('#{Html::getInputId($model, 'city')}').getCityList({
                'country': '#{Html::getInputId($model, 'country')}',
                'url': "{Yii::$app->urlManager->createUrl('account/address-city')}",
            });
            
            $('#{Html::getInputId($model, 'company_vat')}').on('change keyup',function(e){
                checkout.data_changed('check_vat', [{
                    name:'checked_model',value:'{$model->formName()}'
                }])
            })
            
            $('#{Html::getInputId($model, 'as_preferred')}').bootstrapSwitch({
                offText: '{$smarty.const.TEXT_NO}',
                onText: '{$smarty.const.TEXT_YES}',
            });
            
        });
    </script>