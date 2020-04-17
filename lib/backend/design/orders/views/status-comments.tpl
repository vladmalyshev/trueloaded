{use class="yii\helpers\Html"}
<div class="widget box box-wrapp-blue filter-wrapp">
    <div class="widget-header upd-sc-title">
        <h4>{$smarty.const.TABLE_HEADING_COMMENTS_STATUS}</h4>
    </div>
    <div class="widget-content usc-box usc-box2">
        <ul class="comments-var-box">
            <li>{$manager->render('StatusList',  ['manager' => $manager, 'order' => $order])}</li>
            <li>{$manager->render('OrderComments',  ['manager' => $manager, 'order' => $order])}</li>
            <li>{$manager->render('InvoiceComments',  ['manager' => $manager, 'order' => $order])}</li>
        </ul>
        <div style="text-align:center;">
        {Html::submitInput(IMAGE_UPDATE, ['class' => 'btn btn-confirm'])}
        </div>
    </div>
</div>