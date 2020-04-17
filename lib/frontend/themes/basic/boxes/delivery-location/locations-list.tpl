{if $parents}
    <div class="deliveryListParrent">
        {foreach $parents as $parent}
            <a href="{$parent['href']}">{$parent['location_name']}</a>
        {/foreach}
    </div>
{/if}
<div class="deliveryList">
    <ul>
        {foreach $locations_list as $location}
            <li>{if $location['iso2']}<span class="flag {$location['iso2']|lower}"></span>{/if}<a href="{$location['href']}" title="{$location['title']|escape:'html'}">{$location['location_name']}</a></li>
        {/foreach}
    </ul>
</div>
