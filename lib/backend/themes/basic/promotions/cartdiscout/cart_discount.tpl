{use class="yii\helpers\Html"}
<style>
#tab_properties .fancytree-expander { display:none; }
</style>
<div class="">
    <div>{$promo_description}</div>
    <div class="tabbable tabbable-custom widget-content">
      <ul class="nav nav-tabs">
        <li class="active"><a href="#tab_1_0" data-toggle="tab"><span>{$smarty.const.TEXT_MAIN}</span></a></li>
        {if is_array($promo->settings['groups']) && count($promo->settings['groups'])}
          {foreach $promo->settings['groups'] as $group}
            <li><a href="#tab_1_{$group->groups_id}" data-toggle="tab"><span>{$group->groups_name}</span></a></li>
          {/foreach}
        {/if}
      </ul>
      <div class="tab-content">
            <div class="tab-pane active" id="tab_1_0">
                <div class="our-pr-line after" >
                    <div>
                      <label>{$smarty.const.TEXT_SALE}: <div class="info-hint"><div class="info-hint-box"><div class="info-hint-mustache"></div>{$smarty.const.TEXT_CART_DISCOUNT_SALE}</div></div></label>
                      {Html::textInput('deduction[0]', $promo->settings['conditions'][0]['promo_deduction'], ['class'=>'form-control', 'placeholder' => ''])}
                    </div>
                </div>
                <div class="our-pr-line after" >
                    <div>
                      <label>{$smarty.const.HEADING_TYPE}: <div class="info-hint"><div class="info-hint-box"><div class="info-hint-mustache"></div>{$smarty.const.TEXT_CART_DISCOUNT_TYPE}</div></div></label>
                      {Html::radioList('type[0]', $promo->settings['conditions'][0]['promo_type'], $promo->settings['type'], ['class'=>''])}
                    </div>
                    <div>
                      <label>{$smarty.const.TEXT_CONDITION}: <div class="info-hint"><div class="info-hint-box"><div class="info-hint-mustache"></div>{$smarty.const.TEXT_CART_DISCOUNT_CONDITION}</div></div></label>
                      {Html::radioList('condition[0]', $promo->settings['conditions'][0]['promo_condition'], $promo->settings['condition'], ['class'=>''])}
                    </div>
                </div>
                <div class="our-pr-line after">
                    <div>
                          <label class="sale-info">{$smarty.const.TEXT_PROMO_ORDER_LIMIT}:</label>
                          <div style="display:inline-block;">{Html::textInput('limit[0]', $promo->settings['conditions'][0]['promo_limit'], ['class'=>'form-control', 'placeholder' => ''])}</div><div style="display:inline-block;">{Html::checkbox('limit_block[0]', $promo->settings['conditions'][0]['promo_limit_block'], ['class' => 'check_on_off_block'])} disable purchase</div>
                    </div>
                </div>
            </div>            
            {if is_array($promo->settings['groups']) && count($promo->settings['groups'])}
                {foreach $promo->settings['groups'] as $group}
                    {assign var="visible" value=$promo->settings['conditions'][$group->groups_id]['promo_deduction'] && $promo->settings['conditions'][$group->groups_id]['promo_deduction'] > 0}
                    
                    {if $promo->settings['conditions'][$group->groups_id]['promo_deduction'] == $promo->settings['conditions'][0]['promo_deduction'] && 
                        $promo->settings['conditions'][$group->groups_id]['promo_type'] == $promo->settings['conditions'][0]['promo_type'] && 
                        $promo->settings['conditions'][$group->groups_id]['promo_condition'] == $promo->settings['conditions'][0]['promo_condition']
                    }
                    {$visible = false}
                    {/if}
                    <div class="tab-pane" id="tab_1_{$group->groups_id}">
                    <input type="checkbox" class="groups_on_off" name="use_settings[{$group->groups_id}]" {if $visible}checked{/if} value="1"> {sprintf($smarty.const.TEXT_SET_DETAILS, $group->groups_name)}
                        <div {if $visible}style="display:block;"{else}style="display:none;"{/if}>
                            <div class="our-pr-line after " >
                                <div>
                                  <label>{$smarty.const.TEXT_SALE}: <div class="info-hint"><div class="info-hint-box"><div class="info-hint-mustache"></div>{$smarty.const.TEXT_CART_DISCOUNT_SALE}</div></div></label>
                                  {Html::textInput('deduction['|cat:$group->groups_id|cat:']', $promo->settings['conditions'][$group->groups_id]['promo_deduction'], ['class'=>'form-control', 'placeholder' => ''])}
                                </div>
                            </div>
                            <div class="our-pr-line after" >
                                <div>
                                  <label>{$smarty.const.HEADING_TYPE}: <div class="info-hint"><div class="info-hint-box"><div class="info-hint-mustache"></div>{$smarty.const.TEXT_CART_DISCOUNT_TYPE}</div></div></label>
                                  {Html::radioList('type['|cat:$group->groups_id|cat:']', $promo->settings['conditions'][$group->groups_id]['promo_type'], $promo->settings['type'], ['class'=>''])}
                                </div>
                                <div>
                                  <label>{$smarty.const.TEXT_CONDITION}: <div class="info-hint"><div class="info-hint-box"><div class="info-hint-mustache"></div>{$smarty.const.TEXT_CART_DISCOUNT_CONDITION}</div></div></label>
                                  {Html::radioList('condition['|cat:$group->groups_id|cat:']', $promo->settings['conditions'][$group->groups_id]['promo_condition'], $promo->settings['condition'], ['class'=>''])}
                                </div>
                            </div>
                         </div>
                         <div class="our-pr-line after">
                            <div>
                              <label class="sale-info">{$smarty.const.TEXT_PROMO_ORDER_LIMIT}:</label>
                              <div style="display:inline-block;">{Html::textInput('limit['|cat:$group->groups_id|cat:']', $promo->settings['conditions'][$group->groups_id]['promo_limit'], ['class'=>'form-control', 'placeholder' => ''])}</div><div style="display:inline-block;">{Html::checkbox('limit_block['|cat:$group->groups_id|cat:']', $promo->settings['conditions'][$group->groups_id]['promo_limit_block'], ['class' => 'check_on_off_block'])} disable purchase</div>
                            </div>
                         </div>
                    </div>
                {/foreach}
            {/if}
      </div>
    </div>
</div>
<div style="margin-top: 25px;">
    <div class="after">
        <div class="attr-box " style="width:30%;">
            <div class="widget widget-attr-box box box-no-shadow" style="margin-bottom: 0;">
                {if $promo->useProperties}
                <div class="tabbable tabbable-custom widget-content">
                  <ul class="nav nav-tabs">
                    <li class="active"><a href="#tab_products" data-toggle="tab"><span>Products</span></a></li>
                    <li><a href="#tab_properties" data-toggle="tab"><span>Properties</span></a></li>
                  </ul>
                  <div class="tab-content">
                        <div class="tab-pane active" id="tab_products">
                {/if}
                    <div class="widget-header">
                      <h4>{$smarty.const.FIND_PRODUCTS}</h4>
                        <div class="box-head-serch after">
                            <input type="search" name="search" id="search_text" placeholder="{$smarty.const.SEARCH_BY_ATTR}" class="form-control" autocomplete=off>
                        </div>
                    </div>
                    <div class="widget-content">
                        {include file="tree.tpl"}
                    </div>
                {if $promo->useProperties}
                        </div>
                        <div class="tab-pane" id="tab_properties">
                            <div id="ptree" style="height: 410px;overflow: auto;">
                            </div>
                        </div>
                    </div>
                </div>
                {/if}
            </div>
        </div>
        <div class="attr-box attr-box-2">
          <span class="btn btn-primary btn-select-item" onclick="selectItem()"></span>
        </div>
        <style>
            .qty-element {
                width: 35px;
                margin: 0 0 0 15px;
            }            
        </style>
        {assign var="info" value='<div class="info-hint"><div class="info-hint-box"><div class="info-hint-mustache"></div>Buying minimal products quantity</div></div>'}
        <div class="attr-box" style="width:63%;padding-left: 15px;">
            <div class="tabbable tabbable-custom master-slave">
                <ul class="nav nav-tabs">
                    <li class="active"><a href="#master" data-toggle="tab" data-id="master"><span>{$smarty.const.TEXT_MAIN}</span></a></li>
                    <li><a href="#slave" data-toggle="tab"  data-id="slave"><span>{$smarty.const.TEXT_DEPENDED}</span></a></li>
              </ul>
              <div class="tab-content">
                <div class="tab-pane active" id="master">
                    <div class="widget-new widget-attr-box box box-no-shadow" style="margin-bottom: 0;">
                        <div class="widget-content">
                          <table class="table master-table table-striped table-selectable table-hover table-responsive table-bordered datatable">
                            <thead>
                                
                            <tr role="row">
                              <th class="no-sort" style="width:15%"></th>
                              <th style="width:65%">{$smarty.const.TEXT_LABEL_NAME}</th>
                              <th style="width:20%">Qty</th>
                              <th style="width:5%"></th>
                            </tr>
                            </thead>
                            <tbody id="elements-assigned">
                            {if is_array($promo->settings['assigned_items']['master']) && count($promo->settings['assigned_items']['master'])}                
                                {foreach $promo->settings['assigned_items']['master'] as $pKey => $master}
                                    {if key($master) == 'product'}
                                        {assign var = product value = $master['product']}
                                        <tr>
                                          <td ><input type="hidden" name="products_id[master][]" value="{$product->id}">{Html::img($product->image, ['width' => '50', 'height' => 50])}</td>
                                          <td>{$product->name}</td>
                                          <td>
                                          {$info}
                                          {Html::textInput('prod_master_qty['|cat:$product->id|cat:']', $product->quantity, ['class' => 'qty-element form-control'])}
                                          </td>
                                          <td><a href="javascript:void(0)" onclick="delRow(this)" class="remove-ast"></a></td>
                                        </tr>
                                    {else if key($master) == 'category'}
                                        {assign var = category value = $master['category']}
                                        <tr>
                                          <td><input type="hidden" name="categories_id[master][]" value="{$category->id}">{Html::img($category->image, ['width' => '50', 'height' => 50])}</td>
                                          <td>{$category->name}</td>
                                          <td>
                                          {$info}
                                          {Html::textInput('cat_master_qty['|cat:$category->id|cat:']', $category->quantity, ['class' => 'qty-element form-control'])}
                                          </td>
                                          <td><a href="javascript:void(0)" onclick="delRow(this)" class="remove-ast"></a></td>
                                        </tr>
                                    {else if key($master) == 'property'}
                                        {assign var = property value = $master['property']}
                                        <tr>
                                          <td><input type="hidden" name="properties_id[master][]" value="{$property->id}">{Html::img($property->image, ['width' => '50', 'height' => 50])}</td>
                                          <td>{$property->name}</td>
                                          <td>
                                          {$info}
                                          {Html::textInput('pr_master_qty['|cat:$property->id|cat:']', $property->quantity, ['class' => 'qty-element form-control'])}
                                          </td>
                                          <td><a href="javascript:void(0)" onclick="delRow(this)" class="remove-ast"></a></td>
                                        </tr>
                                    {else if key($master) == 'prvalue'}
                                        {assign var = prvalue value = $master['prvalue']}
                                        <tr>
                                          <td><input type="hidden" name="prvalues_id[master][]" value="{$prvalue->id}">{Html::img($prvalue->image, ['width' => '50', 'height' => 50])}</td>
                                          <td>{$prvalue->name}</td>
                                          <td>
                                          {$info}
                                          {Html::textInput('prv_master_qty['|cat:$prvalue->id|cat:']', $prvalue->quantity, ['class' => 'qty-element form-control'])}
                                          </td>
                                          <td><a href="javascript:void(0)" onclick="delRow(this)" class="remove-ast"></a></td>
                                        </tr>
                                    {/if}
                                {/foreach}
                            {/if}
                            </tbody>
                          </table>
                        </div>
                    </div>
                </div>
                <div class="tab-pane" id="slave">
                {assign var="info_dep" value='<div class="info-hint"><div class="info-hint-box"><div class="info-hint-mustache"></div>Quantity can be added automatic pushing in basket</div></div>'}
                    <div class="widget-new widget-attr-box box box-no-shadow" style="margin-bottom: 0;">                        
                        <div class="widget-content">
                          {Html::checkbox('auto_push', $promo->settings['auto_push'], ['class' => ''])}&nbsp;Automatically add some product to customer basket?
                          <table class="table slave-table table-striped table-selectable table-hover table-responsive table-bordered datatable">
                            <thead>
                            <tr role="row">
                              <th class="no-sort" style="width:15%"></th>
                              <th style="width:65%">{$smarty.const.TEXT_LABEL_NAME}</th>
                              <th style="width:20%">{$smarty.const.TABLE_HEADING_QUANTITY}</th>
                              <th style="width:5%"></th>
                            </tr>
                            </thead>
                            <tbody id="elements-assigned">
                            {if is_array($promo->settings['assigned_items']['slave']) && count($promo->settings['assigned_items']['slave'])}
                                 {foreach $promo->settings['assigned_items']['slave'] as $pKey => $slave}
                                    {if key($slave) == 'product'}
                                        {assign var = product value = $slave['product']}
                                        <tr>
                                          <td><input type="hidden" name="products_id[slave][]" value="{$product->id}">{Html::img($product->image, ['width' => '50', 'height' => 50])}</td>
                                          <td>{$product->name}</td>
                                          <td>
                                          {$info_dep}
                                          {Html::textInput('prod_depend_qty['|cat:$product->id|cat:']', $product->quantity, ['class' => 'qty-element form-control'])}</td>
                                          <td><a href="javascript:void(0)" onclick="delRow(this)" class="remove-ast"></a></td>
                                        </tr>
                                    {else if key($slave) == 'category'}
                                        {assign var = category value = $slave['category']}
                                        <tr>
                                          <td><input type="hidden" name="categories_id[slave][]" value="{$category->id}">{Html::img($category->image, ['width' => '50', 'height' => 50])}</td>
                                          <td>{$category->name}</td>
                                          <td>
                                          {$info_dep}
                                          {Html::textInput('cat_depend_qty['|cat:$category->id|cat:']', $category->quantity, ['class' => 'qty-element form-control'])}</td>
                                          <td><a href="javascript:void(0)" onclick="delRow(this)" class="remove-ast"></a></td>
                                        </tr>
                                    {else if key($slave) == 'property'}
                                        {assign var = property value = $slave['property']}
                                        <tr>
                                          <td><input type="hidden" name="properties_id[slave][]" value="{$property->id}">{Html::img($property->image, ['width' => '50', 'height' => 50])}</td>
                                          <td>{$property->name}</td>
                                          <td>
                                          {$info}
                                          {Html::textInput('pr_depend_qty['|cat:$property->id|cat:']', $property->quantity, ['class' => 'qty-element form-control'])}
                                          </td>
                                          <td><a href="javascript:void(0)" onclick="delRow(this)" class="remove-ast"></a></td>
                                        </tr>
                                    {else if key($slave) == 'prvalue'}
                                        {assign var = prvalue value = $slave['prvalue']}
                                        <tr>
                                          <td><input type="hidden" name="prvalues_id[slave][]" value="{$prvalue->id}">{Html::img($prvalue->image, ['width' => '50', 'height' => 50])}</td>
                                          <td>{$prvalue->name}</td>
                                          <td>
                                          {$info}
                                          {Html::textInput('prv_depend_qty['|cat:$prvalue->id|cat:']', $prvalue->quantity, ['class' => 'qty-element form-control'])}
                                          </td>
                                          <td><a href="javascript:void(0)" onclick="delRow(this)" class="remove-ast"></a></td>
                                        </tr>
                                    {/if}
                                {/foreach}
                            {/if}
                            </tbody>
                          </table>
                        </div>
                    </div>
                </div>
              </div>
            </div>          
        </div>        
    </div>
</div>


<script type="text/javascript">
  var mTable;
  var sTable;
  var current_tab = 'master';
  
  {if $promo->settings["properties_tree"]}
      $('#ptree').fancytree({
        extensions: ["glyph"],
        checkbox:true,
        source: {$promo->settings["properties_tree"]},
        dblclick:function(event, data) {
            if (data.node && data.node.key){
                addSelectedPropertyElement(data.node.key);
            }
        },
        select:function(event, data) {
            var pr = $('#ptree').fancytree('getTree');
            $('.properties_box').html('');
            $.each(pr.getSelectedNodes(), function (i, e){
                $('.properties_box').append("<input type='hidden' name='properties[]'  value='"+e.key+"'>")
            });
        },
        glyph: {
            map: getGlyphsMap()
        }
      });
    function getGlyphsMap(){
    return {
        doc: "icon-cubes",//"fa fa-file-o",
        docOpen: "icon-cubes", //"fa fa-file-o",
        checkbox: "icon-check-empty",// "fa fa-square-o",
        checkboxSelected: "icon-check",// "fa fa-check-square-o",
        checkboxUnknown: "icon-check-empty", //"fa fa-square",
        dragHelper: "fa fa-arrow-right",
        dropMarker: "fa fa-long-arrow-right",
        error: "fa fa-warning",
        expanderClosed: "icon-expand", //"fa fa-caret-right",
        expanderLazy: "icon-plus-sign-alt", //"icon-expand-alt", //"fa fa-angle-right",
        expanderOpen: "",//"fa fa-caret-down",icon-minus-sign-alt
        folder: "icon-folder-close-alt",//"fa fa-folder-o",
        folderOpen: "icon-folder-open-alt",//"fa fa-folder-open-o",
        loading: "icon-spinner" //"fa fa-spinner fa-pulse"
      }
  }
  {/if}
  
  beforeSave = function(){
    mTable.fnFilter('');
    sTable.fnFilter('');
    return true;
  }
    
  delRow = function(obj){
    var tr = $(obj).parents('tr');
    if (current_tab == 'master'){
        mTable.fnDeleteRow(mTable.fnGetPosition(tr[0]));
    } else {
        sTable.fnDeleteRow(sTable.fnGetPosition(tr[0]));
    }
    return;    
  }
 
  
  addItem = function(data){
    if (data.hasOwnProperty('product')){
        var product = [];
        product.push('<input type="hidden" name="products_id['+current_tab+'][]" data-type="prd_'+data.product.id+'" value="'+data.product.id+'"><img src="'+data.product.image+'" width="50" height="50">');
        product.push(data.product.name);
        if (current_tab == 'master'){
            product.push('{$info}' + '<input type="text" name="prod_master_qty['+data.product.id+']" value="'+data.product.quantity+'" class="qty-element form-control">');
        } else {
            product.push('{$info_dep}' + '<input type="text" name="prod_depend_qty['+data.product.id+']" value="'+data.product.quantity+'" class="qty-element form-control">');
        }
        product.push('<a href="javascript:void(0)" onclick="delRow(this)" class="remove-ast"></a>');
        
        if (current_tab == 'master'){
            mTable.fnAddData(product, true);
        } else {
            sTable.fnAddData(product, true);
        }
    }
    if (data.hasOwnProperty('category')){
        var category = [];
        category.push('<input type="hidden" name="categories_id['+current_tab+'][]" data-type="cat_'+data.category.id+'"  value="'+data.category.id+'"><img src="'+data.category.image+'" width="50" height="50">');
        category.push(data.category.name);
        if (current_tab == 'master'){
            category.push('{$info}' + '<input type="text" name="cat_master_qty['+data.category.id+']" value="'+data.category.quantity+'" class="qty-element form-control">');
        } else {
            category.push('{$info_dep}' + '<input type="text" name="cat_depend_qty['+data.category.id+']" value="'+data.category.quantity+'" class="qty-element form-control">');
        }
        category.push('<a href="javascript:void(0)" onclick="delRow(this)" class="remove-ast"></a>');
        
        if (current_tab == 'master'){
            mTable.fnAddData(category, true);
        } else {
            sTable.fnAddData(category, true);
        }
    }
    if (data.hasOwnProperty('property')){
        var property = [];
        property.push('<input type="hidden" name="properties_id['+current_tab+'][]" data-type="cat_'+data.property.id+'"  value="'+data.property.id+'"><img src="'+data.property.image+'" width="50" height="50">');
        property.push(data.property.name);
        if (current_tab == 'master'){
            property.push('{$info}' + '<input type="text" name="pr_master_qty['+data.property.id+']" value="'+data.property.quantity+'" class="qty-element form-control">');
        } else {
            property.push('{$info_dep}' + '<input type="text" name="pr_depend_qty['+data.property.id+']" value="'+data.property.quantity+'" class="qty-element form-control">');
        }
        property.push('<a href="javascript:void(0)" onclick="delRow(this)" class="remove-ast"></a>');
        
        if (current_tab == 'master'){
            mTable.fnAddData(property, true);
        } else {
            sTable.fnAddData(property, true);
        }
    }
    if (data.hasOwnProperty('prvalue')){
        var prvalue = [];
        prvalue.push('<input type="hidden" name="prvalues_id['+current_tab+'][]" data-type="cat_'+data.prvalue.id+'"  value="'+data.prvalue.id+'"><img src="'+data.prvalue.image+'" width="50" height="50">');
        prvalue.push(data.prvalue.name);
        if (current_tab == 'master'){
            prvalue.push('{$info}' + '<input type="text" name="prv_master_qty['+data.prvalue.id+']" value="'+data.prvalue.quantity+'" class="qty-element form-control">');
        } else {
            prvalue.push('{$info_dep}' + '<input type="text" name="prv_depend_qty['+data.prvalue.id+']" value="'+data.prvalue.quantity+'" class="qty-element form-control">');
        }
        prvalue.push('<a href="javascript:void(0)" onclick="delRow(this)" class="remove-ast"></a>');
        
        if (current_tab == 'master'){
            mTable.fnAddData(prvalue, true);
        } else {
            sTable.fnAddData(prvalue, true);
        }
    }
  }
  
  {if is_array($promo->settings['assigned_products']) && count($promo->settings['assigned_products'])}
    {foreach $promo->settings['assigned_products'] as $pKey => $assigned_product}
        current_products_id = {$assigned_product['product']->product_id};
    {/foreach}
  {/if}

  var a;
  
  function addSelectedElement(item_value) {
    a = item_value;
    if (item_value.length > 0){
        item_value = item_value.split('_')[0];
        var clear_idx = item_value.substr(1);
        
        if (clear_idx.length > 0){
            if (current_tab == 'master'){
                if ( mTable.children().find('input[type=hidden][value='+ clear_idx +']').size() == 0) {
                    observe({ 'action' : 'addItem', 'params' : { 'type': item_value.substr(0,1), 'item_id': clear_idx } , 'promo_class': '{$promo_class}' }, addItem);
                }
            } else {
                if ( sTable.children().find('input[type=hidden][value='+ clear_idx +']').size() == 0) {
                    observe({ 'action' : 'addItem', 'params' : { 'type': item_value.substr(0,1), 'item_id': clear_idx } , 'promo_class': '{$promo_class}' }, addItem);
                }
            }
        }
    }
    
    return false;
  }
  
  function addSelectedPropertyElement(item_value) {
    a = item_value;
    if (item_value.length > 0){
        var item_values = item_value.split('_');
        var clear_idx = item_values[1];
        
        if (clear_idx.length > 0){
            if (current_tab == 'master'){
                if ( mTable.children().find('input[type=hidden][value='+ clear_idx +']').size() == 0) {
                    observe({ 'action' : 'addItem', 'params' : { 'type': item_values[0], 'item_id': clear_idx } , 'promo_class': '{$promo_class}' }, addItem);
                }
            } else {
                if ( sTable.children().find('input[type=hidden][value='+ clear_idx +']').size() == 0) {
                    observe({ 'action' : 'addItem', 'params' : { 'type': item_values[0], 'item_id': clear_idx } , 'promo_class': '{$promo_class}' }, addItem);
                }
            }
        }
    }
    
    return false;
  }
  
  
  function selectItem(){
   $('.btn-select-item').attr('disabled', false);
   var node;
   if ($('#tab_products').hasClass('active')){
    node = $(fTree).fancytree('getTree').getActiveNode();
    if (node && node.key)
        addSelectedElement(node.key);
   } else if ($('#tab_properties').hasClass('active')){
    node = $('#ptree').fancytree('getTree').getActiveNode();
    if (node && node.key)
        addSelectedPropertyElement(node.key);
   }
  }
    
  $(document).ready(function() {
    
    {if $promo->settings['hide_promo_start_date']}
        $('input[name=promo_date_start]').parent().hide();
    {/if}
    
    mTable =  $('.table.master-table').dataTable({ 
        columnDefs: [ 
        { orderable: false, targets: [0,2,3] }
        ],
    });
    
    sTable =  $('.table.slave-table').dataTable({ 
        columnDefs: [ 
        { orderable: false, targets: [2] }
        ],
    });
    
    $('.master-slave .nav-tabs li > a').click(function(){
        current_tab = $(this).attr('data-id');
    })
    
    $('#element-search-products li').on('dblclick', function(){
        //addSelectedElement($(this).attr('value'));
    }).on('click', function(){
        $('.btn-select-item').attr('disabled', false);
    });
       
    
    $('.groups_on_off').bootstrapSwitch({
        onText: "{$smarty.const.SW_ON}",
        offText: "{$smarty.const.SW_OFF}",
        handleWidth: '20px',
        labelWidth: '24px',
        onSwitchChange: function (event, arguments) {
		  if (arguments){
            $(event.target).parents('.bootstrap-switch-wrapper').next().show();
          } else {
            $(event.target).parents('.bootstrap-switch-wrapper').next().hide();
          }
		  return true;
		},
    });
    $('.check_on_off_block').bootstrapSwitch({
        onText: "{$smarty.const.SW_ON}",
        offText: "{$smarty.const.SW_OFF}",
        handleWidth: '20px',
        labelWidth: '24px'
      });
    
    $( ".datepicker" ).datepicker({
        changeMonth: true,
        changeYear: true,
        showOtherMonths:true,
        autoSize: false,
        dateFormat: '{$smarty.const.DATE_FORMAT_DATEPICKER}'
    });    
    
  });
</script>