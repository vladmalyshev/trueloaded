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
                {if $isStockUnlimited != true}
                    {if substr($inventory['uprid'], 0, 2) != '0{'}
                        <a href="{Yii::$app->urlManager->createUrl(['categories/update-stock', 'products_id' => $inventory['uprid']])}" class="btn right-link" data-class="update-stock-popup">{$smarty.const.TEXT_UPDATE_STOCK}</a>
                    {/if}
                {/if}
                {if substr($inventory['uprid'], 0, 2) != '0{'}
                    {if \common\helpers\Warehouses::get_warehouses_count() > 1}
                        <a href="{Yii::$app->urlManager->createUrl(['categories/order-reallocate', 'prid' => $inventory['uprid']])}" class="btn right-link" data-class="product-relocate-popup">{$smarty.const.TEXT_ORDER_RELOCATE}</a>
                        {if $isStockUnlimited != true}
                            <a href="{Yii::$app->urlManager->createUrl(['categories/warehouses-relocate', 'prid' => $inventory['uprid']])}" class="btn right-link">{$smarty.const.TEXT_WAREHOUSES_RELOCATE}</a>
                        {/if}
                    {/if}
                {/if}
            </div>
            <div class="stock-availability">
                <label>{$smarty.const.TEXT_STOCK_INDICATION}</label>
                {tep_draw_pull_down_menu('inventorystock_indication_'|cat:$inventory['uprid'], \common\classes\StockIndication::get_variants(true, $inventory['product_type'] ), $inventory['stock_indication_id'], 'class="form-control form-control-small stock-indication-id"')}
            </div>
            <div class="delivery-terms">
                <div class="delivery-term-section" {if $is_virtual}style="display:none;"{/if}>
                    <label>{$smarty.const.TEXT_STOCK_DELIVERY_TERMS}</label>
                    {tep_draw_pull_down_menu('inventorystock_delivery_terms_'|cat:$inventory['uprid'], \common\classes\StockIndication::get_delivery_terms(), $inventory['stock_delivery_terms_id'], 'class="form-control form-control-small"')}
                </div>
            </div>
            <div class="links">
                {if substr($inventory['uprid'], 0, 2) != '0{'}
                    {if $isStockUnlimited != true}
                        {if count(Suppliers::getSuppliersList($inventory['uprid'])) > 1}
                            <a href="{Yii::$app->urlManager->createUrl(['categories/suppliers-stock', 'prid' => $inventory['uprid']])}" class="right-link">{$smarty.const.TEXT_SUPPLIERS_STOCK}</a>
                        {/if}
                        {if \common\helpers\Warehouses::get_warehouses_count() > 1}
                            <a href="{Yii::$app->urlManager->createUrl(['categories/warehouses-stock', 'prid' => $inventory['uprid']])}" class="right-link">{$smarty.const.TEXT_WAREHOUSES_STOCK}</a>
                        {/if}
                    {/if}
                    {if (int)$inventory['temporary_quantity'] > 0}
                        <a href="{Yii::$app->urlManager->createUrl(['categories/temporary-stock', 'prid' => $inventory['uprid']])}" class="right-link">{$smarty.const.TEXT_TEMPORARY_STOCK}</a>
                    {/if}
                    {if \common\helpers\Acl::checkExtension('ProductStockHistory', 'productBlock')}
                        {\common\extensions\ProductStockHistory\ProductStockHistory::productBlock($inventory['uprid'])}
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
{if $isStockUnlimited != true}
    <input type="hidden" name="inventory_control_present" value="1">
    <div class="t-row our-pr-line">
        <label for="inventory_control_s2"><input type="radio" name="inventory_control_{$inventory['uprid']}" data-ikey="{$ikey}" class="inventory-stock-options" id="inventory_control_s2" value="0" {if $inventory.stock_control=='0'}checked{/if}/>Overall stock</label>
        <label for="inventory_control_s1"><input type="radio" name="inventory_control_{$inventory['uprid']}" data-ikey="{$ikey}" class="inventory-stock-options" id="inventory_control_s1" value="1" {if $inventory.stock_control=='1'}checked{/if}/>Split stock between platforms</label>
        <label for="inventory_control_s0"><input type="radio" name="inventory_control_{$inventory['uprid']}" data-ikey="{$ikey}" class="inventory-stock-options" id="inventory_control_s0" value="2" {if $inventory.stock_control=='2'}checked{/if}/>Assign platform to warehouse</label>
    </div>

    <div class="t-row" id="inventory_stock_by_platforms_{$ikey}"{if $inventory.stock_control!='1'} style="display: none;"{/if}>
        {foreach $inventory.platformStockList as $platform}
            <div class="form-group">
                <label class="col-md-2 control-label">{$platform.name}:</label>
                <div class="col-md-10">
                    <div class="slider-controls slider-value-top">
                        {$smarty.const.TEXT_OPR_QUANTITY} <span id="slider-range-qty-{$ikey}-{$platform.id}"></span>
                    </div>
                    <div id="slider-range-{$ikey}-{$platform.id}" data-ikey="{$ikey}" data-platform-id="{$platform.id}" data-total-stock="{$inventory['products_quantity']}" data-uprid="{$inventory['uprid']}"></div>
                </div>
            </div>
            {Html::hiddenInput('platform_to_qty_'|cat:$inventory['uprid']|cat:'_'|cat:$platform.id, $platform.qty)}
        {/foreach}
        <div class="form-group">
            <label class="col-md-2 control-label">{$smarty.const.TEXT_SUMMARY}:</label>
            <div class="slider-controls slider-value-top">
                <span id="slider-range-qty-total-{$ikey}">0 from {$inventory['products_quantity']}</span>
            </div>
        </div>
    </div>

    <div class="t-row" id="inventory_platform_to_warehouse_{$ikey}"{if $inventory.stock_control!='2'} style="display: none;"{/if}>
        {foreach $inventory.platforWarehouseList as $platform}
            <div class="t-row">
                <div class="t-col-1">
                    <div class="edp-line">
                        <label>{$platform.name}:</label>
                        {tep_draw_pull_down_menu('inventory_platform_to_warehouse_'|cat:$inventory['uprid'], \common\helpers\Warehouses::get_warehouses(), \common\helpers\Warehouses::get_default_warehouse(), 'class="form-control form-control-small"')}
                    </div>
                </div>
            </div>
        {/foreach}
    </div>
{/if}