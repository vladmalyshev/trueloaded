<div class="widget box box-no-shadow">
    <div class="widget-header">
        <h4>Payment method</h4>
        {$manager->render('Toolbar')}        
    </div>
    <div class="widget-content after">
        <div class="payment-method" id="payment_method">
            {if !$manager->getPayment()}
                <div class="item">
                    <div class="item-radio">
                        <label>
                            <input type="radio" name="payment" value="" checked />
                            <span>Payment is not selected</span>
                        </label>
                    </div>
                </div>
            {/if}
            {foreach $manager->getPaymentSelection() as $i}
                <div class="item payment_item payment_class_{$i.id}"  {if $i.hide_row} style="display: none"{/if}>
                    {if isset($i.methods)}
                        {foreach $i.methods as $m}
                            <div class="item-radio">
                                <label>
                                    <input type="radio" name="payment" value="{$m.id}"{if $i.hide_input} style="display: none"{/if}{if $m.checked} checked{/if}/>
                                    <span>{$m.module}</span>
                                </label>
                            </div>
                        {/foreach}
                    {else}
                        <div class="item-radio">
                            <label>
                                <input type="radio" name="payment" value="{$i.id}"{if $i.hide_input} style="display: none"{/if}{if $i.checked} checked{/if}/>
                                <span>{$i.module}</span>
                            </label>
                        </div>
                    {/if}
                    {*foreach $i.fields as $j}
                        <div class="sub-item">
                            <label>
                                <span>{$j.title}</span>
                            </label>
                            {$j.field}
                        </div>
                    {/foreach*}
                </div>
            {/foreach}
            <script>
                (function($){        
                    $('#payment_method').on('click',function(e){
                      if ( e.target.tagName.toLowerCase()=='input' && e.target.name=='payment' ) {
                        order.dataChanged($('#checkoutForm'), 'payment_changed');           
                      }
                    });
                })(jQuery);
            </script>
            <div class="cra">
                {$manager->render('CreditAmount', ['manager' => $manager])}
                {$manager->render('PromoCode', ['manager' => $manager])}
            </div>            
        </div>
    </div>
</div>