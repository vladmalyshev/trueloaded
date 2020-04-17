
<div class="setting-row">
    <label for="">{$smarty.const.CHOOSE_ORDERS_DATA}</label>
    <select name="setting[0][orders_data]" id="" class="form-control">
        <option value=""{if $settings[0].orders_data == ''} selected{/if}></option>
        <option value="name"{if $settings[0].orders_data == 'name'} selected{/if}>{$smarty.const.TABLE_TEXT_NAME}</option>
        <option value="email"{if $settings[0].orders_data == 'email'} selected{/if}>{$smarty.const.TEXT_EMAIL}</option>
        <option value="telephone"{if $settings[0].orders_data == 'telephone'} selected{/if}>{$smarty.const.TEXT_TELEPHONE}</option>
        <option value="delivery_address"{if $settings[0].orders_data == 'delivery_address'} selected{/if}>{$smarty.const.DELIVERY_ADDRESS}</option>
        <option value="billing_address"{if $settings[0].orders_data == 'billing_address'} selected{/if}>{$smarty.const.TEXT_BILLING_ADDRESS}</option>
        <option value="shipping_method"{if $settings[0].orders_data == 'shipping_method'} selected{/if}>{$smarty.const.TEXT_CHOOSE_SHIPPING_METHOD}</option>
        <option value="payment_method"{if $settings[0].orders_data == 'payment_method'} selected{/if}>{$smarty.const.TEXT_SELECT_PAYMENT_METHOD}</option>
    </select>
</div>
<div class="setting-row">
    <label for="">{$smarty.const.HIDE_PARENTS_IF_EMPTY}</label>
    <select name="setting[0][hide_parents]" id="" class="form-control">
        <option value=""{if $settings[0].hide_parents == ''} selected{/if}>{$smarty.const.TEXT_NO}</option>
        <option value="1"{if $settings[0].hide_parents == '1'} selected{/if}>1</option>
        <option value="2"{if $settings[0].hide_parents == '2'} selected{/if}>2</option>
        <option value="3"{if $settings[0].hide_parents == '3'} selected{/if}>3</option>
        <option value="4"{if $settings[0].hide_parents == '4'} selected{/if}>4</option>
    </select>
</div>