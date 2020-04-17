<div class="confirm-info">
    <!--<strong>{$order->info['payment_method']}</strong>!-->
    {if $payment_confirmation}
        <br>
        {if $payment_confirmation.title}
            {$payment_confirmation.title}<br>
        {/if}
        {if isset($payment_confirmation.fields) && is_array($payment_confirmation.fields)}
            <table>
                {foreach $payment_confirmation.fields as $payment_confirmation_field}
                    <tr>
                        <td>{$payment_confirmation_field.title}</td><td>{$payment_confirmation_field.field}</td>
                    </tr>
                {/foreach}
            </table>
        {/if}
    {/if}
    {if is_object($order->manager)}
    {\yii\helpers\Html::hiddenInput('payment', $order->manager->getPaymentCollection()->selected_module)}
    {/if}
</div>