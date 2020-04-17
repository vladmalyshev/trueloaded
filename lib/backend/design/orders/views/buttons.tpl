<div class="btn-bar" style="padding: 0; text-align: center;">
    <div class="btn-left">
        <a href="javascript:void(0)" onclick="return resetStatement();" class="btn btn-back">{$smarty.const.IMAGE_BACK}</a>
    </div>
    {$manager->render('Unprocessed', ['order' => $order])}
    {$manager->render('Request', ['manager' => $manager, 'order' => $order])}
    <a href="{\Yii::$app->urlManager->createUrl(['orders/ordersbatch', 'pdf' => 'invoice', 'action' => 'selected', 'orders_id' => $order->info['orders_id']])}" TARGET="_blank" class="btn btn-mar-right btn-primary">{$smarty.const.TEXT_INVOICE}</a>
    <a href="{\Yii::$app->urlManager->createUrl(['orders/ordersbatch', 'pdf' => 'packingslip', 'action' => 'selected', 'orders_id' => $order->order_id])}" TARGET="_blank" class="btn btn-primary">{$smarty.const.IMAGE_ORDERS_PACKINGSLIP}</a>
    {$manager->render('PrintLabel', ['manager' => $manager, 'order' => $order])}    
</div>