<div class="price-box order-total">
  {\frontend\design\Info::addBoxToCss('price-box')}
  {foreach $order_total_output as $order_total}
    <div class="price-row{if $order_total.code=='ot_total'} total{/if} {$order_total.class}{if $order_total.show_line} totals-line{/if} {$order_total.code}">
      <div class="title">{$order_total.title}</div>
      <div class="price">
          {$order_total.text}
          {if $order_total.code == 'ot_gv' || $order_total.code == 'ot_coupon' || $order_total.code == 'ot_bonus_points'}
          <span class="remove-discount"></span>
          {/if}
      </div>
    </div>
  {/foreach}
</div>

<script>
    tl(function(){
        $('.ot_gv .remove-discount').on('click', function(){
            $(window).trigger('removeCreditAmount')
        });
        $('.ot_coupon .remove-discount').on('click', function(){
            $(window).trigger('removeDiscountCoupon')
        });
        $('.ot_bonus_points .remove-discount').on('click', function(){
            $(window).trigger('removeBonusPoints')
        })
    })
</script>