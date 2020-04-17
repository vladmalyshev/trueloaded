{use class="\yii\helpers\Html"}
<div style="overflow: auto;">
    <div class="left-box">
        {Html::beginForm('', 'post', ['id' => 'checkoutForm'])}
        <div class="contact-holder">
            <div class="customer-box">{$manager->render('Customer', ['manager' => $manager, 'admin'=> $admin])}</div>
            <div class="shipping-address-box">{$manager->render('ShippingAddress', ['manager' => $manager])}</div>
            <div class="billing-address-box">{$manager->render('BillingAddress', ['manager' => $manager])}</div>
            <div class="modules-box">
                <div class="shipping-modules-box">{$manager->render('Shipping', ['manager' => $manager])}</div>
                <div class="payment-modules-box">{$manager->render('Payment', ['manager' => $manager])}</div>
            </div>
        </div>
        <div class = "btn-tools">
            <div class="btn-left">
                <a href="javascript:void(0)" id="reset_checkout" class="btn btn-cancel">{$smarty.const.IMAGE_CANCEL}</a>
            </div>
            <div class="btn-right">
                <a href="javascript:void(0)" id="save_checkout"  class="btn btn-primary btn-save-checkout">Save changes</a>
            </div>
        </div>
        {Html::endForm()}
    </div>
    <div class="totals-box">
        {$manager->render('OrderTotals', ['manager' => $manager])}
    </div>
</div>
<script>
  (function($){
    $("#reset_checkout").click(function(){
        order.resetCheckout();
    })
    
    $("#save_checkout").click(function(){
        order.saveCheckout($('#checkoutForm'));
    })
    
  })(jQuery)
</script>