<h2 class="bundle_title">{$smarty.const.TEXT_ALSO_AVAILABLE_IN_SETS}</h2>
<div class="bundle-listing">
	<div class="bundle_row after">
  {foreach $products as $product name=bundles}
		{if $smarty.foreach.bundles.index % 2 == 0 && $smarty.foreach.bundles.index != 0}
		</div><div class="bundle_row after">
		{/if}
    <div class="bundle_item">
      <div class="bundle_image"><a href="{$product.product_link}"><img src="{$product.image}" alt="{$product.products_name|escape:'html'}" title="{$product.products_name|escape:'html'}"></a></div>
			<div class="right-area-bundle">
				<div class="bundle_name">
					<a href="{$product.product_link}" class="product-link">{$product.products_name}</a>
				</div>
				<div class="bundle_price">
					{if $product.price}
						<span class="current">{$product.price}</span>
					{else}
						<span class="old">{$product.price_old}</span>
						<span class="specials">{$product.price_special}</span>
					{/if}
				</div>
      </div>			
    </div>
  {/foreach}
	</div>
</div>
