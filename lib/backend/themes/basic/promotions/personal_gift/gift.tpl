{use class="yii\helpers\Html"}
{use class="backend\components\Currencies"}
{Currencies::widget()}
<div>{$promo_description}</div>
<div class="after">
    <div class="attr-box " style="width:30%;">
        <div class="widget widget-attr-box box box-no-shadow" style="margin-bottom: 0;">
            <div class="widget-header">
              <h4>{$smarty.const.FIND_PRODUCTS}</h4>
                <div class="box-head-serch after">
                    <input type="search" name="search" id="search_text" placeholder="{$smarty.const.SEARCH_BY_ATTR}" class="form-control" autocomplete=off>
                </div>
            </div>
            <div class="widget-content">
                {include file="tree.tpl"}
            </div>
        </div>
    </div>
    <div class="attr-box attr-box-2">
      <span class="btn btn-primary btn-select-item" onclick="selectItem()"></span>
    </div>
    <div class="attr-box" style="width:63%;padding-left: 15px;">
      <div class="widget-new widget-attr-box box box-no-shadow" style="margin-bottom: 0;">
        <div class="widget-content">
            <div>{Html::checkbox('promo_type', $promo->settings['promo_skip_date_range'], ['id' => 'skip_date_range'])}<label for="skip_date_range"> Skip order history (will work only for current cart)</label></div>
            <div>{Html::checkbox('auto_push', $promo->settings['auto_push'], ['id' => 'promo_exc_vat'])}<label for="promo_exc_vat"> Use <b>Exclusive</b> VAT mode</label></div>
              <table class="table master-table table-striped table-selectable table-hover table-responsive table-bordered datatable">
                <thead>
                    <tr role="row">
                        <th class="no-sort" style="width:10%"></th>
                        <th style="width:35%">{$smarty.const.TEXT_LABEL_NAME}</th>
                        <th style="width:20%">Qty</th>
                        <th style="width:40%">Purchased amount</th>
                        <th style="width:5%">&nbsp;</th>
                    </tr>
                </thead>
                <tbody id="elements-assigned">
                {if $promo->settings['assigned_items']|count}
                    {assign var=iter value="0"}
                    {foreach $promo->settings['assigned_items'] as $pKey => $item}
                        {if key($item) == 'product'}
                            {assign var = product value = $item['product']}
                            <tr>
                              <td ><input type="hidden" name="products_id[]" value="{$product->id}" data-type="prd_{$product->id}" >{Html::img($product->image, ['width' => '50', 'height' => 50])}</td>
                              <td>{$product->name}</td>
                              <td>
                              {Html::textInput('prod_quantity['|cat:$product->id|cat:'][]', intval($product->sets_conditions->promotions_sets_conditions_discount), ['class' => 'qty-element form-control'])}
                              </td>
                              <td>
                              {Html::textInput('prod_amount['|cat:$product->id|cat:'][]', $product->sets_conditions->promotions_sets_conditions_amount, ['class' => 'currency-pull qty-element form-control'])}
                              {if $product->assets}
                                {$product->assets}
                              {/if}
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
<script type="text/javascript">
    var mTable;
    
    
   function beforeSave(form){
    mTable.fnFilter('');
    unformatMaskMoney('.currency-pull');
    return true;
   }
   
    afterSave = function(){
        $('.currency-pull').setMaskMoney();
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
    
    function selectItem(){
        $('.btn-select-item').attr('disabled', false);
        var node = $(fTree).fancytree('getTree').getActiveNode();
        if (node && node.key)
            addSelectedElement(node.key);
    }
    
    function addSelectedElement(item_value) {  
        if (item_value.length > 0){
            item_value = item_value.split('_')[0];
            var clear_idx = item_value.substr(1);
            
            if (clear_idx.length > 0){
                if ( mTable.children().find('input[type=hidden][value='+ clear_idx +']').size() == 0 || true) {
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
    
    addItem = function(data){
    
        if (data.hasOwnProperty('product')){
            var product = [];            
            product.push('<input type="hidden" name="products_id[]" data-type="prd_'+data.product.id+'" value="'+data.product.id+'"><img src="'+data.product.image+'" width="50" height="50">');
            product.push(data.product.name);
            product.push('<input type="text" name="prod_quantity['+data.product.id+'][]" value="'+data.product.quantity+'" placeholder ="0" class="qty-element form-control">');
            var text = '<input type="text" name="prod_amount['+data.product.id+'][]" value="'+data.product.amount+'" class="qty-element currency-pull form-control">';
            if (data.product.assets){
                text = text + data.product.assets;
            }
            product.push(text);
            product.push('<a href="javascript:void(0)" onclick="delRow(this)" class="remove-ast"></a>');
            
            mTable.fnAddData(product, true);
        }    
        $(mTable[0].lastElementChild).find('input.currency-pull').setMaskMoney();
    }
    
  $(document).ready(function() {
  
    $('input[name=promo_date_start]').parent().show();
    
    mTable =  $('.table.master-table').dataTable({ 
        columnDefs: [ 
        { orderable: false, targets: [0,1,3,4] }
        ],
    });
    
    $('.currency-pull').setMaskMoney();

    $( ".datepicker" ).datepicker({
        changeMonth: true,
        changeYear: true,
        showOtherMonths:true,
        autoSize: false,
        dateFormat: '{$smarty.const.DATE_FORMAT_DATEPICKER}'
    });    
    
  });
</script>