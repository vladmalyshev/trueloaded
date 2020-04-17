{use class="\common\helpers\Date"}
{use class="\yii\helpers\Html"}
<div class="or_box_head">{$smarty.const.TEXT_ORDER_NUM}{$oInfo->orders_id}</div>
<div class="row_or">
    <div>{$smarty.const.TEXT_DATE_ORDER_CREATED}</div>
    <div>{Date::datetime_short($oInfo->date_purchased)}</div>
</div>
{if tep_not_null($oInfo->last_modified)}
<div class="row_or">
    <div>{$smarty.const.TEXT_DATE_ORDER_LAST_MODIFIED}</div>
    <div>{Date::date_short($oInfo->last_modified)}</div>
</div>
{/if}
<div class="row_or">
    <div>{$smarty.const.TEXT_INFO_PAYMENT_METHOD}</div>
    <div>{strip_tags($oInfo->payment_method)}</div>
</div>
<div class="btn-toolbar btn-toolbar-order">
    {Html::a(TEXT_PROCESS_ORDER_BUTTON, \Yii::$app->urlManager->createUrl(['orders/process-order', 'orders_id' => $oInfo->orders_id]), ['class' => 'btn btn-primary btn-process-order'])}
    <span class="disable_wr"><span class="dis_popup"><span class="dis_popup_img"></span><span class="dis_popup_content">{$smarty.const.TEXT_COMPLITED}</span></span>
    {Html::a(IMAGE_EDIT, \Yii::$app->urlManager->createUrl(['editor/order-edit', 'orders_id' => $oInfo->orders_id]), ['class' => 'btn btn-no-margin btn-edit'])}</span>{if !tep_session_is_registered('login_affiliate')}{Html::button(IMAGE_DELETE, ['class' => 'btn btn-delete', 'onclick' => "confirmDeleteOrder("|cat:$oInfo->orders_id|cat:")"])}<br>{/if}
    {Html::a(TEXT_INVOICE, \Yii::$app->urlManager->createUrl(['orders/ordersbatch', 'pdf' => 'invoice', 'action' => 'selected', 'orders_id' => $oInfo->orders_id]), ['class' => "btn btn-no-margin", 'target'=>"_blank"])}{Html::a(IMAGE_ORDERS_PACKINGSLIP, \Yii::$app->urlManager->createUrl(['orders/ordersbatch', 'pdf' => 'packingslip', 'action' => 'selected', 'orders_id' => $oInfo->orders_id]), ['class' => "btn", 'target'=>"_blank"])}
    {Html::input('button', '', IMAGE_REASSIGN, ['class' => "btn btn-primary btn-process-order", 'onclick' => "reassignOrder("|cat:$oInfo->orders_id|cat:")" ])}
    {if $ext = \common\helpers\Acl::checkExtensionAllowed('MergeOrders', 'allowed')}{$ext::actionOrderactions($oInfo->orders_id)}{/if}
</div>
