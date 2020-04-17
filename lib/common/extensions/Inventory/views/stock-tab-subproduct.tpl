{use class="yii\helpers\Html"}
{use class="common\helpers\Suppliers"}
<div class="t-row">
    <div class="widget-content inventoryqtyupdate">
        <div class="stock-block">
            <div class="available-stock">
                <div>{$smarty.const.TEXT_STOCK_QUANTITY_INFO}</div>
                <div class="val" name="inventoryqty_{$inventory['uprid']}_info">{$inventory['products_quantity']}</div>
                <input type="hidden" name="inventoryqty_{$inventory['uprid']}" value="{$inventory['products_quantity']}">
            </div>
            <div class="temporary">
                <div>{$smarty.const.TEXT_STOCK_TEMPORARY_QUANTITY}</div>
                <div class="val" name="temporary_quantity_{$inventory['uprid']}_info">{$inventory['temporary_quantity']}</div>
                <input type="hidden" name="temporary_quantity_{$inventory['uprid']}" value="{$inventory['temporary_quantity']}">
            </div>
            <div class="total-allocated">
                <div>{$smarty.const.TEXT_STOCK_ALLOCATED_QUANTITY}</div>
                <div class="val" name="allocated_quantity_{$inventory['uprid']}_info">{$inventory['allocated_quantity']}</div>
                <input type="hidden" name="allocated_quantity_{$inventory['uprid']}" value="{$inventory['allocated_quantity']}">
            </div>
            <div class="real-stock-total">
                <div>{$smarty.const.TEXT_STOCK_WAREHOUSE_QUANTITY}</div>
                <div class="val" name="warehouse_quantity_{$inventory['uprid']}_info">{$inventory['warehouse_quantity']}</div>
                <input type="hidden" name="warehouse_quantity_{$inventory['uprid']}" value="{$inventory['warehouse_quantity']}">
            </div>
            <div class="available">
                <div>{$smarty.const.TEXT_STOCK_SUPPLIERS_QUANTITY}</div>
                <div class="val" name="suppliers_quantity_{$inventory['uprid']}_info">{$inventory['suppliers_quantity']}</div>
                <input type="hidden" name="suppliers_quantity_{$inventory['uprid']}" value="{$inventory['suppliers_quantity']}">
            </div>
            <div class="ordered-stock">
                <div>{$smarty.const.TEXT_STOCK_ORDERED_QUANTITY}</div>
                <div class="val" name="ordered_quantity_{$inventory['uprid']}_info">{\common\helpers\Product::getStockOrdered($inventory['uprid'], true)}</div>
                <input type="hidden" name="ordered_quantity_{$inventory['uprid']}" value="{\common\helpers\Product::getStockOrdered($inventory['uprid'], true)}">
            </div>
            <div class="buttons">
            </div>
            <div class="stock-availability">
                <label>{$smarty.const.TEXT_STOCK_INDICATION}</label>
                {tep_draw_pull_down_menu('inventorystock_indication_'|cat:$inventory['uprid'], \common\classes\StockIndication::get_variants(true, $inventory['product_type'] ), $inventory['stock_indication_id'], 'readonly="readonly" disabled="disabled" class="form-control form-control-small stock-indication-id"')}
            </div>
            <div class="delivery-terms">
                <div class="delivery-term-section" {if $is_virtual}style="display:none;"{/if}>
                    <label>{$smarty.const.TEXT_STOCK_DELIVERY_TERMS}</label>
                    {tep_draw_pull_down_menu('inventorystock_delivery_terms_'|cat:$inventory['uprid'], \common\classes\StockIndication::get_delivery_terms(), $inventory['stock_delivery_terms_id'], 'readonly="readonly" disabled="disabled" class="form-control form-control-small"')}
                </div>
            </div>
            <div class="links">
                {if substr($inventory['uprid'], 0, 2) != '0{'}
                    {if $isStockUnlimited != true}
                        {if count(Suppliers::getSuppliersList($inventory['stock_uprid'])) > 1}
                            <a href="{Yii::$app->urlManager->createUrl(['categories/suppliers-stock', 'prid' => $inventory['stock_uprid']])}" class="right-link">{$smarty.const.TEXT_SUPPLIERS_STOCK}</a>
                        {/if}
                        {if \common\helpers\Warehouses::get_warehouses_count() > 1}
                            <a href="{Yii::$app->urlManager->createUrl(['categories/warehouses-stock', 'prid' => $inventory['stock_uprid']])}" class="right-link">{$smarty.const.TEXT_WAREHOUSES_STOCK}</a>
                        {/if}
                    {/if}
                    {if (int)$inventory['temporary_quantity'] > 0}
                        <a href="{Yii::$app->urlManager->createUrl(['categories/temporary-stock', 'prid' => $inventory['stock_uprid']])}" class="right-link">{$smarty.const.TEXT_TEMPORARY_STOCK}</a>
                    {/if}
                    {if \common\helpers\Acl::checkExtension('ProductStockHistory', 'productBlock')}
                        {\common\extensions\ProductStockHistory\ProductStockHistory::productBlock($inventory['stock_uprid'])}
                    {else}
                        <span class="right-link dis_module">{$smarty.const.TEXT_STOCK_HISTORY}</span>
                    {/if}
                {/if}
                <div class="disabled">
                    {if substr($inventory['uprid'], 0, 2) != '0{' && \common\helpers\Acl::checkExtension('ProductAssets', 'adminProductPopup')}
                        <a href="{Yii::$app->urlManager->createUrl(['categories/product-assets', 'prid' => $inventory['uprid']])}" class="right-link">{$smarty.const.TEXT_PRODUCT_ASSETS}</a>
                    {/if}
                </div>
            </div>
        </div>
    </div>
</div>
