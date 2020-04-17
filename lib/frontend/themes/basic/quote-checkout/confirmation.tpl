{use class = "yii\helpers\Html"}
{use class="frontend\design\Block"}
{use class="frontend\design\Info"}
{\frontend\design\Info::addBoxToCss('form')}

<div class="page-confirmation">

    {Html::beginForm($form_action_url, 'post', ['id' => 'frmCheckoutConfirm', 'name' => 'checkout_confirmation'], false)}


    {if $noShipping}
        {Block::widget(['name' => 'confirmation_no_shipping_q', 'params' => ['type' => 'confirmation', 'params' => $params]])}
    {else}
        {Block::widget(['name' => 'confirmation_q', 'params' => ['type' => 'confirmation', 'params' => $params]])}
    {/if}


    {$payment_process_button_hidden}
    {Html::endForm()}
</div>
<script type="text/javascript">
    tl('{Info::themeFile('/js/main.js')}', function(){
        $('.order-summary').scrollBox();

        var count = 0;
        $('#frmCheckoutConfirm').on('submit', function(e){
            if (count > 0){
                e.preventDefault();
                return false
            }
            count++;
        })
    })
</script>