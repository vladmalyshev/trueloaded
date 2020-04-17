{use class="frontend\design\Block"}
{use class = 'yii\helpers\Html'}
{use class="frontend\design\Info"}
{*use class="\frontend\design\boxes\PagingBar"*}
<div class="box-block type-1 w-block-box w-listing">
    <div class="block">
        <div class="box-block type-9 special-page">
            <div class="block">
                {*\frontend\design\boxes\Banner::widget(['params' => $params['params'], 'settings' => [['banners_group' => 'left', 'banners_type' => 'banner']]])*}
            </div>
            <div class="block">
                <h1>{$smarty.const.TEXT_PERSONAL_CATALOG}</h1>
                {Block::widget(['name' => 'products', 'params' => $params])}
                {*Html::beginForm(['/shopping-cart', 'action' => 'add_all'], 'post', ['id' => "add_all-form"])}
                    {\frontend\design\boxes\Listing::widget(['params' => $params['params'], 'settings' => [['listing_type' => 'type-1_3']]])}
                    {PagingBar::widget(['params' => $params['params']])}
                    {Html::hiddenInput('personal_catalog', 1)}
                {Html::endForm()*}
                <div class="all-scroll-div">
                    <div class="main-width">
                        <div class="quick-order-total">
                            <div class="already-in-cart">
                                <span class="title">Already in cart:</span>
                                <span class="value" id="total_current">{$total}</span>
                            </div>
                            <div class="total-selected">
                                <span class="title">Selected:</span>
                                <span class="value" id="total_selected">{$selected}</span>
                            </div>
                            <div class="total-total">
                                <span class="title">Total Sum:</span>
                                <span class="value" id="total_total">{$total}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="selected_products"></div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    tl([
        '{Info::themeFile('/js/main.js')}'
    ], function(){
        var bd = $('body');
        bd.on('change keyup blur', '.qty-inp', function () {
            $.post('{Yii::$app->urlManager->createUrl('personal-catalog/recalculate')}',
                $('form#add_all-form').serialize(),
                function (data) {
                    $('#total_current').html(data.current);
                    $('#total_selected').html(data.selected);
                    $('#total_total').html(data.total);
            });
        });
        $('input.qty-inp').first().change();
        {*

        // Commented because the feature from another branch and need modify template @see view on account

        bd.on('click', '.pc-delete-item', function (event) {
            event.preventDefault();
            actionPersonalCatalog(
                '{Yii::$app->urlManager->createUrl('personal-catalog/confirm-delete')}',
                $(this).attr('data-id'),
                '',
            );
        });

        bd.on('click', '.pc-send-shop', function (e) {
            var qty = parseInt($(this).closest('.item').find('.qty-inp').val())
            if (qty > 0) {
                actionPersonalCatalog(
                    '{Yii::$app->urlManager->createUrl('personal-catalog/add-to-cart')}',,
                    $(this).attr('data-id'),
                    $(this).attr('data-cart'),
                    qty
                );
            } else {
                alertMessage('<div style="padding:10px">Select quantity</div>');
            }
        });
        function actionPersonalCatalog(action, id, in_cart, qty) {
            if (qty === undefined) {
                qty = 1;
            }
            $.post(action,
                {
                    products_id: id,
                    pc_in_cart: in_cart,
                    pc_qty: qty,
                    _csrf: $('meta[name=csrf-token]').attr('content')
                }
                , function (d) {
                    if (d.message != undefined) {
                        alertMessage(d.message);
                        setTimeout(window.location.reload(), 3000);
                    } else {
                        alertMessage(d);
                    }
                });
        }
        {**}
    });
</script>
