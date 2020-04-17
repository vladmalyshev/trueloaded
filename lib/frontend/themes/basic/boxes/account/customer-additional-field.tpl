
{if in_array($field.field_type, ['firstname', 'lastname', 'company', 'phone', 'email', 'postcode', 'street_address', 'suburb', 'city', 'state', 'country_id'])}
    {$typyClass = 'text'}
{else}
    {$typyClass = $field.field_type}
{/if}
<div class="customer-additional-field field-type-{$typyClass}">
    {if !$settings[0]['no_label']}
            <label>{$field.title}:{if $field.required}<span class="required">*</span>{/if}</label>
    {/if}

    {if $field.field_type == 'text' || $field.field_type == ''}
            <input
                    class="form-control"
                    name="field[{$field.additional_fields_id}]"
                    value="{$value}"
                    type="text"
                    {if $field.required}data-required="{$field.title}"{/if}>
    {/if}

    {if $field.field_type == 'checkbox'}
            <input
                    type="checkbox"
                    name="field[{$field.additional_fields_id}]"
                    {if $value}checked{/if}
                    {if $field.required}data-required="{$field.title}"{/if}>
    {/if}

    {if in_array($field.field_type, ['firstname', 'lastname', 'company', 'phone', 'email', 'postcode', 'street_address', 'suburb', 'city', 'state'])}
            <input
                    class="form-control"
                    type="text"
                    name="field[{$field.additional_fields_id}]"
                    value="{$value}" class="address-field"
                    data-type="{$field.field_type}"
                    data-group-id="{$field.additional_fields_group_id}">


    {/if}

    {if $field.field_type == 'country_id'}
        <select
                class="form-control"
                type="text"
                name="field[{$field.additional_fields_id}]"
                value="{$value}" class="address-field"
                data-type="{$field.field_type}"
                data-group-id="{$field.additional_fields_group_id}">
            {foreach $countries as $country}
                <option value="{$country.id}" {if $value == $country.id} selected{/if}>{$country.text}</option>
            {/foreach}
        </select>

    {/if}

</div>

