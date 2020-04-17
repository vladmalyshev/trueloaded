{use class = 'yii\helpers\Html'}
<div class="page-confirmation">
    <div>
        {$message}
    </div>
    <div class="buttons">
        <div class="right-buttons">
            {Html::submitButton($smarty.const.CONFIRM_DELETE_FROM_PC, ['class' => 'btn', 'id' => 'confirmButton'])}
            {Html::button($smarty.const.CANCEL, ['class' => 'btn cancel-button'])}
        </div>
    </div>
</div>
<script>
    $('.cancel-button').on('click', function (e) {
        e.preventDefault();
        $('.pop-up-close').click();
    });
    $('#confirmButton').on('click', function (e) {
        e.preventDefault();
        $('.pop-up-close').click();
        $.post("{Yii::$app->urlManager->createUrl(['personal-catalog/delete'])}",
            {
                _csrf: $('meta[name="csrf-token"]').attr('content'),
                products_id: '{$productId}',
                personalCatalogButtonWrapId: '{$personalCatalogButtonWrapId}',
                reload: {$reload}
            }
            , function (response) {
                alertMessage(response.message);
                if (response.hasOwnProperty('button') && response.button.length > 0) {
                    $('#personal-button-wrap-{$personalCatalogButtonWrapId}').html(response.button);
                }
                if(response.hasOwnProperty('reload') && response.reload === 1) {
                    setTimeout(window.location.reload(), 3000);
                }
            });
    });
</script>
