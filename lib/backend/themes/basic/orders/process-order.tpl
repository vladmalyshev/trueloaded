{use class = "yii\helpers\Html"}
{use class = "yii\helpers\Url"}

{if is_array($messsages) && $messsages}
    {foreach $messsages as $messages_block}
        {foreach $messages_block as $message}
              <div class="alert fade in alert-{$message['type']}">
                  <i data-dismiss="alert" class="icon-remove close"></i>
                  <span id="message_plce">{$message['text']}</span>
              </div>
        {/foreach}
    {/foreach}
{/if}

{Html::beginForm(array_merge(['orders/ordersubmit'], $queryParams), 'post', ['id' => 'status_edit', 'onSubmit' => 'return check_form();'] )}
    {Html::hiddenInput('orders_id', $order->order_id)}
    
    {$manager->render('Notification', ['manager' => $manager])}
    
    {$manager->render('AddressDetails', ['manager' => $manager, 'order' => $order])}

    {$manager->render('Products', ['manager' => $manager, 'order' => $order])}

    {$manager->render('ExtraCustomData', ['manager' => $manager, 'order' => $order])}
    
    {$manager->render('StatusBox', ['manager' => $manager, 'order' => $order])}

{Html::endForm()}

    {$manager->render('Buttons', ['manager' => $manager, 'order' => $order])}
<script>
    function getTrackingList() {
        $.get('orders/tracking-list', {
            'orders_id': '{$order->order_id}',
        }, function (data, status) {
            $('.tracking_number').html(data);
        });
    }
    $(function() {
        getTrackingList()
    });
</script>
