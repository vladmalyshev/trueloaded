{use class="frontend\design\Info"}
<div class="page-list">
    {foreach $locations_list as $location}
        <div class="item">
            {if $location['image_listing_src']}
                <div class="image">
                    <a href="{$location['href']}" title="{$location['title']|escape:'html'}">
                        <img src="{$location['image_listing_src']}" border="0" width="{$location['image_listing_width']}" height="{$location['image_listing_height']}">
                    </a>
                </div>
            {/if}
            <div class="name">
                <a href="{$location['href']}" title="{$location['title']|escape:'html'}">{$location['location_name']}</a>
            </div>
            {if $location['location_description_short']}
                <div class="description">
                    {$location['location_description_short']}
                </div>
            {/if}
        </div>
    {/foreach}
</div>