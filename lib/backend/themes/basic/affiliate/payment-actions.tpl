<div class="or_box_head">{$affiliate_firstname} {$affiliate_lastname}</div>
<div class="btn-toolbar btn-toolbar-order">
    <a class="btn btn-edit btn-no-margin" href="{$app->urlManager->createUrl(['affiliate/payment-edit', 'affiliate_payment_id' => $affiliate_payment_id])}">{$smarty.const.IMAGE_EDIT}</a><button class="btn btn-delete" onclick="deleteItemConfirm({$affiliate_payment_id})">{$smarty.const.IMAGE_DELETE}</button>
</div>