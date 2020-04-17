<div class="order-total-items">
    <div>
        <b>
            <span>{$items}</span> {$smarty.const.ITEMS_IN_TOTAL}            
        </b>
    </div>
    {if $pData && !empty($pData->collection_points_text)}
    <div>
        <b>
            <span>{$smarty.const.TEXT_ORDER_POINT_TO_TEXT}:</span>
        </b>
        <pre>{$pData->collection_points_text}</pre>
    </div>
    {/if}
    <div>
    {if $ext = \common\helpers\Acl::checkExtension('VatOnOrder', 'process_order_message')}
        {$ext::process_order_message($order)}
    {else}
        <span class="dis_module">{$smarty.const.ENTRY_BUSINESS}</span>
    {/if}
    </div>
    {if $shipping_weight > 0}
    <div>{$smarty.const.TEXT_ACTUAL_WEIGHT_NP}:<b> {number_format($shipping_weight, 2)}</b></div>
    {/if}
    {$volume = \common\helpers\Order::getOrderVolumeWeight($order->order_id)}
    {if $volume > 0}
        <div>{$smarty.const.TEXT_ACTUAL_VOLUME_WEIGHT_KG}:<b> {number_format($volume, 2)}</b></div>
    {/if}
    {if $ext = \common\helpers\Acl::checkExtension('Neighbour', 'allowed')}
        {$ext::showDetails($order->order_id)}
    {/if}
    {if $ext = \common\helpers\Acl::checkExtension('GiftWrapMessage', 'allowed')}
        {$ext::instance()->adminOrderView($order)}
    {/if}
    <div>
    {if $parent}
        <pre>{$smarty.const.TEXT_CREATED_BY} {$parent->getShortName()}: {$parent->model->orders_id}</pre>
    {/if}
    </div>
</div>
