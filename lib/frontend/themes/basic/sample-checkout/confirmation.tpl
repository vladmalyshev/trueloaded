{use class = "yii\helpers\Html"}
{use class="frontend\design\Block"}
{\frontend\design\Info::addBoxToCss('form')}

<div class="page-confirmation">

    {Html::beginForm($form_action_url, 'post', ['id' => 'frmCheckoutConfirm', 'name' => 'checkout_confirmation'], false)}


    {if $extSampleFree}
        {Block::widget(['name' => 'confirmation_free_s', 'params' => ['type' => 'checkout', 'params' => $params]])}
    {else}
        {Block::widget(['name' => 'confirmation_s', 'params' => ['type' => 'checkout', 'params' => $params]])}
    {/if}


    {$payment_process_button_hidden}
    {Html::endForm()}
</div>
<script type="text/javascript">
    tl(function(){
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