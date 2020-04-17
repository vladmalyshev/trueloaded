{use class="frontend\design\Info"}
<div class="categories">
  {foreach $categories as $category}
      <a class="item category-link" href="{$category.link}">
          {$category.img}
          <h2 class="name">
              {if $category.categories_h2_tag}
                  {$category.categories_h2_tag}
              {else}
                  {$category.categories_name}
              {/if}
          </h2>
      </a>
  {/foreach}
</div>
