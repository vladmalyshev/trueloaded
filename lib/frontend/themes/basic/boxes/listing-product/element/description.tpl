    {if $element.settings[0].just_short}
        {$description = $product.products_description_short}
    {else}
        {if $product.products_description_short}
            {$description = $product.products_description_short}
        {else}
            {$description = $product.products_description}
        {/if}
    {/if}
    {if !$element.settings[0].use_tags}
        {$description = strip_tags($description)}

        {if $element.settings[0].full_description == ''}
            {if $element.settings[0].description_characters}
                {$description_characters = $element.settings[0].description_characters}
            {else}
                {$description_characters = 90}
            {/if}
            {$description = $description|truncate:$description_characters}
        {/if}
    {/if}
    {$description}