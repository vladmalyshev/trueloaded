<div class="quantity-discounts">
  <div class="heading-4">{$smarty.const.QUANTITY_DISCOUNTS}</div>
  <div class="quantity-discounts-content">
    {foreach $discounts as $discount}
    <div class="item">
      <span class="count">{$discount.count}</span>
      <span class="price">{$discount.price}</span>
    </div>
    {/foreach}
  </div>
</div>