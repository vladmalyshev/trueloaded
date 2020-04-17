<div class="widget-header widget-header-prod">
    <h4>{$smarty.const.TEXT_PROD_DET}</h4>
    <a name="products"></a>
    <div class="toolbar no-padding">
        <div class="btn-group">
            <span id="orders_list_collapse" class="btn btn-xs widget-collapse"><i class="icon-angle-down"></i></span>
        </div>
    </div>
</div>
<div class="widget-content widget-content-prod_">
    <table class="table" border="0" width="100%" cellspacing="0" cellpadding="2">
        <thead>
        <tr class="dataTableHeadingRow">
            <th class="dataTableHeadingContent left" colspan="2">{$smarty.const.TABLE_HEADING_PRODUCTS}</th>
            <th class="dataTableHeadingContent" width="10%">{$smarty.const.TABLE_HEADING_PRODUCTS_MODEL}</th>
            <th class="dataTableHeadingContent" width="8%" align="center">{$smarty.const.TABLE_HEADING_UNIT_PRICE}</th>
            <th class="dataTableHeadingContent" width="8%" align="center">{$smarty.const.TABLE_HEADING_QUANTITY}</th>
            <th class="dataTableHeadingContent" width="8%" align="center">{$smarty.const.TABLE_HEADING_QUANTITY_ORDERED}</th>
            <th class="dataTableHeadingContent" width="8%" align="center">{$smarty.const.TABLE_HEADING_TOTAL_PRICE}</th>
            {*<th class="dataTableHeadingContent" width="100px"></th>*}
        </tr>
        </thead>

        {for $i=0; $i<sizeof($products); $i++}
            <tr class="dataTableRow product_info">
                <td class="dataTableContent box_al_cente" valign="top">
                    {\common\classes\Images::getImage($products[$i]['id'])}
                </td>
                <td class="dataTableContent left" valign="top">
                    <label>{$products[$i]['name']}</label>
                    {if is_array($products[$i]['attributes']) && $products[$i]['attributes']|count > 0}
                        {for $j=0; $j<sizeof($products[$i]['attributes']); $j++}
                            <div class="prop-tab-det-inp"><small>&nbsp;
                                    <i> - {($products[$i]['attributes'][$j]['option'])} : {($products[$i]['attributes'][$j]['value'])}</i></small>
                            </div>
                            <input type="hidden" name="id[{$products[$i]['attributes'][$j]['option_id']}]" data-option="{$products[$i]['attributes'][$j]['option_id']}" value="{$products[$i]['attributes'][$j]['value_id']}">
                        {/for}
                    {/if}
                </td>
                <td class="dataTableContent left" valign="top">
                    <label>{$products[$i]['model']}</label>
                </td>
                <td class="dataTableContent" align="right" valign="top">
                    <label>{$currencies->display_price($products[$i]['price'], $products[$i]['tax'])}</label>
                </td>
                <td class="dataTableContent" align="right" valign="top">
                    <label>{$products[$i]['qty']}</label>
                </td>
                <td class="dataTableContent" align="right" valign="top">
                    <label>{$products[$i]['ordered_qty']}</label>
                </td>
                <td class="dataTableContent" align="right" valign="top">
                    <label>{$currencies->format($products[$i]['price'] * $products[$i]['qty'] )}</label>
                </td>

                {*<td class="dataTableContent adjust-bar" align="center" >*}
                    {*{if $products[$i]['ga']}*}
                        {*<div><a href="{\yii\helpers\Url::to(['orders/addproduct', 'orders_id'=>{$oID}, 'action'=>'show_giveaways'])}" class="popup" data-class="add-product"><i class="icon-pencil"></i></a></div>*}
                    {*{else}*}
                        {*{if $oID}*}
                            {*<div><a href="{\yii\helpers\Url::to(['orders/addproduct', 'action' => 'edit_product', 'products_id' => {$products[$i]['id']}, 'orders_id' => {$oID}])}" class="popup" data-class="edit-product"><i class="icon-pencil"></i></a></div>*}
                        {*{else}*}
                            {*<div><a href="{\yii\helpers\Url::to(['orders/addproduct', 'action' => 'edit_product', 'products_id' => {$products[$i]['id']}])}" class="popup" data-class="edit-product"><i class="icon-pencil"></i></a></div>*}
                        {*{/if}*}
                    {*{/if}*}
                    {*<div class="del-pt" onclick="deleteOrderProduct(this);">*}
                        {*{if $products[$i]['ga']}*}
                            {*{tep_draw_hidden_field('delete_type', 'remove_giveaway')}*}
                        {*{else}*}
                            {*{tep_draw_hidden_field('delete_type', 'remove_product')}*}
                        {*{/if}*}

                    {*</div>*}
                {*</td>*}
            </tr>
        {/for}
    </table>
</div>