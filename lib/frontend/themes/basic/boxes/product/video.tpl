{use class="frontend\design\Info"}
{capture name="video"}
  {\frontend\design\Info::addBoxToCss('product-images')}
  {if $video[0].code}
  <div class="frame-video">
    <iframe
            width="{if $settings[0].video_width}{$settings[0].video_width}{else}560{/if}"
            height="{if $settings[0].video_height}{$settings[0].video_height}{else}315{/if}"
            src="https://www.youtube.com/embed/{$video[0].code}?rel={if $settings[0].rel}0{else}1{/if}&controls={if $settings[0].controls}0{else}1{/if}&showinfo={if $settings[0].showinfo}0{else}1{/if}"
            frameborder="0"
            allowfullscreen></iframe>


  </div>
  {/if}
{/capture}

<div class="video-box{if $settings[0].align_position == 'horizontal'} additional-horizontal{elseif $video|@count > 1} additional-vertical{/if}">
  {if $settings[0].align_position == 'horizontal'}
    {$smarty.capture.video}
  {/if}


  {if $video|@count > 1}
  <div class="additional-videos">
    {foreach $video as $item}
      <div class="js-product-image" data-image-id="{$image_id}">
        <div class="item">
          <div>
            <a><img src="https://img.youtube.com/vi/{$item.code}/0.jpg" alt="" data-code="{$item.code}" class="add-video"></a>
          </div>
        </div>
      </div>
    {/foreach}
  </div>
  {/if}


  {if !$settings[0].align_position}
    {$smarty.capture.video}
  {/if}

</div>




<script type="text/javascript">
  tl('{Info::themeFile('/js/slick.min.js')}', function(){

    {Info::addBoxToCss('slick')}
    $('.additional-videos').slick({
      {if !$settings[0].align_position}
      vertical: true,
      rows: 3,
      {else}
      slidesToShow: 3,
      {/if}
      infinite: false
    });

    $('.add-video').on('click', function(){
      var code = $(this).data('code');
      $('.frame-video').html('<iframe width="{if $settings[0].video_width}{$settings[0].video_width}{else}560{/if}" height="{if $settings[0].video_height}{$settings[0].video_height}{else}315{/if}" src="https://www.youtube.com/embed/' + code + '?autoplay=1&rel={if $settings[0].rel}0{else}1{/if}&controls={if $settings[0].controls}0{else}1{/if}&showinfo={if $settings[0].showinfo}0{else}1{/if}" frameborder="0" allowfullscreen></iframe>')
    })
  })
</script>