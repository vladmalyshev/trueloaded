<div class="rating" {if $count > 0}itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating"{/if}>
{if $settings[0].reviews_count == 'left'}<span class="review-count">(<span{if $count > 0} itemprop="reviewCount"{/if}>{$count}</span>)</span>{/if}
<span class="rating-{$rating}"></span>
    {if $count > 0}
        <meta itemprop="ratingValue" content="{$rating}" />
        <meta itemprop="bestRating" content="5" />
    {/if}
{if $settings[0].reviews_count == 'right'}<span class="review-count">(<span {if $count > 0}itemprop="reviewCount"{/if}>{$count}</span>)</span>{/if}
</div>