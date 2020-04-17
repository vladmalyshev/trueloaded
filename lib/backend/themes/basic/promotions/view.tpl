    <div class="or_box_head">{$promo->promo_label}</div>
        <div class="row_or_wrapp">
            <div class="row_or"><div>Promotion Items:</div><div>{$promo->sets|count}</div></div>
        </div>
        <div class="btn-toolbar btn-toolbar-order">
            <a href="{\Yii::$app->urlManager->createUrl(['promotions/edit', 'platform_id' => $promo->platform_id, 'promo_id' => $promo->promo_id])}" class="btn btn-edit btn-no-margin">{$smarty.const.IMAGE_EDIT}</a>
            <button class="btn btn-no-margin btn-delete" onclick="confirmDeletePromo({$promo->promo_id})">{$smarty.const.IMAGE_DELETE}</button>
        </div>