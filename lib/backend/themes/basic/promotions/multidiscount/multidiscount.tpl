{use class="yii\helpers\Html"}
{*if $promo->settings['useMarketPrices']*}
    {use class="backend\components\Currencies"}
    {Currencies::widget()}
{*/if*}
<div>
    <div>{$promo_description}</div>
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
                width: 100%;
                margin: 0;
            }            
        </style>
        {assign var="info" value='<div class="info-hint"><div class="info-hint-box"><div class="info-hint-mustache"></div>Buying minimal products quantity</div></div>'}
        <div class="attr-box" style="width:63%;padding-left: 15px;">
          <div class="widget-new widget-attr-box box box-no-shadow" style="margin-bottom: 0;">
            <div class="widget-content">
                  <table class="table master-table table-striped table-selectable table-hover table-responsive table-bordered datatable">
                    <thead>
                        
                    <tr role="row">
                      <th class="no-sort" style="width:5%"></th>
                      <th class="no-sort" style="width:15%"></th>
                      <th style="width:45%">{$smarty.const.TEXT_LABEL_NAME}</th>
                      <th style="width:20%">Qty</th>
                      <th style="width:20%">Summ</th>
                      <th style="width:15%">Discount, &percnt;</th>
                      <th style="width:5%"></th>
                    </tr>
                    </thead>
                    <tbody id="elements-assigned">
                    {if $promo->settings['assigned_items']|count}    
                        {assign var=current_hash value="0"}
                        {assign var=current_hash_count value="0"}
                        {assign var=iter value="0"}
                        {foreach $promo->settings['assigned_items'] as $pKey => $item}
                            {if key($item) == 'product'}
                                {assign var = product value = $item['product']}
                                <tr>
                                  <td><input type="checkbox" name="concated[{$product->id}]" class="concat" data-hash="{$product->hash}"></td>
                                  <td ><input type="hidden" name="products_id[]" value="{$product->id}" data-type="prd_{$product->id}" >{Html::img($product->image, ['width' => '50', 'height' => 50])}</td>
                                  <td>{$product->name}</td>
                                  <td>
                                  {Html::textInput('prod_quantity['|cat:$product->id|cat:']', $product->quantity, ['class' => 'qty-element form-control', 'data-qindex' => $product->qindex, 'data-nindex' => $product->nindex])}
                                  </td>
                                  <td>
                                  {Html::textInput('prod_amount['|cat:$product->id|cat:']', $promo->settings['sets_conditions']['product'][$product->id][0]->promotions_sets_conditions_amount, ['class' => 'currency-pull qty-element form-control'])}
                                  </td>
                                  <td>
                                  {Html::textInput('prod_discount['|cat:$product->id|cat:']', $promo->settings['sets_conditions']['product'][$product->id][0]->promotions_sets_conditions_discount, ['class' => 'qty-element form-control', 'placeholder' => 0])}
                                  </td>
                                  <td><a href="javascript:void(0)" onclick="delRow(this)" class="remove-ast"></a></td>
                                </tr>
                            {else if key($item) == 'category'}
                                {assign var = category value = $item['category']}
                                <tr>
                                  <td></td>
                                  <td><input type="hidden" name="categories_id[]" value="{$category->id}" data-type="cat_{$category->id}" >{Html::img($category->image, ['width' => '50', 'height' => 50])}</td>
                                  <td>{$category->name}</td>
                                  <td>
                                  {Html::textInput('cat_quantity['|cat:$category->id|cat:']', $category->quantity, ['class' => 'qty-element form-control'])}
                                  </td>
                                  <td>
                                  {Html::textInput('cat_amount['|cat:$category->id|cat:']', $promo->settings['sets_conditions']['category'][$category->id][0]->promotions_sets_conditions_amount, ['class' => 'currency-pull qty-element form-control'])}
                                  </td>
                                  <td>
                                  {Html::textInput('cat_discount['|cat:$category->id|cat:']', $promo->settings['sets_conditions']['category'][$category->id][0]->promotions_sets_conditions_discount, ['class' => 'qty-element form-control'])}
                                  </td>
                                  <td><a href="javascript:void(0)" onclick="delRow(this)" class="remove-ast"></a></td>
                                </tr>
                            {else if key($item) == 'property'}
                                {assign var = property value = $item['property']}
                                <tr>
                                  <td></td>
                                  <td><input type="hidden" name="properties_id[]" value="{$property->id}" data-type="cat_{$property->id}" >{Html::img($property->image, ['width' => '50', 'height' => 50])}</td>
                                  <td>{$property->name}</td>
                                  <td>
                                  {Html::textInput('pr_quantity['|cat:$property->id|cat:']', $property->quantity, ['class' => 'qty-element form-control'])}
                                  </td>
                                  <td>
                                  {Html::textInput('pr_amount['|cat:$property->id|cat:']', $promo->settings['sets_conditions']['property'][$property->id][0]->promotions_sets_conditions_amount, ['class' => 'currency-pull qty-element form-control'])}
                                  </td>
                                  <td>
                                  {Html::textInput('pr_discount['|cat:$property->id|cat:']', $promo->settings['sets_conditions']['property'][$property->id][0]->promotions_sets_conditions_discount, ['class' => 'qty-element form-control'])}
                                  </td>
                                  <td><a href="javascript:void(0)" onclick="delRow(this)" class="remove-ast"></a></td>
                                </tr>
                            {else if key($item) == 'prvalue'}
                                {assign var = prvalue value = $item['prvalue']}
                                <tr>
                                  <td></td>
                                  <td><input type="hidden" name="prvalues_id[]" value="{$prvalue->id}" data-type="cat_{$prvalue->id}" >{Html::img($prvalue->image, ['width' => '50', 'height' => 50])}</td>
                                  <td>{$prvalue->name}</td>
                                  <td>
                                  {Html::textInput('prv_quantity['|cat:$prvalue->id|cat:']', $prvalue->quantity, ['class' => 'qty-element form-control'])}
                                  </td>
                                  <td>
                                  {Html::textInput('prv_amount['|cat:$prvalue->id|cat:']', $promo->settings['sets_conditions']['property_value'][$prvalue->id][0]->promotions_sets_conditions_amount, ['class' => 'currency-pull qty-element form-control'])}
                                  </td>
                                  <td>
                                  {Html::textInput('prv_discount['|cat:$prvalue->id|cat:']', $promo->settings['sets_conditions']['property_value'][$prvalue->id][0]->promotions_sets_conditions_discount, ['class' => 'qty-element form-control'])}
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


<script type="text/javascript">
  var mTable;
  
  {if $promo->settings["properties_tree"]}
      $('#ptree').fancytree({
        extensions: ["glyph"],
        checkbox:false,
        source: {$promo->settings["properties_tree"]},
        dblclick:function(event, data) {
            if (data.node && data.node.key){
                addSelectedPropertyElement(data.node.key);
            }
        },
        click:function(event, data) {
            if (data.node.children && data.node.children.length>0){
                if (!data.node.selected){
                    data.options.nodeStatus(data.node.children, true);
                } else {
                    data.options.nodeStatus(data.node.children, false);
                }
            }
        },
        nodeStatus: function(node, status){
            $.each(node, function(i, e){
                e.setSelected(status);
            })
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
        expanderOpen: "icon-minus-sign-alt",//"fa fa-caret-down",
        folder: "icon-folder-close-alt",//"fa fa-folder-o",
        folderOpen: "icon-folder-open-alt",//"fa fa-folder-open-o",
        loading: "icon-spinner" //"fa fa-spinner fa-pulse"
      }
  }
  {/if}

  {if $promo->settings['useMarketPrices'] && $promo->settings['currencies']}
   var multiCurrency = function(name, id, amount){
    var cBlock = '';
    {foreach $promo->settings['currencies'] as $id => $code}
        input = document.createElement('input');
        input.className = "form-control qty-element currency-pull";
        input.name = name + "["+id+"][" + "{$id}" + "]";
        input.value = amount;
        input.setAttribute('data-id', "{$id}");
        input.setAttribute('data-code', "{$code}");
        cBlock = cBlock + input.outerHTML;
    {/foreach}
        return cBlock;
    }
  {/if}
  
  beforeSave = function(){
    mTable.fnFilter('');
    {*if $promo->settings['useMarketPrices']*}
    unformatMaskMoney('.currency-pull');
    {*/if*}    
    return true;
  }
  
  afterSave = function(){
    
  }
    
  delRow = function(obj){
    var tr = $(obj).parents('tr');
    var rowspan = $(tr).find('td:first').prop('rowspan');
    var first = mTable.fnGetPosition(tr[0]);
    for(var i=first; i<first+rowspan;i++){
        mTable.fnDeleteRow(first);
    }    
    return;    
  }
 
  
  addItem = function(data){
    
    if (data.hasOwnProperty('product')){
        var adding = true;
        if (data.product.hasOwnProperty('already')){
            if (Array.isArray(data.product.already) && data.product.already.length > 0){
                $catS = "This product already exists in " + data.product.already.join(", ") + ". Do you realy want to add this product?";
                if (!confirm($catS)){
                    adding = false;
                }
            }
        }
        if (adding){
            var product = [];
            product.push('<input type="checkbox" name="concated['+data.product.id+']" class="concat">');
            product.push('<input type="hidden" name="products_id[]" data-type="prd_'+data.product.id+'" value="'+data.product.id+'"><img src="'+data.product.image+'" width="50" height="50">');
            product.push(data.product.name);
            product.push('<input type="text" name="prod_quantity['+data.product.id+']" value="'+data.product.quantity+'" placeholder ="0" class="qty-element form-control">');
            {if $promo->settings['useMarketPrices']}
                product.push(multiCurrency("prod_amount", data.product.id, data.product.amount));
            {else}
                product.push('<input type="text" name="prod_amount['+data.product.id+']" value="'+data.product.amount+'" class="qty-element currency-pull form-control">');
            {/if}            
            product.push('<input type="text" name="prod_discount['+data.product.id+']" value="'+data.product.discount+'" placeholder ="0" class="qty-element form-control">');
            product.push('<a href="javascript:void(0)" onclick="delRow(this)" class="remove-ast"></a>');
            
            mTable.fnAddData(product, true);
        }
    }
    if (data.hasOwnProperty('category')){
        var category = [];
        category.push('');
        category.push('<input type="hidden" name="categories_id[]" data-type="cat_'+data.category.id+'"  value="'+data.category.id+'"><img src="'+data.category.image+'" width="50" height="50">');
        category.push(data.category.name);
        category.push('<input type="text" name="cat_quantity['+data.category.id+']" value="'+data.category.quantity+'" placeholder ="0" class="qty-element form-control">');
        {if $promo->settings['useMarketPrices']}
            category.push(multiCurrency("cat_amount", data.category.id, data.category.amount));
        {else}
            category.push('<input type="text" name="cat_amount['+data.category.id+']" value="'+data.category.amount+'" class="qty-element currency-pull form-control">');
        {/if}
        
        category.push('<input type="text" name="cat_discount['+data.category.id+']" value="'+data.category.discount+'" placeholder ="0" class="qty-element form-control">');
        category.push('<a href="javascript:void(0)" onclick="delRow(this)" class="remove-ast"></a>');
        
        mTable.fnAddData(category, true);
    }
    
    if (data.hasOwnProperty('property')){
        var property = [];
        property.push('');
        property.push('<input type="hidden" name="properties_id[]" data-type="cat_'+data.property.id+'"  value="'+data.property.id+'"><img src="'+data.property.image+'" width="50" height="50">');
        property.push(data.property.name);
        property.push('<input type="text" name="pr_quantity['+data.property.id+']" value="'+data.property.quantity+'" class="qty-element form-control">');
        {if $promo->settings['useMarketPrices']}
            property.push(multiCurrency("pr_amount", data.property.id, data.property.amount));
        {else}
            property.push('<input type="text" name="pr_amount['+data.property.id+']" value="'+data.property.amount+'" class="qty-element currency-pull form-control">');
        {/if}
        
        property.push('<input type="text" name="pr_discount['+data.property.id+']" value="'+data.property.discount+'" placeholder ="0" class="qty-element form-control">');
        property.push('<a href="javascript:void(0)" onclick="delRow(this)" class="remove-ast"></a>');
        
        mTable.fnAddData(property, true);
    }
    if (data.hasOwnProperty('prvalue')){
        var prvalue = [];
        prvalue.push('');
        prvalue.push('<input type="hidden" name="prvalues_id[]" data-type="cat_'+data.prvalue.id+'"  value="'+data.prvalue.id+'"><img src="'+data.prvalue.image+'" width="50" height="50">');
        prvalue.push(data.prvalue.name);
        prvalue.push('<input type="text" name="prv_quantity['+data.prvalue.id+']" value="'+data.prvalue.quantity+'" class="qty-element form-control">');
        {if $promo->settings['useMarketPrices']}
            prvalue.push(multiCurrency("prv_amount", data.prvalue.id, data.prvalue.amount));
        {else}
            prvalue.push('<input type="text" name="prv_amount['+data.prvalue.id+']" value="'+data.prvalue.amount+'" class="qty-element currency-pull form-control">');
        {/if}
        
        prvalue.push('<input type="text" name="prv_discount['+data.prvalue.id+']" value="'+data.prvalue.discount+'" placeholder ="0" class="qty-element form-control">');
        prvalue.push('<a href="javascript:void(0)" onclick="delRow(this)" class="remove-ast"></a>');
        
        mTable.fnAddData(prvalue, true);
    }
    
    {if $promo->settings['useMarketPrices']}
        $.each($(mTable[0].lastElementChild).find('input.currency-pull'), function(i, e){
            $(e).setMaskMoney({ 'currency_id': $(e).data('id') });
        })
    {else}
        $(mTable[0].lastElementChild).find('input.currency-pull').setMaskMoney();
    {/if}    
  }
  
  function addSelectedElement(item_value) {  
    if (item_value.length > 0){
        item_value = item_value.split('_')[0];
        var clear_idx = item_value.substr(1);
        
        if (clear_idx.length > 0){
            if ( mTable.children().find('input[type=hidden][value='+ clear_idx +']').size() == 0) {
                var already_selected = [];
                var items = mTable.children().find('input[type=hidden]');
                if (items.length > 0){
                    $.each(items, function(i, e){
                        already_selected.push($(e).data('type'));
                    })
                }
                observe({ 'action' : 'addItem', 'params' : { 'type': item_value.substr(0,1), 'item_id': clear_idx , already_selected: already_selected } , 'promo_class': '{$promo_class}' }, addItem);
            }
        }
    }
    
    return false;
  }
  var a;
  function addSelectedPropertyElement(item_value) {
    a = item_value;
    if (item_value.length > 0){
        var item_values = item_value.split('_');
        var clear_idx = item_values[1];
        
        if (clear_idx.length > 0){
            if ( mTable.children().find('input[type=hidden][value='+ clear_idx +']').size() == 0) {
                observe({ 'action' : 'addItem', 'params' : { 'type': item_values[0], 'item_id': clear_idx } , 'promo_class': '{$promo_class}' }, addItem);
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
  var positions;
  var hacked = [], hacked_amount = [], hacked_discount = [];  
  var divorced = [];
     
  var groupRows = function(){
    var firstRow, pos, rowspan;
    divorce_it(true);
    hacked = [], hacked_amount = [], hacked_discount = [];
    firstRow = mTable.fnGetNodes($('.concat:checked:first').parents('tr'));
    pos = mTable.fnGetPosition(firstRow);
    
    $.each($('.concat:checked:not(:first)'), function(i, e){
        
        var row = mTable.fnGetNodes($(e).parents('tr'));
        var iter = pos+1;
        var last_iter = mTable.fnGetPosition(row);
        for(var i=(iter); i< last_iter;i++){
            if (!$(mTable.fnGetNodes(iter)).find('input[type=checkbox]').prop('checked')){
                hacked.push(mTable.fnGetData(iter));
                mTable.fnDeleteRow(iter);
            }
        }
        pos++;
    })
    if (hacked.length > 0){
        $.each(hacked, function(i, e){
           mTable.fnAddData(e);
           pos = mTable.fnGetNodes().length - 1;
           $(mTable.fnGetNodes(pos)).find('input[type=checkbox]').prop('checked', false);
           //$(mTable.fnGetNodes(pos)).find('.currency-pull').val(hacked_amount[i]);
           $(mTable.fnGetNodes(pos)).find('.currency-pull').setMaskMoney();
           //$(mTable.fnGetNodes(pos)).find('input:last').val(hacked_discount[i]);
        });        
    }
  }
  var addQindex = function (holder){
    var input = $(holder).find('input.qty-element');
    if ($(input).is('input')){
        var qindex = $(input).data('qindex');
        var nindex = $(input).data('nindex');
        var name = $(input).attr('name').replace('prod_quantity', 'prod_qindex');
        $(holder).append("<div class='qindex-holder'><input type='checkbox' name='"+name+"' value=1 "+(qindex?"checked":"")+">&nbsp;{$smarty.const.TEXT_ALL_PRODS_SAME_QTY|escape:'javascript'}</div>");
        var name = $(input).attr('name').replace('prod_quantity', 'prod_nindex');
        $(holder).append("<div class='nindex-holder'><input type='checkbox' name='"+name+"' value=1 "+(nindex?"checked":"")+">&nbsp;{$smarty.const.TEXT_NOT_ALL_PRODS_NECESSARY|escape:'javascript'}</div>");
    }
  }
  
  var removeQindex = function (holder){
    var qindex = $(holder).find('.qindex-holder');
    if ($(qindex).is('div')){
        $(qindex).remove();
    }
    var nindex = $(holder).find('.nindex-holder');
    if ($(nindex).is('div')){
        $(nindex).remove();
    }
  }
  
  var unit_rows = function(skipGroup) {
        if (!skipGroup){
            groupRows();
        }
        var row = $('.concat:checked:first').parents('tr');
        size =  $('.concat:checked').size();
        var data = mTable.fnGetNodes(row);
        var hash = new Date();
        hash = hash.getTime() * Math.round(Math.random()*10);
        data.cells[0].setAttribute('rowspan', size);
        var input = document.createElement('input');
        var value = $(data.cells[1]).find('input[type=hidden]').val();
        var old_hash = $(data.cells[1]).find('.hash').val();        
        $(data.cells[1]).find('.hash').remove();
        input.setAttribute('name', 'products_hash['+value+']');
        input.className = 'hash';
        input.setAttribute('type', 'hidden');
        input.setAttribute('value', hash);
        if (old_hash){
            $('.hash[value='+old_hash+']', mTable).val(hash);
        }
        data.cells[1].appendChild(input);
        data.cells[3].setAttribute('rowspan', size);
        addQindex(data.cells[3]);
        data.cells[4].setAttribute('rowspan', size);
        data.cells[5].setAttribute('rowspan', size);
        data.cells[6].setAttribute('rowspan', size);
        $(data.cells[0]).find('.concat').addClass('united');
        $.each($('.concat:checked:not(.united)'), function(i, e){
            row = $(e).parents('tr');            
            data = mTable.fnGetNodes(row);
            data.cells[0].remove();
            $(data.cells[0]).find('.hash').remove();
            input = document.createElement('input');
            value = $(data.cells[0]).find('input[type=hidden]').val();
            input.setAttribute('name', 'products_hash['+value+']');
            input.setAttribute('type', 'hidden');
            input.setAttribute('value', hash);
            input.className = 'hash';
            data.cells[0].appendChild(input);
            data.cells[2].remove();
            data.cells[3].remove();
            data.cells[2].remove();
            data.cells[2].remove();
        });
        $('.concat:checked').prop('checked', false);
        //$('.btn-divorce').show();
        if (divorced.length > 0){
            $.each(divorced, function(i, e){
                if (Array.isArray(e)){
                    $.each(e, function(ii, ee){
                        $('input[name*=products_id][value='+ee+']').parents('tr').find('input[type=checkbox]').prop('checked', true);
                    })
                }
                divorced.splice(i,1);
                unit_rows(true);
            })
        }
        //init_grouppen();
  }
  
  var unit_it = function(){
    
    var size = $('.concat:checked').size();
    if (size > 1){
        unit_rows(false);
        /*if ($('.concat:checked').hasClass('united')){
            size = $('.concat:checked:not(.united)').size();
            var alreadyU = $('.concat:checked.united').parents('td')[0].getAttribute('rowspan');
            unit_rows(parseInt(size) + parseInt(alreadyU));
        } else {
            unit_rows(size);
        }*/
    } 
    return false;
  }
  
  var init_grouppen = function(){
    var hash, size, iter, current_hash;
    current_hash = '';
    $.each($('.concat'), function(i, e){
        hash = $(e).data('hash');
        if (hash > 0){
            if (current_hash != hash) {
                size = $('.concat[data-hash='+hash+']').size();
                $('.concat[data-hash='+hash+']').prop('checked', true);
                if (size>1){
                    unit_rows(size);
                }
                $('.concat[data-hash='+hash+']').prop('checked', false);
                current_hash = hash;
            }
        }
    })
  }
  
  
  var divorce_rows = function(obj, set_checked){
        //mTable.fnDraw();
        var row = $(obj).parents('tr');
        var data = mTable.fnGetNodes(row);
        var currentPosition = mTable.fnGetPosition(data);
        $(data.cells[1]).find('.hash').remove();
        size = data.cells[0].getAttribute('rowspan');
        data.cells[0].setAttribute('rowspan',1);
        $(data.cells[0]).find('input[type=checkbox]').removeClass('united').prop('checked', false);
        if (set_checked){ $(data.cells[0]).find('.concat').prop('checked', true); }
        data.cells[3].setAttribute('rowspan',1);
        removeQindex(data.cells[3]);
        data.cells[4].setAttribute('rowspan',1);
        data.cells[5].setAttribute('rowspan',1);
        data.cells[6].setAttribute('rowspan',1);
        for (var i = (currentPosition+1); i<=currentPosition+(parseInt(size)-1);i++){
            row = mTable.fnGetNodes(i);
            data = mTable.fnGetNodes(row);
            $(data.cells[0]).find('.hash').remove();
            row.insertCell(0);
            var id = row.cells[1].querySelector('input[type=hidden]').value;
            row.cells[0].innerHTML = '<input type="checkbox" name="concated['+id+']" class="concat">';
            if (set_checked){ $(row.cells[0]).find('.concat').prop('checked', true); }
            row.insertCell(3);
            row.cells[3].innerHTML = '<input type="text" name="prod_quantity['+id+']" value="1" class="qty-element form-control">';
            row.insertCell(4);
            row.cells[4].innerHTML = '<input type="text" name="prod_amount['+id+']" value="0" class="qty-element currency-pull form-control">';
            row.insertCell(5);
            row.cells[5].innerHTML = '<input type="text" name="prod_discount['+id+']" value="0.00" placeholder ="0" class="qty-element form-control">';
            row.insertCell(6);
            row.cells[6].innerHTML = '<a href="javascript:void(0)" onclick="delRow(this)" class="remove-ast"></a>';
            $(row).find('input.currency-pull').setMaskMoney();
        }
  }
  
  var divorce_it = function (set_checked){
    if ($('.united:checked').size()){
        $.each($('.united:checked'), function(i, e){
            divorce_rows(e, set_checked);
        })
        $('.btn-divorce').hide();
    } else if ($('.united').size()){
        divorced = [];
        $.each($('.united'), function(i, e){
            var chain = [];
            var rowspan = $(e).parent().attr('rowspan');
            var row = mTable.fnGetNodes($(e).parents('tr'));
            for(var i=mTable.fnGetPosition(row);i<mTable.fnGetPosition(row)+parseInt(rowspan);i++){
                chain.push($(mTable.fnGetNodes(i)).find('input[type=hidden][name*=products_id]').val());
            }
            divorced.push(chain);
            divorce_rows(e);
        });
        
    }
  }
  
  var checkGroupState = function(){
    if ($('.concat.united:checked').size()){
        $('.btn-divorce').show();
    }else {
        $('.btn-divorce').hide();
    }
    if ($('.concat:checked').size() > 1){
        $('.btn-groups').show();
    } else {
        $('.btn-groups').hide();
    }    
  }
    
  $(document).ready(function() {
    
    {if $promo->settings['hide_promo_start_date']}
        $('input[name=promo_date_start]').parent().hide();
    {/if}
    
    mTable =  $('.table.master-table').dataTable({ 
        columnDefs: [ 
        { orderable: false, targets: [0,1,3,4,5] }
        ],
    });
    
    /*$('body').on('change', '.united', function(){
        if (!$(this).is(':checked')){
            divorce_it(this);
        }
    })*/
    
    $('#element-search-products li').on('dblclick', function(){
        //addSelectedElement($(this).attr('value'));
    }).on('click', function(){
        $('.btn-select-item').attr('disabled', false);
    });
    
    init_grouppen();
       
    $('.currency-pull').setMaskMoney();
    
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
    
    $('.dataTables_header div:first').html('<a class="btn btn-default btn-groups" href="javascript:void(0);" onclick="unit_it()">{$smarty.const.TEXT_GROUP}</a>&nbsp;<a class="btn btn-default btn-divorce" href="javascript:void(0);" onclick="divorce_it()">{$smarty.const.TEXT_UNGROUP}</a>');
    
    checkGroupState();
    
    $('body').on('change', '.concat', function(){
        checkGroupState();
    })
    
    
    $( ".datepicker" ).datepicker({
        changeMonth: true,
        changeYear: true,
        showOtherMonths:true,
        autoSize: false,
        dateFormat: '{$smarty.const.DATE_FORMAT_DATEPICKER}'
    });    
    
  });
</script>

