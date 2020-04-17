{use class="frontend\design\Info"}
{\frontend\design\Info::addBoxToCss('product-images')}
{\frontend\design\Info::addBoxToCss('fancybox')}
{Info::addBoxToCss('slick')}
{if !$settings[0].no_wrapper}
<div class="js-product-image-set additional-images-box">
    <div class="images{if $settings[0].align_position == 'horizontal'} additional-horizontal{/if}">
{/if}

        <div class="additional-images"{if $images_count < 2} style="visibility: hidden; width: 0; height: 0" {/if}>

            {function imageItem}
                <div class="js-product-image" data-image-id="{$image_id}">
                    <div class="item"><div class="item-div">
                            <a href="{$item.image.Large.url}"
                               title="{$item.title|escape:'html'}"
                               class="fancybox {if $item.default} active{/if}"
                               data-fancybox-group="fancybox">
                                <img
                                        src="{$item.image.Small.url}"
                                        data-med="{$item.image.Medium.url}"
                                        data-lrg="{$item.image.Large.url}"
                                        alt="{$item.alt|escape:'html'}"
                                        title="{$item.title|escape:'html'}"
                                        class="default item-img"
                                        srcset="{$item.srcset}"
                                        sizes="{$item.sizes}"
                                >
                            </a>
                        </div></div>
                </div>
            {/function}

            {foreach $images as $image_id=>$item}
                {if $item.default == 1}
                    {imageItem item=$item image_id = $image_id}
                {/if}
            {/foreach}
            {foreach $images as $image_id=>$item}
                {if $item.default == 0}
                    {imageItem item=$item image_id = $image_id}
                {/if}
            {/foreach}
        </div>


{if !$settings[0].no_wrapper}
    </div>
{/if}
    <script type="text/javascript">
        tl([
            '{Info::themeFile('/js/slick.min.js')}',
            '{Info::themeFile('/js/jquery.fancybox.pack.js')}'
        ], function(){

            $('.main-image-box .additional-images').slick({
                {if !$settings[0].align_position}
                vertical: true,
                rows: 3,
                {else}
                slidesToShow: 3,
                {/if}
                infinite: false
            });
            $('.additional-images img').on('click', function(){
                $('.additional-images .item .active').removeClass('active');
                $(this).closest('a').addClass('active');
                $('.main-image').attr({
                    'src': $(this).data('med'),
                    'data-lrg': $(this).data('lrg'),
                    'alt': $(this).attr('alt'),
                    'title': $(this).attr('title')
                }).removeAttr('srcset');
                return false
            });
            $('.img-holder img').on('click', function(){
                $('.additional-images .active').closest('a').trigger('click', true);
                return false
            });
            $('.fancybox').on('click', function(a, open){
                if (!open) return false
            }).fancybox({
                nextEffect: 'fade',
                prevEffect: 'fade',
                padding: 10
            });

        })
    </script>
{if !$settings[0].no_wrapper}
</div>
{/if}