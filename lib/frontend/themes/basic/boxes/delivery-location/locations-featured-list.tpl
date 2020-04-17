{use class="frontend\design\Info"}
<div class="deliveryList deliveryListHeight">
    <ul>
        {foreach $locations_list as $location}
            <li>
                {if $location['image_listing_src']}
                    <div class="deliveryImg"><a href="{$location['href']}" title="{$location['title']|escape:'html'}"><img src="{$location['image_listing_src']}" border="0" width="{$location['image_listing_width']}" height="{$location['image_listing_height']}"></a></div>
                {/if}
                <div class="deliveryName"><a href="{$location['href']}" title="{$location['title']|escape:'html'}">{$location['location_name']}</a></div>
                {if $location['location_description_short']}
                    <div class="deliveryDesc locationFeatured-description_short">
                        {$location['location_description_short']}
                    </div>
                {/if}
            </li>
        {/foreach}
    </ul>
</div>
<script type="text/javascript">
  tl('{Info::themeFile('/js/main.js')}' , function(){
    setTimeout(function(){
      $('.deliveryListHeight').inRow(['.deliveryImg', '.deliveryName', '.deliveryDesc'], 4)
    }, 500);
  });

</script>