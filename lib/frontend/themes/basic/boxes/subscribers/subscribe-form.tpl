{use class="yii\helpers\Html"}
{\frontend\design\Info::addBoxToCss('switch')}
{assign var=re1 value='.{'}
{assign var=re2 value='}'}

{Html::beginForm($link, 'post', ['name' => 'subscribers_form'])}
<div class="contact-info form-inputs">
    <div class="col-full">
        <label>
            <span>{field_label const="ENTRY_FIRST_NAME" required_text="*"}</span>
            <input
                    type="text"
                    name="subscribers_firstname"
                    id="subscribers_firstname"
                    value="{$subscribers_firstname|escape:'html'}"
                    data-required="{$smarty.const.TEXT_NAME|escape:'html'}"
                    data-pattern="{$re1}{$smarty.const.ENTRY_FIRST_NAME_MIN_LENGTH}{$re2}"
                    placeholder="{$smarty.const.TEXT_NAME|escape:'html'}">
        </label>
    </div>
    <div class="col-full">
        <label>
            <span>{field_label const="ENTRY_LAST_NAME" required_text="*"}</span>
            <input
                    type="text"
                    name="subscribers_lastname"
                    id="subscribers_lastname"
                    value="{$subscribers_lastname|escape:'html'}"
                    data-required="{$smarty.const.ENTRY_LAST_NAME|escape:'html'}"
                    data-pattern="{$re1}{$smarty.const.ENTRY_LAST_NAME_MIN_LENGTH}{$re2}"
                    placeholder="{$smarty.const.ENTRY_LAST_NAME|escape:'html'}">
        </label>
    </div>
    <div class="col-full">
        <label>
            <span>{field_label const="TEXT_EMAIL" required_text="*"}</span>
            <input
                    type="email"
                    name="subscribers_email_address"
                    id="subscribers_email_address"
                    value="{$subscribers_email_address|escape:'html'}"
                    placeholder="{$smarty.const.TEXT_PLEASE_ENTER_VALID_EMAIL|escape:'html'}"
                    data-required="{$smarty.const.ENTRY_EMAIL_ADDRESS|escape:'html'}"
                    data-pattern="email">
        </label>
    </div>
    <div class="buttons">
        <div class="left-buttons">
            <div class="checkbox-holder">
            <input
                    type="checkbox"
                    name="privacy"
                    id="privacy"
                    value="1"
                    data-required="{$smarty.const.TEXT_PLEASE_TERMS|escape:'html'}"
                    class="checkbox">
            <span>{$smarty.const.TEXT_PLEASE_TERMS}</span>
            </div>
        </div>
        <div class="right-buttons"><button type="submit" class="btn">{$smarty.const.CONTINUE}</button></div>
    </div>
</div>
{Html::endForm()}
