<div class="video-tab">
<h4>{$smarty.const.VIDEOS_FROM}</h4>
<div class="tabbable tabbable-custom">
  {if $languages|@count > 1}
  <ul class="nav nav-tabs">
    {foreach $languages as $lKey => $lItem}
      <li{if $lKey == 0} class="active"{/if}><a href="#tab_1_14_{$lItem['id']}" class="flag-span" data-toggle="tab">{$lItem['logo']}<span>{$lItem['name']}</span></a></li>
    {/foreach}
  </ul>
  {/if}
  <div class="tab-content {if $languages|@count < 2}tab-content-no-lang{/if}">
    {foreach $languages as $lKey => $lItem}
      <div class="tab-pane{if $lKey == 0} active{/if}" id="tab_1_14_{$lItem['id']}">

        <div class="product-video">
          {if isset($app->controller->view->videos[$lItem['id']]) && $app->controller->view->videos[$lItem['id']]|@count == 0}
            <div>
              <textarea name="video[{$lItem['id']}][]" cols="30" rows="2" placeholder="{$smarty.const.PLACE_HERE_CODE}" class="form-control"></textarea>
            </div>
          {/if}
          {foreach $app->controller->view->videos[$lItem['id']] as $item}
            <div>
              <div class="remove"></div>
              <textarea name="video[{$lItem['id']}][]" cols="30" rows="2" placeholder="{$smarty.const.PLACE_HERE_CODE}" class="form-control">{$item.video}</textarea>
            </div>
          {/foreach}
        </div>

        <div><span class="btn btn-add-video" data-lng="{$lItem['id']}">{$smarty.const.ADD_MORE_VIDEO}</span></div>

      </div>
    {/foreach}
  </div>
</div>


<script type="text/javascript">
  (function($){
    $(function(){
      $('.btn-add-video').on('click', function(){
        var this_lng = $(this).closest('.tab-pane');
        var lng_id = $(this).data('lng');

        $('.product-video', this_lng).append('<div><div class="remove"></div><textarea name="video['+lng_id+'][]" cols="30" rows="2" placeholder="{$smarty.const.PLACE_HERE_CODE}" class="form-control">{$item.video}</textarea></div>');

        $('.product-video .remove').off('click').on('click', function(){
          $(this).parent().remove()
        })
      });

      $('.product-video .remove').on('click', function(){
        $(this).parent().remove()
      })
    })
  })(jQuery)
</script>
</div>