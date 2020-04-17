{use class="\yii\helpers\Url"}
{use class="\yii\helpers\Html"}
{use class="\common\helpers\OrderProduct"}
<div class="popup-heading">{$product['products_quantity']} x {$product['products_name']}</div>
<form id="products_status" action="{Yii::$app->urlManager->createUrl('purchase-orders/products-status-update')}" method="post">
{tep_draw_hidden_field('opID', $product['orders_products_id'])}

<div class="creditHistoryPopup">
  <div class="widget box box-wrapp-blue filter-wrapp">
    <div class="widget-header upd-sc-title">
      <h4>{$smarty.const.TABLE_HEADING_COMMENTS_STATUS}</h4>
    </div>
    <div class="widget-content usc-box usc-box2">
      <div class="f_tab">
        <div class="f_row">
          <div class="f_td">
            <label>{$smarty.const.TABLE_HEADING_COMMENTS}:</label>
          </div>
          <div class="f_td">
            {tep_draw_textarea_field('comments', 'soft', '60', '5', '', 'class="form-control"', false)}
          </div>
        </div>
        <div class="f_row">
          <div class="f_td">
            <label>{$smarty.const.ENTRY_STATUS}</label>
          </div>
          <div class="f_td">
            {tep_draw_pull_down_menu('status', $statuses_array, $product['orders_products_status'], 'class="form-control" onChange="return doCheckOrderProductStatus();"')}
          </div>
        </div>
        {foreach $orderProductArray as $opsId => $locationArray}
            {foreach $locationArray as $locationId => $opArray}
                <div class="f_row update_order_product_holder_{$opsId}">
                    <div class="f_td">
                        <div class="amount">{Html::input('text', ('update_order_product_'|cat:$opsId|cat:'['|cat:$warehouseId|cat:']['|cat:$supplierId|cat:']['|cat:$locationId|cat:']'), $opArray['value'], ['class'=>'form-control form-control-small-qty', 'opsid' => {$opsId}])}</div>
                    </div>
                    <div class="f_td">
                        {$opArray['locationName']}
                        <div id="update_order_product_{$opsId}[{$warehouseId}][{$supplierId}][{$locationId}]"></div>
                    </div>
                </div>
            {/foreach}
        {/foreach}
      </div>
    </div>
  </div>
</div>

<div class="mail-sending noti-btn">
  <div><span class="btn btn-cancel">{$smarty.const.IMAGE_CANCEL}</span></div>
  <div><input type="submit" class="btn btn-confirm" value="{$smarty.const.IMAGE_UPDATE}"></div>
</div>

</form>

<script>
    var table;
    (function($) {
        doCheckOrderProductStatus();
    })(jQuery);

    $('#products_status').submit(function(e) {
        e.preventDefault();
        $.post('{Yii::$app->urlManager->createUrl('purchase-orders/products-status-update')}', $(this).serializeArray(), function(response, status) {
            if (status == 'success') {
                if (response.status == 'ok') {
                    $('#products-status-{$product['orders_products_id']}').css('color', response.ops.colour).text(response.ops.status);
                    $('#products-qty-cnld-{$product['orders_products_id']}').text(response.op.qty_cnld);
                    $('#products-qty-rcvd-{$product['orders_products_id']}').text(response.op.qty_rcvd);
                    $('#order-status').val(response.os.status);
                    $('#products_status .btn-cancel').trigger('click');
                }
            }
        }, 'json');
    });

    function doCheckOrderProductStatus() {
        let opStatus = $('form#products_status select[name="status"]').val();
        $('form#products_status div[class*="update_order_product_holder_"]').hide();
        $('form#products_status input[name^="update_order_product_"]').each(function() {
            if (typeof($(this).attr('default')) == 'undefined') {
                $(this).attr('default', $(this).val());
            }
            $(this).val($(this).attr('default')).change();
        });
        $('form#products_status div.update_order_product_holder_' + opStatus).show();
        return true;
    }
    {foreach $orderProductArray as $opsId => $locationArray}
        {foreach $locationArray as $locationId => $opArray}
            $('form#products_status div[id="update_order_product_{$opsId}[{$warehouseId}][{$supplierId}][{$locationId}]"]').slider({
                range: 'min',
                value: {$opArray['value']},
                min: {$opArray['min']},
                max: {$opArray['max']},
                slide: function(event, ui) {
                    let isError = false;
                    let input = $('form#products_status input[name="' + $(this).attr('id') + '"]');
                    if (input.length > 0) {
                        let value = ui.value;
                        if (value < {$opArray['min']}) {
                            value = {$opArray['min']};
                        }
                        if (value > {$opArray['max']}) {
                            value = {$opArray['max']};
                        }
                        value = parseInt(value);
                        {if isset($opArray['awaiting'])}
                            let quantity = 0;
                            let inputValue = parseInt(input.val());
                            $('form#products_status input[name^="update_order_product_' + input.attr('opsid') + '["]').each(function() {
                                quantity += parseInt($(this).val());
                            });
                            if ((quantity - inputValue + value) > {$opArray['awaiting']}) {
                                value = ({$opArray['awaiting']} - quantity + inputValue);
                                isError = true;
                            }
                        {/if}
                        $(this).slider('value', value);
                        input.val(value);
                        if (isError == false) {
                            return true;
                        }
                    }
                    return false;
                }
            });
        {/foreach}
    {/foreach}
    $('form#products_status input[name^="update_order_product_"]').unbind('change').bind('change', function() {
        let slider = $('form#products_status div[id="' + $(this).attr('name') + '"]');
        if (slider.length > 0) {
            slider.slider('option', 'slide').call(slider, null, { value: $(this).val() });
        }
    }).unbind('keypress').bind('keypress', function(event) {
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if (keycode == '13') {
            event.preventDefault();
            return $(this).change();
        }
        return true;
    });
</script>