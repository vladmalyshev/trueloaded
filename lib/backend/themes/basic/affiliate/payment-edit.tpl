{use class="yii\helpers\Html"}
{use class="yii\helpers\Url"}
<div id="affiliate_management_data">
    {Html::beginForm($url, 'post', ['name' => 'affiliate_edit', 'id' => 'affiliate_payment_edit'])}
    <div class="box-wrap">
        <div class="create-or-wrap after create-cus-wrap">
            <div class="cbox-left">
                <div class="cr-ord-cust">
                    <span>{$smarty.const.TEXT_AFFILIATE}</span>
                    <div class="order-customer-address">
                    {Html::a($affiliateLink, Url::to(['affiliate/edit', 'affiliate_id' => $model->affiliate_id]))}
                    </div>
                </div>
                <div class="cr-ord-cust cr-ord-cust-bmethod">
                    <span>{$smarty.const.TEXT_AFFILIATE_PAYMENT}</span>
                    <div>{$model->affiliate_payment_total}</div>
                    <span>{$smarty.const.TEXT_AFFILIATE_BILLED}</span>
                    <div>{$model->affiliate_payment_date}</div>
                </div>  
                <div class="cr-ord-cust global-currency">
                    <span>{$smarty.const.TEXT_AFFILIATE_PAYING}</span><br>
                    <div>
                        <table cellspacing="0" cellpadding="10" border="1">
                            <thead>
                                {if AFFILIATE_USE_BANK == 'true'}
                                    <th>{$smarty.const.TEXT_AFFILIATE_TRANSFER}</th>
                                {/if}
                                {if AFFILIATE_USE_PAYPAL == 'true'}
                                    <th>{$smarty.const.TEXT_AFFILIATE_PAYPAL}</th>
                                {/if}
                                {if AFFILIATE_USE_CHECK == 'true'}
                                    <th>{$smarty.const.TEXT_AFFILIATE_CHECK_PAY}</th>
                                {/if}
                            </thead>
                            <tbody>
                                {if AFFILIATE_USE_BANK == 'true'}
                                    <td>
                                        {$smarty.const.TEXT_AFFILIATE_BANK}:<br>{$affiliate->affiliate_payment_bank_name}<br>
                                        {$smarty.const.TEXT_AFFILIATE_BRANCH}:<br>{$affiliate->affiliate_payment_bank_branch_number}<br>
                                        {$smarty.const.TEXT_AFFILIATE_SWIFT}:<br>{$affiliate->affiliate_payment_bank_swift_code}<br>
                                        {$smarty.const.TEXT_AFFILIATE_NAME}:<br>{$affiliate->affiliate_payment_bank_account_name}<br>
                                        {$smarty.const.TEXT_AFFILIATE_NUMBER}:<br>{$affiliate->affiliate_payment_bank_account_number}
                                    </td>
                                {/if}
                                {if AFFILIATE_USE_PAYPAL == 'true'}
                                    <td>{$smarty.const.TEXT_AFFILIATE_PAYPAL_ACCOUNT}:<br>{$affiliate->affiliate_payment_paypal}</td>
                                {/if}
                                {if AFFILIATE_USE_CHECK == 'true'}
                                    <td>{$smarty.const.TEXT_AFFILIATE_CHECK}:<br>{$affiliate->affiliate_payment_check}</td>
                                {/if}
                            </tbody>
                        </table>
                    </div>
                </div>
                    
            </div>
            <div class="cbox-right">
                <div style="min-height: 470px;">
                    <table class="table table-striped table-selectable table-bordered table-hover table-responsive datatable no-footer table-no-search table-no-pagination" checkable_list="9" data_ajax="{$app->urlManager->createUrl(['affiliate/payment-status-history', 'affiliate_payment_id' => $model->affiliate_payment_id])}">
                     <thead>
                     <tr>
                         <th>{$smarty.const.TEXT_AFFILIATE_NEW_VALUE}</th>
                         <th>{$smarty.const.TEXT_AFFILIATE_OLD_VALUE}</th>
                         <th>{$smarty.const.TEXT_AFFILIATE_DATE_ADDED}</th>
                         <th>{$smarty.const.TEXT_AFFILIATE_AFFILIATE_NOTIFIED}</th>
                     </tr>
                     </thead>
                     <tbody>
                     </tbody>
                 </table>
                </div>
                <div>
                    
                    Status: {Html::dropDownList('status', $model->affiliate_payment_status, $ordersStatuses, ['class'=>'form-control'])}<br>
                    {Html::checkbox('notify', true, ['label' => 'Notify Affiliate'])}<br>
                    
                </div>
            </div>
        </div>
    </div>
    <div class="btn-bar">
        <div class="btn-left"><a href="javascript:void(0)" onclick="return backStatement();" class="btn btn-cancel-foot">{$smarty.const.IMAGE_BACK}</a></div>
        <div class="btn-right"><button class="btn btn-confirm">{$smarty.const.IMAGE_UPDATE}</button></div>
    </div>
    {Html::input('hidden', 'affiliate_payment_id', $model->affiliate_payment_id)}
    {Html::endForm()}
</div>
<script type="text/javascript">
function backStatement() {
    window.location.href = '{$app->urlManager->createAbsoluteUrl('affiliate/payment')}';
    return false;
}
</script>