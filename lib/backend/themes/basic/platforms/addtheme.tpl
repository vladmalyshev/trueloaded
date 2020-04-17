<div class="themes_jcarousel">
	<div class="jcarousel">
		<ul id="th_jcarousel">
			{foreach $results as $res}
			<li data-id="{$res['id']}">
				<div class="fr_theme_img"><img src="{DIR_WS_CATALOG}images/screenshot-{$res['theme_name']}.png"></div>
				<div class="fr_theme_name">{$res['title']}</div>
				{if $res['description']}<div class="fr_theme_desc">{$res['description']}</div>{/if}
				<div class="fr_buttons"><button class="btn">{$smarty.const.TEXT_ASSIGN}</button></div>
			</li>
			{/foreach}
		</ul>
	</div>
	<a href="#" class="jcarousel-control-prev"></a>
  <a href="#" class="jcarousel-control-next"></a>
</div>
<script type="text/javascript">
(function($) {
    $(function() {
var jcarousel = $('.jcarousel').jcarousel();
			jcarousel.on('jcarousel:reload jcarousel:create', function () {
                var carousel = $(this),
                    width = carousel.innerWidth();
                if (width >= 1099) {
                    width = width / 3;
                } else {
                    width = width / 2;
                }

                carousel.jcarousel('items').css('width', Math.ceil(width-36) + 'px');
            })
            .jcarousel({
                wrap: 'circular'
            });
        $('.jcarousel-control-prev')
            .on('jcarouselcontrol:active', function() { 
                $(this).removeClass('inactive');
            })
            .on('jcarouselcontrol:inactive', function() { 
                $(this).addClass('inactive');
            })
            .jcarouselControl({
                target: '-=1'
            });

        $('.jcarousel-control-next')
            .on('jcarouselcontrol:active', function() { 
                $(this).removeClass('inactive');
            })
            .on('jcarouselcontrol:inactive', function() { 
                $(this).addClass('inactive');
            })
            .jcarouselControl({ 
                target: '+=1'
            });
					$('.fr_buttons button').click(function(){
            $('input[name="theme_id"]').val($(this).parent().parent().data('id'));
						var src_img = $(this).parent().prev().prev().find('img').attr('src');
						$('.theme_wr .theme_title').html('<img src="'+src_img+'" width="100" height="80"><div class="theme_title2">' + $(this).parent().parent().find('.fr_theme_name').text() + '</div>').addClass('act');
						$('.pop-up-close').trigger('click');
						return false;
					})
});
})(jQuery);

</script>
