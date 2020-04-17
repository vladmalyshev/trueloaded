{use class="frontend\design\Info"}
{Info::addBoxToCss('cookie-notice')}
<script>
    tl('{Info::themeFile('/js/main.js')}', function(){
        if (!$.cookie('cookieNotice')) {
            var body = $('body');
            var popUpContent = '\
            <div class="cookie-notice {if $settings[0].position}{$settings[0].position}{else}top{/if}">\
                <div class="text">{$smarty.const.TEXT_COOKIE_NOTICE}</div>\
                <div class="buttons">\
                    <span class="btn btn-accept">{$smarty.const.TEXT_COOKIE_BUTTON}</span>\
                    {if !$settings[0].cancel_button}<span class="btn btn-close">{$smarty.const.TEXT_CLOSE}</span>{/if}\
                </div>\
            </div>';

    {if $settings[0].position == 'popup'}
            alertMessage(popUpContent, 'popup-simple')
            var cookieNoticeBox = $('.cookie-notice');
    {else}
            body.append(popUpContent);
            var cookieNoticeBox = $('.cookie-notice');
            cookieNoticeBox.css({
                'position':'fixed',
                'left': 0,
                'width': '100%',
                'z-index': '1000'
            });
        {if $settings[0].position == 'bottom'}
            cookieNoticeBox.css({ 'bottom': 0 });
            body.css({ 'padding-bottom': cookieNoticeBox.height()});
        {else}
            cookieNoticeBox.css({ 'top': 0 });
            body.css({ 'padding-top': cookieNoticeBox.height()});
        {/if}
    {/if}

            $('.btn-accept', cookieNoticeBox).on('click', function(){
                cookieNoticeBox.remove();
                $.cookie('cookieNotice', 1, $.extend(cookieConfig || {}, { expires: {if $settings[0].expires_days}{$settings[0].expires_days}{else}365{/if}}));
                body.css('padding-bottom', '');
                body.css('padding-top', '');

            });
            $('.btn-close', cookieNoticeBox).on('click', function(){
                cookieNoticeBox.remove();
                body.css('padding-bottom', '');
                body.css('padding-top', '');
            })
        }
    })
</script>