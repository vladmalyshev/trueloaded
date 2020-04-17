{use class="frontend\design\Info"}
<div class="messages"></div>
<div class="address-book">

        {foreach $address_array as $address}
        <div class="item{if $address.address_book_id == $address.default_address} primary_bg{/if} js-addressBlock">
            <div class="heading-4">
                {$address.text|escape:'html'}
                <a href="{$address.link_edit}" class="{if $settings[0].popup} popup-link {/if}{if $settings[0].like_button == 1} btn {/if}{if $settings[0].like_button == 2} btn-1 {/if}{if $settings[0].like_button == 3} btn-3 {/if}{if $settings[0].like_button == 4} btn-2 {/if} btn-edit">
                    {$text}
                </a>

                <span class="btn-del" data-href="{$address.link_delete}"></span>
            </div>
            <div class="address-wrapper">
                <div class="default-address">
                    <span>{$smarty.const.TEXT_PRIMARY}</span>
                    <input
                            type="radio"
                            name="is_default"
                            value="{$address.address_book_id}"
                            class="switch"
                            {if $address.address_book_id == $address.default_address} checked{/if}>
                </div>
                <div class="default-data">
                    <div class="name">{$address.customers}</div>
                    {$address.format}
                </div>
            </div>
        </div>
        {/foreach}


        <div class="button-add" {if !$show_add} style="display: none" {/if}>
            <a href="{$link_add}" class="{if $settings[0].popup_add} popup-link {/if}{if $settings[0].like_button_add == 1} btn {/if}{if $settings[0].like_button_add == 2} btn-1 {/if}{if $settings[0].like_button_add == 3} btn-3 {/if}{if $settings[0].like_button_add == 4} btn-2 {/if}">
                {$text_add}
            </a>
        </div>


</div>
<script type="text/javascript">
    tl('{Info::themeFile('/js/bootstrap-switch.js')}', function(){
        var box = $('#box-{$id}');

        $('.btn-del', box).on('click', function(){
            var _this = $(this);
            confirmMessage('{$smarty.const.DELETE_ADDRESS_DESCRIPTION}', function(){
                $.get(_this.data('href'), function(data){
                    var messages = '';
                    $.each(data.messages, function(key, val){
                        messages += '<div class="message '+val['type']+'">'+val.text+'</div>';
                        if (val['type'] == 'success'){
                            _this.closest('.item').remove();
                        }
                    });
                    $('.button-add').show();
                    $('.messages', box).html(messages);
                }, 'json')
            }, '{$smarty.const.IMAGE_BUTTON_DELETE}', '{$smarty.const.CANCEL}')
        });

        var customers_id = {$customer_id};

        {\frontend\design\Info::addBoxToCss('switch')}
        $(".switch", box).bootstrapSwitch({
            offText: '{$smarty.const.TEXT_NO}',
            onText: '{$smarty.const.TEXT_YES}',
            onSwitchChange: function (element, arguments) {
                switchPrimary(element.target.value, customers_id);
                return true;
            }
        })
        $('.switch[checked]', box).bootstrapSwitch('state', true);
    });

    function switchPrimary(is_default, customers_id) {
        $.get('{$link_switch}', { 'is_default' : is_default, 'customers_id' : customers_id }, function(data, status){
                var $chkCollection = $('input[name="is_default"]');
                $('.js-addressBlock').removeClass('primary_bg');
                $chkCollection.each(function(){
                    this.checked = ( parseInt(this.value,10)==data.default_address_id );
                    var $chk = $(this);
                    if ( this.checked ) {
                        $chk.parents('.js-addressBlock').addClass('primary_bg');
                    }
                    if ( typeof $chk.bootstrapSwitch === 'function' ) {
                        $chk.bootstrapSwitch('state', this.checked, true);
                    }
                });
        },"json");
    }
</script>