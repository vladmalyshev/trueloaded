{use class="yii\helpers\Html"}
{Html::beginForm(Yii::$app->urlManager->createUrl('freeze-stock/warehouse-location'), 'post', ['id' => 'order_update_form', 'onSubmit' => 'return false;'])}
{tep_draw_hidden_field('products_id', $products_id)}
<div class="popup-heading">{$smarty.const.TEXT_STOCK_CHECK}</div>
<div class="popup-content">
    <div>
        <label>{$smarty.const.TABLE_HEADING_LOCATION}:</label>
    {foreach $locationList as $key => $location}
    <div class="form-group">
            <label class="control-label">{$location.name}</label>
            <div class="input-slider">
                    <div class="slider-controls slider-value-top">
                            {Html::input('text', 'stock_equal_qty['|cat:$location.suppliers_id|cat:']['|cat:$location.warehouse_id|cat:']['|cat:$location.location_id|cat:']', $location.qty, ['class'=>'form-control form-control-small-qty', 'id' => 'stock_equal_qty_'|cat:$key, 'onchange' => 'updateEqualSlider('|cat:$key|cat:');'])}
                    </div>
                    <div id="slider-equal-{$key}"></div>
            </div>
        <div class="available-stock-info">{$smarty.const.AVAILABLE_STOCK}: <span class="available-stock-val">{$location.qty}</span></div>
    </div>
    {/foreach}
    <div class="form-group summary">
        <label class="control-label">{$smarty.const.TEXT_SUMMARY}:</label>
        <div class="">
                <span id="slider-equal-qty-total">0</span>
        </div>
    </div>
    <div class="">
        <div class="">
            <span class="btn btn-primary" onclick="doProductUpdateSubmit()">{$smarty.const.IMAGE_APPLY}</span>
        </div>
    </div>
    </div>        
</div>
{Html::endForm()}
<script type="text/javascript">
var equal_allocated_stock = [];
function updateEqualSlider(id) {
    var val = $('#stock_equal_qty_'+id).val();
    $('#slider-equal-'+id).slider('value', val);
    equal_allocated_stock[id] = parseInt(val);
    updateEqualSliderTotal();
}
function updateEqualSliderTotal() {
    var total_allocated_stock = 0;
    for (var i in equal_allocated_stock) {
        total_allocated_stock += equal_allocated_stock[i];
    } 
    $('#slider-equal-qty-total').text(total_allocated_stock);
}
{foreach $locationList as $key => $location}
    equal_allocated_stock[{$key}] = {$location.qty};
    $( '#slider-equal-{$key}' ).slider({
            range: 'min',
            value: {$location.qty},
            min: 0,
            max: {max($location.qty, 100)},
            slide: function( event, ui ) {
                equal_allocated_stock[{$key}] = ui.value;
                updateEqualSliderTotal();
                $('#stock_equal_qty_{$key}').val(ui.value);
            }
    });
{/foreach}
function doProductUpdateSubmit() {
    $.post("{Yii::$app->urlManager->createUrl('freeze-stock/warehouse-location')}", $('form#order_update_form').find('input').serialize(), function(response, status) {
        if (status == 'success') {
            if (response.message != '') {
                alert(response.message);
            }
            if (response.status == 'ok') {
                $('.popup-box:last').trigger('popup.close');
                $('.popup-box-wrap:last').remove();
                var table = $('.table').DataTable();
                table.draw(false);
            }
        }
    }, 'json');
    return false;
}
updateEqualSliderTotal();
</script>