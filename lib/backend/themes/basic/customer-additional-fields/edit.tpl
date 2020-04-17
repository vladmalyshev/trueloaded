
{use class="backend\assets\BannersAsset"}
{BannersAsset::register($this)|void}

<form action="" class="group-form">
    <input type="hidden" name="field_id" value="{$field_id}"/>
    <input type="hidden" name="group_id" value="{$group_id}"/>

    <div class="">


        <table style="width: 500px; margin-bottom: 50px">
            <tr>
                <td class="label_name">field code:</td>
                <td class="label_value">
                    <input type="text" name="additional_fields_code" value="{$fields.additional_fields_code}" class="form-control"/>
                </td>
            </tr>
            <tr>
                <td class="label_name">type:</td>
                <td class="label_value">
<select name="field_type" id="" class="form-control">
    <option value="text"{if $fields.field_type == '' && $fields.field_type == 'text'} selected{/if}>Text</option>
    <option value="checkbox"{if $fields.field_type == 'checkbox'} selected{/if}>Checkbox</option>

    <optgroup label="Address">
        <option value="firstname"{if $fields.field_type == 'firstname'} selected{/if}>First Name</option>
        <option value="lastname"{if $fields.field_type == 'lastname'} selected{/if}>Last Name</option>
        <option value="company"{if $fields.field_type == 'company'} selected{/if}>Company Name</option>
        <option value="phone"{if $fields.field_type == 'phone'} selected{/if}>Phone number</option>
        <option value="email"{if $fields.field_type == 'email'} selected{/if}>email</option>
        <option value="postcode"{if $fields.field_type == 'post_code'} selected{/if}>Post Code</option>
        <option value="suburb"{if $fields.field_type == 'suburb'} selected{/if}>Suburb</option>
        <option value="state"{if $fields.field_type == 'state'} selected{/if}>County/State</option>
        <option value="street_address"{if $fields.field_type == 'street_address'} selected{/if}>Street Address</option>
        <option value="city"{if $fields.field_type == 'city'} selected{/if}>Town/City</option>
        <option value="country_id"{if $fields.field_type == 'country_id'} selected{/if}>Country</option>
    </optgroup>
</select>
                </td>
            </tr>
            <tr>
                <td class="label_name">Required:</td>
                <td class="label_value">
                    <input type="checkbox" name="required" {if $fields.required}checked{/if} class="check_bot_switch_on_off"/>
                </td>
            </tr>
        </table>


        <div class="tabbable tabbable-custom">
            <ul class="nav nav-tabs">

                {foreach $languages as $language}
                    <li{if $language.id == $languages_id} class="active"{/if}><a href="#{$item.id}_{$language.id}" data-toggle="tab">{$language.logo} {$language.name}</a></li>
                {/foreach}

            </ul>
            <div class="tab-content">

                {foreach $languages as $language}
                    <div class="tab-pane{if $language.id == $languages_id} active{/if}" id="{$item.id}_{$language.id}" data-language="{$language.id}">


                        <table style="width: 500px">
                            <tr>
                                <td class="label_name">{$smarty.const.TABLE_HEADING_TITLE}:</td>
                                <td class="label_value">
                                    <input type="text" name="title[{$language.id}]" value="{$fieldsDescriptionByLanguages[$language.id]['title']}" class="form-control"/>
                                </td>
                            </tr>
                        </table>

                    </div>
                {/foreach}

            </div>
        </div>


    </div>

    <div class="btn-bar">
        <div class="btn-left">
            <a href="{$url_back}" class="btn">{$smarty.const.IMAGE_BACK}</a>
        </div>
        <div class="btn-right">
            <span class="btn btn-confirm">{$smarty.const.IMAGE_SAVE}</span>
        </div>
    </div>
</form>



<script type="text/javascript">

    $(function () {

        $(".check_bot_switch_on_off").bootstrapSwitch(
            {
                onText: "{$smarty.const.SW_ON}",
                offText: "{$smarty.const.SW_OFF}",
                handleWidth: '20px',
                labelWidth: '24px'
            }
        );

        let form = $('.group-form');

        $('.btn-confirm').on('click', function(){
            $.post('{$app->urlManager->createUrl('customer-additional-fields/save')}', form.serializeArray(), function(response){

                alertMessage(`<div class="alert-message">${ response.text}</div>`);

                if (!(+$('input[name="field_id"]').val())){
                    window.location = '{$app->urlManager->createUrl(['customer-additional-fields/edit', 'group_id' => $group_id, 'level_type' => 'fields'])}&field_id=' + response.field_id
                }
                if (response.code != 'error') {
                    setTimeout(function () {
                        $('.popup-box-wrap').remove()
                    }, 1000)
                    resetStatement()
                }
            }, 'json')
        });
    })

</script>