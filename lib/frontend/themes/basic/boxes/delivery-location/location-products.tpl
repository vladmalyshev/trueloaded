{use class="frontend\design\IncludeTpl"}
{use class="frontend\design\Info"}

<div class="products-box columns-{$settings[0].col_in_row}{if $settings[0].view_as == 'carousel'} products-carousel carousel-2{/if}">
    {IncludeTpl::widget(['file' => 'boxes/products-listing.tpl', 'params' => [ 'only_column'=>true, 'products' => $products, 'settings' => $settings, 'languages_id' => $languages_id]])}
</div>

{if $settings[0].view_as == 'carousel'}
    <script type="text/javascript">
        tl('{Info::themeFile('/js/slick.min.js')}', function(){

            var carousel = $('.carousel-2');
            var tabs = carousel.parents('.tabs');
            tabs.find('> .block').show();
            var show = {if $settings[0].col_in_row}{$settings[0].col_in_row}{else}4{/if};
            var width = carousel.width();
            if (width < 800 && show > 3) show = 3;
            if (width < 600 && show > 2) show = 2;
            if (width < 400 && show > 1) show = 1;
          {Info::addBoxToCss('slick')}
            $('.carousel-2 > div').slick({
                slidesToShow: show,
                slidesToScroll: show,
                infinite: false
            });
            setTimeout(function(){ tabs.trigger('tabHide') }, 100)
        })
    </script>
{/if}
