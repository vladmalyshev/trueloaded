<div class="attr-box attr-box-3">
      <div class="widget widget-attr-box box box-no-shadow" style="margin-bottom: 0;">
        <div class="widget-header">
          <h4>{$smarty.const.FIND_PRODUCTS}</h4>
          <div class="box-head-serch after">
            <input type="search" id="element-search-by-products" placeholder="{$smarty.const.SEARCH_BY_ATTR}" class="form-control">
            <button onclick="return false"></button>
          </div>
        </div>
        <div class="widget-content">
          <div id="element-search-products" size="25" style="width: 100%; height: 100%; border: none;">
          </div>
        </div>
      </div>
    </div>
    <div class="attr-box attr-box-2">
      <span class="btn btn-primary btn-select-item" onclick="selectItem()"></span>
    </div>
    
    <!--<div class="attr-box attr-box-3">
      <div class="widget-new widget-attr-box box box-no-shadow" style="margin-bottom: 0;">
        <div class="widget-header">
          <h4>{$smarty.const.FIELDSET_ASSIGNED_PRODUCTS}</h4>
          <div class="box-head-serch after">
            <input type="search" id="search-elements-assigned" placeholder="{$smarty.const.SEARCH_BY_ATTR}" class="form-control">
            <button onclick="return false"></button>
          </div>
        </div>
        <div class="widget-content">
          <table class="table assig-attr-sub-table element-products">
            <thead>
            <tr role="row">
              <th></th>
              <th>{$smarty.const.TEXT_IMG}</th>
              <th>{$smarty.const.TEXT_LABEL_NAME}</th>
              <th></th>
            </tr>
            </thead>
            <tbody id="elements-assigned">
            {foreach $keyword->products as $pKey => $product}
              {include file="new-product.tpl" product=$product}
            {/foreach}
            </tbody>
          </table>
          <input type="hidden" value="" name="element_sort_order" id="element_sort_order"/>
        </div>
      </div>
    </div>-->