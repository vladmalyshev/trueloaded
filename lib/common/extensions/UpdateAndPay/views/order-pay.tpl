{use class="frontend\design\Info"}
{use class = "yii\helpers\Html"}
{use class="frontend\design\Block"}
{\frontend\design\Info::addBoxToCss('info')}
{\frontend\design\Info::addBoxToCss('select-suggest')}
{\frontend\design\Info::addBoxToCss('autocomplete')}
{\frontend\design\Info::addBoxToCss('form')}

{if $payment_error && $payment_error.title }
    <div class="" id="payment_error-box" style="display:none;">
        <div class="" style="padding: 20px">
            <strong>{$payment_error.title}</strong><br>
            {$payment_error.error}
        </div>
    </div>
    <script>
        tl('{Info::themeFile('/js/main.js')}', function(){
            $('<a href="#payment_error-box"></a>').popUp().trigger('click')
        });
    </script>
{/if}

{Html::beginForm($checkout_process_link, 'post', ['id' => 'frmCheckout', 'name' => 'one_page_checkout'])}
<input type="hidden" name="order_id" value="{$order_id}" />

{Block::widget(['name' => 'update-and-pay-order_pay', 'params' => ['type' => 'payer', 'params' => $params]])}



{Html::endForm()}

{$payment_javascript_validation}


<script type="text/javascript">
    tl('{Info::themeFile('/js/main.js')}', function(){
        $('.order-summary').scrollBox();

        $('.closeable-box').closeable();
    })
</script>