{use class="yii\helpers\Html"}
{use class="backend\models\EP\Datasource\HolbiSoap"}
{assign var="customUniqId" value="0"}
{$customUniqId=$customUniqId+1}
<div class="scroll-table-workaround" id="ep_datasource_config">
{if empty($apitemplate) }
  {include file='holbi-soap-api.tpl'}
{else }
  {include file=$apitemplate}
{/if}

    <div class="widget box">
        <div class="widget-header">
            <h4>Product</h4>
            <div class="toolbar no-padding">
              <div class="btn-group">
                <span class="btn btn-xs widget-collapse"><i class="icon-angle-down"></i></span>
              </div>
            </div>
        </div>
        <div class="widget-content">
            <div class="w-line-row w-line-row-1">
                <div class="wl-td">
                    <label>Create product:</label>
                    Create Server products on this client {Html::checkbox('datasource['|cat:$code|cat:'][products][create_on_client]', !!$products['create_on_client'], ['value'=>'1', 'class' => 'default_switcher'])}
                    &nbsp;&nbsp;
                    Create this store products on server {Html::checkbox('datasource['|cat:$code|cat:'][products][create_on_server]', !!$products['create_on_server'], ['value'=>'1', 'class' => 'default_switcher'])}
                </div>
            </div>
            <div class="w-line-row w-line-row-1">
                <div class="wl-td">
                    <label>Update product:</label>
                    Update imported client products modified on the server {Html::checkbox('datasource['|cat:$code|cat:'][products][update_on_client]', !!$products['update_on_client'], ['value'=>'1', 'class' => 'default_switcher'])}
                    <br>
                    Update local modified products on the server {Html::checkbox('datasource['|cat:$code|cat:'][products][update_on_server]', !!$products['update_on_server'], ['value'=>'1', 'class' => 'default_switcher'])}
                </div>
            </div>
            <div class="w-line-row w-line-row-1">
                <div class="wl-td">
                    <label>{$smarty.const.TEXT_SOAP_PRODUCT_CUSTOMIZE_FLAGS}</label>
                    {Html::checkbox('datasource['|cat:$code|cat:'][products][custom_flags]', !!$products['custom_flags'], ['value'=>'1', 'class' => 'default_switcher js-soap_custom_flags','data-rel'=>'js-soap-custom-flags_rel_'|cat:$customUniqId])}
                </div>
            </div>
            <div class="w-line-row w-line-row-1 js-soap-custom-flags_rel_{$customUniqId}">
                <div class="wl-td">
                    <label></label>
                    <table class="table tabl-res table-striped table-hover table-responsive table-bordered table-switch-on-off double-grid">
                        <thead>
                        <tr>
                            <th>{$smarty.const.TEXT_SOAP_PRODUCT_CUSTOM_HEAD_KEY}</th>
                            <th width="20%">{$smarty.const.TEXT_SOAP_PRODUCT_CUSTOM_HEAD_SERVER}</th>
                            <th width="20%">{$smarty.const.TEXT_SOAP_PRODUCT_CUSTOM_HEAD_CLIENT}</th>
                        </tr>
                        </thead>
                        <tbody>
                        {foreach HolbiSoap::productFlags() as $flagInfo}
                            <tr class="js-switch-holder{if $flagInfo['only_one_active']} js-switch-one-on{/if}">
                                <td>{$flagInfo['label']}</td>
                                {if $flagInfo['select']}
                                    <td>{if $flagInfo['server']}{Html::dropDownList('datasource['|cat:$code|cat:'][products]['|cat:$flagInfo['server']|cat:']', $products[$flagInfo['server']], $flagInfo['select'], ['class' => 'form-control'])}{else}&nbsp;{/if}</td>
                                    <td>{if $flagInfo['client']}{Html::dropDownList('datasource['|cat:$code|cat:'][products]['|cat:$flagInfo['client']|cat:']', $products[$flagInfo['client']], $flagInfo['select'], ['class' => 'form-control'])}{else}&nbsp;{/if}</td>
                                {else}
                                    <td>{if $flagInfo['server']}{Html::checkbox('datasource['|cat:$code|cat:'][products]['|cat:$flagInfo['server']|cat:']', !!$products[$flagInfo['server']], ['value'=>'1', 'class' => 'default_switcher'])}{else}&nbsp;{/if}</td>
                                    <td>{if $flagInfo['client']}{Html::checkbox('datasource['|cat:$code|cat:'][products]['|cat:$flagInfo['client']|cat:']', !!$products[$flagInfo['client']], ['value'=>'1', 'class' => 'default_switcher'])}{else}&nbsp;{/if}</td>
                                {/if}
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="w-line-row w-line-row-1">
                <div class="wl-td">
                    <label>If server remove product:</label> {Html::dropDownList('datasource['|cat:$code|cat:'][products][action_for_server_remove]', $products['action_for_server_remove'], $ServerProductsRemovedVariants, ['class' => 'form-control'])}
                </div>
            </div>
            <div class="w-line-row w-line-row-1">
                <div class="wl-td">
                    <label>Product images:</label> {Html::dropDownList('datasource['|cat:$code|cat:'][products][images_copy]', $products['images_copy']['value'], $products['images_copy']['items'], $products['images_copy']['options'])}
                </div>
            </div>
            <div class="w-line-row w-line-row-1">
                <div class="wl-td">
                    <label>New product Stock availability:</label> {Html::dropDownList('datasource['|cat:$code|cat:'][product_new][stock_indication_id]', $product_new['stock_indication_id'], $StockIndicationVariants, ['class' => 'form-control'])}
                </div>
            </div>
            <div class="w-line-row w-line-row-1">
                <div class="wl-td">
                    <label>New product Stock delivery terms:</label> {Html::dropDownList('datasource['|cat:$code|cat:'][product_new][stock_delivery_terms_id]', $product_new['stock_delivery_terms_id'], $StockDeliveryTermsVariants, ['class' => 'form-control'])}
                </div>
            </div>
        </div>
    </div>

    <div class="widget box">
        <div class="widget-header">
            <h4>Synchronization Order Status mapping</h4>
            <div class="toolbar no-padding">
              <div class="btn-group">
                <span class="btn btn-xs widget-collapse"><i class="icon-angle-down"></i></span>
              </div>
            </div>
        </div>
        <div class="widget-content">
            <div class="wl-td">
                <label><b>Local order status</b></label> <label class="radio-inline"><b>Server Order status</b></label>
            </div>
            {foreach $LocalShopOrderStatuses as $LocalShopOrderStatus}
                <div class="wl-td">
                    {if strpos($LocalShopOrderStatus.id,'group')===0 }
                        <label><b>{$LocalShopOrderStatus.text}</b></label> &nbsp;
                    {else}
                        <label>{$LocalShopOrderStatus.text}</label> {Html::dropDownList('datasource['|cat:$code|cat:'][status_map_local_to_server]['|cat:$LocalShopOrderStatus.status_id|cat:']', $status_map_local_to_server[$LocalShopOrderStatus.status_id], $ServerShopOrderStatusesWithCreate, ['class' => 'form-control js-status-map'])}
                    {/if}
                </div>
            {/foreach}
        </div>
    </div>

    <div class="widget box">
        <div class="widget-header">
            <h4>Orders</h4>
            <div class="toolbar no-padding">
              <div class="btn-group">
                <span class="btn btn-xs widget-collapse"><i class="icon-angle-down"></i></span>
              </div>
            </div>
        </div>
        <div class="widget-content">

            <div class="w-line-row w-line-row-1">
                <div class="wl-td">
                    <label>Export Order method:</label> {Html::dropDownList('datasource['|cat:$code|cat:'][order][export_as]', $order['export_as']['value'], $order['export_as']['items'], $order['export_as']['options'])}
                </div>
            </div>

            <div class="w-line-row w-line-row-1">
                <div class="wl-td">
                    <label>Export Order statuses:</label> {Html::dropDownList('datasource['|cat:$code|cat:'][order][export_statuses]', $order['export_statuses']['value'], $order['export_statuses']['items'], $order['export_statuses']['options'])}
                </div>
            </div>

            <div class="w-line-row w-line-row-1">
                <div class="wl-td">
                    <label>Set Order status after export:</label> {Html::dropDownList('datasource['|cat:$code|cat:'][order][export_success_status]', $order['export_success_status']['value'], $order['export_success_status']['items'], $order['export_success_status']['options'])}
                </div>
            </div>

            <div class="w-line-row w-line-row-1">
                <div class="wl-td">
                    <label>Server dispatched statuses:</label>
                    {if $order['server_dispatched_statuses']['fetched']}
                        {Html::dropDownList('datasource['|cat:$code|cat:'][order][server_dispatched_statuses]', $order['server_dispatched_statuses']['value'], $order['server_dispatched_statuses']['items'], $order['server_dispatched_statuses']['options'])}
                    {else}
                        Department API configuration error
                        {if $order['server_dispatched_statuses']['value']}
                            {if is_array($order['server_dispatched_statuses']['value'])}
                                {foreach $order['server_dispatched_statuses']['value'] as $_keep_value}
                                    {Html::hiddenInput('datasource['|cat:$code|cat:'][order][server_dispatched_statuses][]', $_keep_value)}
                                {/foreach}
                            {else}
                                {Html::hiddenInput('datasource['|cat:$code|cat:'][order][server_dispatched_statuses]', $order['server_dispatched_statuses']['value'])}
                            {/if}
                        {/if}
                    {/if}
                </div>
            </div>
            <div class="w-line-row w-line-row-1">
                <div class="wl-td">
                    <label>Set Order status after server dispatch:</label> {Html::dropDownList('datasource['|cat:$code|cat:'][order][local_dispatch_status]', $order['local_dispatch_status']['value'], $order['local_dispatch_status']['items'], $order['local_dispatch_status']['options'])}
                </div>
            </div>
            <div class="w-line-row w-line-row-1">
                <div class="wl-td">
                    <label>Order modification update:</label> {Html::dropDownList('datasource['|cat:$code|cat:'][order][disable_order_update]', $order['disable_order_update']['value'], $order['disable_order_update']['items'], $order['disable_order_update']['options'])}
                </div>
            </div>
        </div>
    </div>

    <div class="widget box">
        <div class="widget-header">
            <h4>Customer</h4>
            <div class="toolbar no-padding">
              <div class="btn-group">
                <span class="btn btn-xs widget-collapse"><i class="icon-angle-down"></i></span>
              </div>
            </div>
        </div>
        <div class="widget-content">
            <div class="w-line-row w-line-row-1">
                <div class="wl-td">
                    <label>Address book synchronization (to server):</label> {Html::dropDownList('datasource['|cat:$code|cat:'][customer][ab_sync_server]', $customer['ab_sync_server']['value'], $customer['ab_sync_server']['items'], $customer['ab_sync_server']['options'])}
                </div>
            </div>
            <div class="w-line-row w-line-row-1">
                <div class="wl-td">
                    <label>Address book synchronization (to local store):</label> {Html::dropDownList('datasource['|cat:$code|cat:'][customer][ab_sync_client]', $customer['ab_sync_client']['value'], $customer['ab_sync_client']['items'], $customer['ab_sync_client']['options'])}
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $('.js-status-map').on('change',function(event){
        var $target = $(event.target);
        if ( $target.val().indexOf('create_')===0 ) return;
        $('.js-status-map').not($target).each(function () {
            if ( $(this).val()==$target.val() ) {
                $(this).val('');
            }
        });
    });
    $('.default_switcher').bootstrapSwitch({
        onText: "{$smarty.const.SW_ON}",
        offText: "{$smarty.const.SW_OFF}",
        handleWidth: '20px',
        labelWidth: '24px',
        onSwitchChange: function () {
            var $holder = $(this).parents('.js-switch-holder');
            if ($holder.hasClass('js-switch-one-on')) {
                $holder.find('input[type="checkbox"]').not(this).each(function () {
                    if ($(this).is(':checked')) {
                        this.checked = false;
                        $(this).bootstrapSwitch('state', this.checked, true);
                    }
                });
            }
            if ($(this).hasClass('js-soap_custom_flags') || $(this).hasClass('js-soap_main_flags')) {
                if ($(this).is(':checked')) {
                    $('.' + $(this).attr('data-rel')).show();
                } else {
                    if ($(this).hasClass('js-soap_main_flags')) {
                        $('.' + $(this).attr('data-rel') + ' .js-soap_custom_flags').each(function () {
                            if ($(this).is(':checked')) {
                                $(this).trigger('click');
                            }
                        });
                    }
                    $('.' + $(this).attr('data-rel')).hide();
                }
            }
        }
    });
    $('.js-soap_custom_flags, .js-soap_main_flags').each(function(){
        if (!this.checked && $(this).attr('data-rel')) $('.'+$(this).attr('data-rel')).hide();
    });

    $('.js-switch-one-on').each(function(){
        var $selectGroup = $(this);
        var $selects = $selectGroup.find('select');
        if ( $selects.length==0 ) return;
        $selects.on('change',function() {
            if ( $(this).val()!='disabled' ) $selects.not(this).val('disabled');
        })
    });

    $('#ep_datasource_config .widget-collapse').off('click').on('click', function() {
			var widget         = $(this).parents(".widget");
			var widget_content = widget.children(".widget-content");
			var widget_chart   = widget.children(".widget-chart");
			var divider        = widget.children(".divider");

			if (widget.hasClass('widget-closed')) {
				// Open Widget
				$(this).children('i').removeClass('icon-angle-up').addClass('icon-angle-down');
				widget_content.slideDown(200, function() {
					widget.removeClass('widget-closed');
				});
				widget_chart.slideDown(200);
				divider.slideDown(200);
			} else {
				// Close Widget
				$(this).children('i').removeClass('icon-angle-down').addClass('icon-angle-up');
				widget_content.slideUp(200, function() {
					widget.addClass('widget-closed');
				});
				widget_chart.slideUp(200);
				divider.slideUp(200);
			}
		});
    $('#ep_datasource_config .widget-collapse:gt(0)').trigger('click');

</script>