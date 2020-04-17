{use class="Yii"}
{use class="frontend\design\boxes\checkout\ShippingList"}
{use class="frontend\design\Info"}
{use class = "yii\helpers\Html"}
{if $payment_error && $payment_error.title }
  <p><strong>{$payment_error.title}</strong><br>{$payment_error.error}</p>
{/if}

{if $message != ''}
  <p>{$message}</p>
{/if}
{Html::beginForm($checkout_process_link, 'post', ['id' => 'frmCheckout', 'name' => 'one_page_checkout'])}
  {\frontend\design\Info::addBoxToCss('form')}

<div class="col-left">
  {if $is_shipping}
    <div class="shipping-address form-inputs" id="shipping_address">
      <div class="heading-4">{$smarty.const.SHIPPING_ADDRESS}</div>

      {if $addresses_array != ''}
      <div class="addresses" id="billing-addresses">
        {foreach $addresses_array as $addresse}
          <div class="address-item">
            <label>
              <input type="radio" name="billto" value="{$addresse.id}"{if $billing_address_book_id == $addresse.id} checked{/if}/>
              <span>{$addresse.text}</span>
            </label>
          </div>
        {/foreach}

        <div class="address-item">
          <label>
            <input type="radio" name="billto" value=""{if $billing_address_book_id == ''} checked{/if}/>
            <span>{$smarty.const.NEW_BILLING_ADDRESS}</span>
          </label>
        </div>
      </div>
    {/if}

      {assign var=re1 value='.{'}
      {assign var=re2 value='}'}

      <div id="billing-address">
{if in_array(ACCOUNT_GENDER, ['required', 'required_register', 'visible', 'visible_register'])}
        <div class="col-full genders-title">
          <div class="">{field_label const="ENTRY_GENDER" configuration="ACCOUNT_GENDER"}</div>
          <label><input type="radio" name="gender" value="m"{if $billing_gender == 'm'} checked{/if}/> <span>{$smarty.const.MR}</span></label>
          <label><input type="radio" name="gender" value="f"{if $billing_gender == 'f'} checked{/if}/> <span>{$smarty.const.MRS}</span></label>
          <label><input type="radio" name="gender" value="s"{if $billing_gender == 's'} checked{/if}/> <span>{$smarty.const.MISS}</span></label>
        </div>
{/if}

      <div class="columns">
{if in_array(ACCOUNT_FIRSTNAME, ['required', 'required_register', 'visible', 'visible_register'])}
        <div class="col-2">
          <label>
            <span>{field_label const="ENTRY_FIRST_NAME" configuration="ACCOUNT_FIRSTNAME"}</span>
            {if in_array(ACCOUNT_FIRSTNAME, ['required', 'required_register'])}
            <input type="text" name="firstname" value="{$billing_firstname|escape:'html'}" data-pattern="{$re1}{$smarty.const.ENTRY_FIRST_NAME_MIN_LENGTH}{$re2}" data-required="{sprintf($smarty.const.ENTRY_FIRST_NAME_ERROR, $smarty.const.ENTRY_FIRST_NAME_MIN_LENGTH)}"/>
            {else}
            <input type="text" name="firstname" value="{$billing_firstname|escape:'html'}"/>
            {/if}
          </label>
        </div>
{/if}
{if in_array(ACCOUNT_LASTNAME, ['required', 'required_register', 'visible', 'visible_register'])}
        <div class="col-2">
          <label>
            <span>{field_label const="ENTRY_LAST_NAME" configuration="ACCOUNT_LASTNAME"}</span>
            {if in_array(ACCOUNT_LASTNAME, ['required', 'required_register'])}
            <input type="text" name="lastname" value="{$billing_lastname|escape:'html'}" data-pattern="{$re1}{$smarty.const.ENTRY_LAST_NAME_MIN_LENGTH}{$re2}" data-required="{sprintf($smarty.const.ENTRY_LAST_NAME_ERROR, $smarty.const.ENTRY_LAST_NAME_MIN_LENGTH)}"/>
            {else}
            <input type="text" name="lastname" value="{$billing_lastname|escape:'html'}"/>
            {/if}
          </label>
        </div>
{/if}
{if in_array(ACCOUNT_STREET_ADDRESS, ['required', 'required_register', 'visible', 'visible_register'])}
        <div class="col-2">
          <label>
            <span>{field_label const="ENTRY_STREET_ADDRESS" configuration="ACCOUNT_STREET_ADDRESS"}</span>
            {if in_array(ACCOUNT_STREET_ADDRESS, ['required', 'required_register'])}
            <input type="text" name="street_address_line1" value="{$billing_street_address|escape:'html'}" data-pattern="{$re1}{$smarty.const.ENTRY_STREET_ADDRESS_MIN_LENGTH}{$re2}" data-required="{$smarty.const.ENTRY_STREET_ADDRESS_ERROR}"/>
            {else}
            <input type="text" name="street_address_line1" value="{$billing_street_address|escape:'html'}"/>
            {/if}
          </label>
        </div>
{/if}
{if in_array(ACCOUNT_SUBURB, ['required', 'required_register', 'visible', 'visible_register'])}
          <div class="col-2">
            <label>
            <span>{field_label const="ENTRY_SUBURB" configuration="ACCOUNT_SUBURB"}</span>
            {if in_array(ACCOUNT_SUBURB, ['required', 'required_register'])}
            <input type="text" name="street_address_line2" value="{$billing_suburb|escape:'html'}" data-pattern="{$re1}1{$re2}" data-required="{$smarty.const.ENTRY_SUBURB_ERROR}"/>
            {else}
            <input type="text" name="street_address_line2" value="{$billing_suburb|escape:'html'}"/>
            {/if}
            </label>
          </div>
{/if}
{if in_array(ACCOUNT_POSTCODE, ['required', 'required_register', 'visible', 'visible_register'])}
        <div class="col-2">
          <label>
            <span>{field_label const="ENTRY_POST_CODE" configuration="ACCOUNT_POSTCODE"}</span>
            {if in_array(ACCOUNT_POSTCODE, ['required', 'required_register'])}
            <input type="text" name="postcode" value="{$billing_postcode|escape:'html'}" data-pattern="{$re1}{$smarty.const.ENTRY_POSTCODE_MIN_LENGTH}{$re2}" data-required="{$smarty.const.ENTRY_POST_CODE_ERROR}"/>
            {else}
            <input type="text" name="postcode" value="{$billing_postcode|escape:'html'}"/>
            {/if}
          </label>
        </div>
{/if}
{if in_array(ACCOUNT_CITY, ['required', 'required_register', 'visible', 'visible_register'])}
        <div class="col-2">
          <label>
            <span>{field_label const="ENTRY_CITY" configuration="ACCOUNT_CITY"}</span>
            {if in_array(ACCOUNT_CITY, ['required', 'required_register'])}
            <input type="text" name="city" value="{$billing_city|escape:'html'}" data-pattern="{$re1}{$smarty.const.ENTRY_CITY_MIN_LENGTH}{$re2}" data-required="{$smarty.const.ENTRY_CITY_ERROR}"/>
            {else}
            <input type="text" name="city" value="{$billing_city|escape:'html'}"/>
            {/if}
          </label>
        </div>
{/if}
{if in_array(ACCOUNT_STATE, ['required', 'required_register', 'visible', 'visible_register'])}
          <div class="col-2">
            <label>
              <span>{field_label const="ENTRY_STATE" configuration="ACCOUNT_STATE"}</span>
              {if $entry_state_has_zones}
                <select name="state">
                  {foreach $zones_array as $zone}
                    <option value="{$zone.id}"{if $billing_state == $zone.id} selected{/if}>{$zone.text}</option>
                  {/foreach}
                </select>
              {else}
                {if in_array(ACCOUNT_STATE, ['required', 'required_register'])}
                <input type="text" name="state" value="{$billing_state|escape:'html'}" data-pattern="{$re1}{$smarty.const.ENTRY_STATE_MIN_LENGTH}{$re2}" data-required="{$smarty.const.ENTRY_STATE_ERROR}"/>
                {else}
                <input type="text" name="state" value="{$billing_state|escape:'html'}"/>
                {/if}
              {/if}
            </label>
          </div>
{/if}
{if in_array(ACCOUNT_COUNTRY, ['required', 'required_register', 'visible', 'visible_register'])}
        <div class="col-2">
          <label>
            <span>{field_label const="ENTRY_COUNTRY" configuration="ACCOUNT_COUNTRY"}</span>
            <select name="country" data-required="{$smarty.const.ENTRY_COUNTRY_ERROR}">
              {foreach $bill_countries as $country}
                <option value="{$country.countries_id}"{if $country.countries_id == $billing_country} selected{/if}>{$country.countries_name}</option>
              {/foreach}
            </select>
          </label>
        </div>
{/if}            
      </div>
      </div>
    </div>
  {/if}
</div>
<input type="hidden" name="ship_as_bill" value="on" />
<input type="hidden" name="shipping" value="free_free" />

<div class="col-right">
  <div class="contact-info form-inputs">
    <div class="heading-4">{$smarty.const.CONTACT_INFORMATION}</div>
    <div class="col-full">
      <label>
        <span>{field_label const="ENTRY_EMAIL_ADDRESS" required_text="*"}</span>
        <input type="email" name="email_address" value="{$email_address|escape:'html'}" data-required="{$smarty.const.EMAIL_REQUIRED}" data-pattern="email"/>
      </label>
    </div>
    <div class="columns">
{if in_array(ACCOUNT_TELEPHONE, ['required', 'required_register', 'visible', 'visible_register'])}
      <div class="col-2">
        <label>
          <span>{field_label const="ENTRY_TELEPHONE_NUMBER" configuration="ACCOUNT_TELEPHONE"}</span>
          {if in_array(ACCOUNT_TELEPHONE, ['required', 'required_register'])}
          <input type="text" name="telephone" value="{$telephone|escape:'html'}" data-pattern="{$re1}{$smarty.const.ENTRY_TELEPHONE_MIN_LENGTH}{$re2}" data-required="{sprintf($smarty.const.ENTRY_TELEPHONE_NUMBER_ERROR, $smarty.const.ENTRY_TELEPHONE_MIN_LENGTH)}"/>
          {else}
          <input type="text" name="telephone" value="{$telephone|escape:'html'}"/>
          {/if}
        </label>
      </div>
{/if}
{if in_array(ACCOUNT_LANDLINE, ['required', 'required_register', 'visible', 'visible_register'])}
      <div class="col-2">
        <label>
          <span>{field_label const="ENTRY_LANDLINE" configuration="ACCOUNT_LANDLINE"}</span>
          {if in_array(ACCOUNT_LANDLINE, ['required', 'required_register'])}
          <input type="text" name="landline" value="{$landline|escape:'html'}" data-pattern="{$re1}{$smarty.const.ENTRY_LANDLINE_MIN_LENGTH}{$re2}" data-required="{sprintf($smarty.const.ENTRY_LANDLINE_NUMBER_ERROR, $smarty.const.ENTRY_LANDLINE_MIN_LENGTH)}"/>
          {else}
          <input type="text" name="landline" value="{$landline|escape:'html'}"/>
          {/if}
        </label>
      </div>
{/if}
{if in_array(ACCOUNT_COMPANY, ['required', 'required_register', 'visible', 'visible_register'])}
          <div class="col-2">
            <label>
              <span>{field_label const="ENTRY_COMPANY" configuration="ACCOUNT_COMPANY"}</span>
            {if in_array(ACCOUNT_COMPANY, ['required', 'required_register'])}
            <input type="text" name="customer_company" value="{$customer_company|escape:'html'}" data-pattern="{$re1}1{$re2}" data-required="{$smarty.const.ENTRY_COMPANY_ERROR}"/>
            {else}
            <input type="text" name="customer_company" value="{$customer_company|escape:'html'}"/>
            {/if}
            </label>
          </div>
{/if}
{if in_array(ACCOUNT_COMPANY_VAT, ['required', 'required_register', 'visible', 'visible_register'])}
        <div class="col-2 company_vat_box">
            <label for="customer_company_vat">{field_label const="ENTRY_BUSINESS" configuration="ACCOUNT_COMPANY_VAT"}</label>
            {if in_array(ACCOUNT_COMPANY_VAT, ['required', 'required_register'])}
            <input id="customer_company_vat" type="text" name="customer_company_vat" value="{$customer_company_vat|escape:'html'}" data-pattern="{$re1}1{$re2}" data-required="{$smarty.const.ENTRY_VAT_ID_ERROR}"/><span id="customer_company_vat_status"></span>
            {else}
            <input id="customer_company_vat" type="text" name="customer_company_vat" value="{$customer_company_vat|escape:'html'}"/><span id="customer_company_vat_status"></span>
            {/if}
        </div>
{/if}
    </div>
  </div>
</div>

<div class="col-right">
  <div class="contact-info form-inputs">
    <div class="heading-4">{$smarty.const.COMMENTS_ABOUT_ORDER}</div>
    <div class="col-full">
      <label>
        <span>{field_label const="TYPE_ADDITIONAL_INFO" required_text=""}</span>
        <textarea name="comments" id="" cols="30" rows="5">{$comments|escape:'html'}</textarea>
      </label>
    </div>
  </div>
</div>

<div class="buttons">
  <div class="right-buttons">
    <span class="continue-text">{$smarty.const.CONTINUE_CHECKOUT_PROCEDURE}</span>
    <button type="submit" class="btn-2">{$smarty.const.CONTINUE}</button>
  </div>
</div>

{use class="frontend\design\boxes\sample\Products"}
{Products::widget(['type'=> 2])}

  {\frontend\design\Info::addBoxToCss('price-box')}
<div class="price-box" id="order_totals">
  {include file="./totals.tpl"}
</div>

<div class="buttons">
  <div class="right-buttons">
    <span class="continue-text">{$smarty.const.CONTINUE_CHECKOUT_PROCEDURE}</span>
    <button type="submit" class="btn-2">{$smarty.const.CONTINUE}</button>
  </div>
</div>

{Html::endForm()}

{$payment_javascript_validation}

  <script type="text/javascript">
function checkCountryVatState() {
    var selected = $('select[name="country"]').val();
    if (selected == {$smarty.const.STORE_COUNTRY}) {
        $('.company_vat_box').hide();
    } else {
        $('.company_vat_box').show();
    }
}
        
    var submitter = 0;

    tl([
      '{Info::themeFile('/js/main.js')}',
      '{Info::themeFile('/js/bootstrap-switch.js')}'
    ], function(){

      var addresses = {$addresses_json};

      $(function(){
        var shipping_address = $('#shipping-address');
        var shipping_addresses = $('#shipping-addresses');
        var billing_address = $('#billing-address');
        var billing_addresses = $('#billing-addresses');


        var ship_as_bill = false;
        if ($('input[name="ship_as_bill"]:checked').val()){
          ship_as_bill = true;
        }else{
          $('.hide-billing-address').hide();
        }

        $('.item-radio input').radioHolder({ holder: '.item-radio'});

        $('.addresses input').radioHolder({ holder: '.address-item'});

        $('.genders-title input').radioHolder();

        {\frontend\design\Info::addBoxToCss('switch')}
        $('#as-shipping').bootstrapSwitch({
          offText: '{$smarty.const.TEXT_NO}',
          onText: '{$smarty.const.TEXT_YES}',
          onSwitchChange: function (a, key) {
            if(key){
              $('.hide-billing-address').show();
              ship_as_bill = true;
            } else {
              $('.hide-billing-address').hide();
              ship_as_bill = false;
            }
            $('input:checked', shipping_addresses).each(same_addresses);
            $('input, select', shipping_address).each(same_address)
          }
        });

        var change_address = function(event){
          var address = addresses[$(this).val()];
          $('input, select', event.data.type_address).each(function(){
            var name = $(this).attr('name').replace('ship_', '').replace('shipping_', '');
            if ($(this).attr('type') == 'radio'){
              if (address == undefined) {
                $(this).prop('checked', false)
              }else{
                if ($(this).val() == address[name]){
                  $(this).prop('checked', true)
                } else {
                  $(this).prop('checked', false)
                }
              }
            } else {
              if (address != undefined) {
                $(this).val(address[name])
              } else if (name == 'country') {
                $(this).val('{$smarty.const.STORE_COUNTRY}')
              } else {
                $(this).val('')
              }
            }
            $(this).trigger('change')
          })
        };

        var same_addresses = function(){
          if (ship_as_bill){
            var val = $(this).val();
            $('select', billing_addresses).val(val);
            $('input', billing_addresses).each(function(){

              if ($(this).attr('value') == val){
                $(this).prop('checked', true).trigger('change')
              } else {
                $(this).prop('checked', false)
              }
            });
          }
        };

        var same_address = function(){
          if (ship_as_bill) {
            var val = $(this).val();
            var name = $(this).attr('name').replace('ship_', '').replace('shipping_', '');
            if ( this.type && this.type == 'radio' ) {
		if ($(this).prop('checked')) {
              	    $('input[name="' + name + '"]', billing_address).filter('[value="'+val+'"]').trigger('click');
		}
            }else {
              $('input[name="' + name + '"], select[name="' + name + '"]', billing_address).val(val);
            }
          }
        };

        $('input', shipping_addresses).on('change', { type_address: shipping_address}, change_address);

        $('input', billing_addresses).on('change', { type_address: billing_address}, change_address);

        $('input', shipping_addresses).on('change', same_addresses);

        $('input[type="radio"]', shipping_address).on('click', same_address);
        $('input, select', shipping_address).filter(function() {
          return !this.name.match(/(postcode|state|country)$/);
        }).on('change keyup', same_address);

        var delay = (function(){
          var timer = 0;
          return function(callback, ms){
            clearTimeout (timer);
            timer = setTimeout(callback, ms);
          };
        })();

        var $frmCheckout = $('#frmCheckout');
        $frmCheckout.append('<input type="hidden" name="xwidth" value="'+screen.width+'">').append('<input type="hidden" name="xheight" value="'+screen.height+'">');

        //$frmCheckout.find('input, select').validate();

        if ( typeof window.check_form == 'function' ) {
          $frmCheckout.on('submit',function(){
            return window.check_form();
          });
        }
        
        checkCountryVatState();
        
        $(document).on('change keyup',function(event) {
          if ( event.target.name && event.target.name.match(/(postcode|state|country|company_vat)$/) ) {
            if ( event.target.name.indexOf('ship')===0 ) {
              same_address.apply(event.target);
            }
            $frmCheckout.trigger('checkout_data_changed');
          }
        });
//        $('input, select',$frmCheckout).filter(function() {
//          return this.name.match(/(postcode|state|country)$/);
//        }).on('change',function () {
//          $frmCheckout.trigger('checkout_data_changed');
//        });
        $('#shipping_method').on('click',function(e){
          if ( e.target.tagName.toLowerCase()=='input' && e.target.name=='shipping' ) {
            $frmCheckout.trigger('checkout_data_changed');
          }
        });
				$('#payment_method').on('click',function(e){
          if ( e.target.tagName.toLowerCase()=='input' && e.target.name=='payment' ) {
            $frmCheckout.trigger('checkout_data_changed');
          }
        });
        $frmCheckout.on('checkout_data_changed', function(event, extra_post) {
          var $xhr,
                  $post_data = $frmCheckout.serializeArray();
          if ( extra_post && $.isArray(extra_post) ) {
            for(var _i=0; _i<extra_post.length; _i++){
              $post_data.push(extra_post[_i]);
            }
          }
          delay(function(){
            if($xhr && $xhr.readyState != 4) {
              $xhr.abort();
            }
            $xhr = $.ajax({
              url:'{$ajax_server_url}',
              data: $post_data,
              method:'post',
              dataType:'json',
              success: function(data) {
                if ( data.replace ) {
                  if ( data.replace.shipping_method ) {
                    $('#shipping_method').html(data.replace.shipping_method);
                  }
                  if ( data.replace.order_totals ) {
                    $('#order_totals').html(data.replace.order_totals);
                  }
                  if ( data.replace.company_vat_status ) {
                    $('#customer_company_vat_status').html(data.replace.company_vat_status);
                  }
                }
                if (data.payment_allowed){
                  var $payments = $('#payment_method').find('.payment_item');
                  $payments.each(function(){
                    var $payment_item = $(this);
                    var get_payment_class = this.className.match(/payment_class_([^\s]+)/);
                    if ( get_payment_class ){
                      if ($.inArray(get_payment_class[1],data.payment_allowed)===-1){
                        if ($payment_item.is(':visible')){
                          $payment_item.hide();
                        }
                      }else{
                        if ($payment_item.not(':visible')){
                          $payment_item.show();
                        }
                      }
                    }
                  });
                }
/*
                if ( data.zones ) {
                  if (data.zones.state){
                    var $current_state = $('input[name="state"], select[name="state"]',$frmCheckout);
                    var $new_state = $(data.zones.state);
                    if ($current_state.length>0 && $new_state.length>0 && $new_state[0].tagName!=$current_state[0].tagName){
                      $current_state.replaceWith($new_state);
                      //$new_state.validate();
                    }

                  }
                  if (data.zones.ship_state){
                    var $current_ship_state = $('input[name="ship_state"], select[name="ship_state"]',$frmCheckout);
                    var $new_ship_state = $(data.zones.ship_state);
                    if ($current_ship_state.length>0 && $new_ship_state.length>0 && $new_ship_state[0].tagName!=$current_ship_state[0].tagName ){
                      $current_ship_state.replaceWith($new_ship_state);
                      //$new_ship_state.validate();
                    }
                  }
                }
*/
                var credit_modules_message = '';
                if ( data.credit_modules && data.credit_modules.message ) {
                  credit_modules_message = data.credit_modules.message;
                }
                $('#credit_modules_message').html(credit_modules_message);
                
                checkCountryVatState();
              }
            });
          }, 300 );
        });
        $frmCheckout.trigger('checkout_data_changed');

        $('.js_cot_gv_dep').on('switch_update',function(event,state){
          if (state){
            $(this).removeClass('semi_disabled');
            $('.js_cot_gv_dep').find('input, button').removeAttr('disabled').removeAttr('readonly');
          }else{
            $(this).addClass('semi_disabled');
            $('.js_cot_gv_dep').find('input, button').attr({
              disabled:'disabled',
              readonly:'readonly'
            });
          }
        });

        {\frontend\design\Info::addBoxToCss('switch')}
        $('.credit-on-off').bootstrapSwitch({
          offText: '{$smarty.const.TEXT_NO}',
          onText: '{$smarty.const.TEXT_YES}',
          onSwitchChange: function (a, key) {
            $frmCheckout.trigger('checkout_data_changed', [[{
              name:'coupon_apply',value:'y'
            }]]);
            $('.js_cot_gv_dep').trigger('switch_update',[key]);
          }
        });
        if (!$('.credit-on-off').is(':checked')){
          $('.js_cot_gv_dep').trigger('switch_update',[false]);
        }
        $('.js_discount_apply').on('click',function() {
          $frmCheckout.trigger('checkout_data_changed', [[{
            name:'coupon_apply',value:'y'
          }]]);
          return false;
        });
      });

    })

  tl(['{Info::themeFile('/js/jquery-ui.min.js')}'], function(){
    {\frontend\design\Info::addBoxToCss('autocomplete')}
    $('input[name="state"]').autocomplete({
      source: function(request, response) {
        $.getJSON("{Yii::$app->urlManager->createUrl('account/address-state')}", { term : request.term, country: $('select[name="country"]').val() }, response);
      },
      minLength: 0,
      autoFocus: true,
      delay: 0,
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
      select: function( event, ui ) {
        setTimeout(function(){
          $('input[name="state"]').trigger('change');
        }, 200)
      }
    }).focus(function () {
      $(this).autocomplete("search");
    });
    $('input[name="ship_state"]').autocomplete({
			appendTo: $('input[name="ship_state"]').parent().parent(),
      source: function(request, response) {
        $.getJSON("{Yii::$app->urlManager->createUrl('account/address-state')}", { term : request.term, country: $('select[name="ship_country"]').val() }, response);
      },
      minLength: 0,
      autoFocus: true,
      delay: 0,
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
      select: function( event, ui ) {
        setTimeout(function(){
          $('input[name="ship_state"]').trigger('change');
        }, 200)
      }
    }).focus(function () {
      $(this).autocomplete("search");
    });
  })

  </script>