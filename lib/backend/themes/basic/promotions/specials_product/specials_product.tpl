{use class="yii\helpers\Html"}
<div class="our-pr-line after div_sale_prod" >
    <div>
      <label class="sale-info">{$smarty.const.TEXT_SALE}:</label>
      {Html::textInput('specials_price_full', '', ['class'=>'form-control','id'=>'specials_price_full', 'placeholder' => '%'])}
    </div>
</div>
<div >
    <div class="after">
        <div class="attr-box attr-box-1">
            <div class="widget widget-attr-box box box-no-shadow" style="margin-bottom: 0;">
                    <div class="widget-header">
                      <h4>{$smarty.const.FIND_PRODUCTS}</h4>
                        <div class="box-head-serch after">
                            <input type="search" name="search" id="search_text" placeholder="{$smarty.const.SEARCH_BY_ATTR}" class="form-control">
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
        <div class="attr-box attr-box-3">
          <div class="widget-new widget-attr-box box box-no-shadow" style="margin-bottom: 0;">
            <div class="widget-header">
              <h4>{$smarty.const.TABLE_HEADING_PRODUCTS}</h4>              
            </div>
            <div class="widget-content">
              <table class="table table-striped table-selectable table-hover table-responsive table-bordered datatable">
                <thead>
                <tr role="row">
                  <th class="no-sort" >{$smarty.const.TEXT_IMG}</th>
                  <th>{$smarty.const.TEXT_LABEL_NAME}</th>
                  <th></th>
                  <th></th>
                </tr>
                </thead>
                <tbody id="elements-assigned">
                {if $promo->settings['assigned_products']|count}                
                    {foreach $promo->settings['assigned_products'] as $pKey => $assigned_product}
                      <tr >
                          <td><input type="hidden" name="products_id[]" value="{$assigned_product['product']->product_id}">{Html::img($assigned_product['product']->image)}</td>
                          <td>{$assigned_product['product']->name}</td>
                          <td><a href="javascript:void(0)" style="display: none;" class="btn btn-default popup set_{$assigned_product['product']->product_id}">Set Discounts</a><div style="display: none;" class="special_settings" data-id="{$assigned_product['product']->product_id}"></div></td>
                          <td><a href="javascript:void(0)" onclick="delRow(this)" class="remove-ast"></a></td>
                        </tr>
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
  var kTable;
  var current_products_id = 0;
  var current_row = 0;
  
  beforeSave = function(){
    kTable.fnFilter('');
    return true;
  }

  writeSpecials = function(data, vars){
    $('.special_settings[data-id='+vars.params.products_id+']').html(data);
    $('.set_' + vars.params.products_id + '.popup').show();
  }
  
  loadSpecials = function(products_id){
    observe({ 'action' : 'getSpecials', 'params' : { 'products_id': products_id } , 'promo_class': '{$promo_class}' }, writeSpecials, 'html');
  }
    
  delRow = function(obj){
    var tr = $(obj).parents('tr');
    kTable.fnDeleteRow(kTable.fnGetPosition(tr[0]));
    return;    
  }
  
  assignPopup = function(product_id){
    $('.set_' + product_id + '.popup').click(function(){
            var that = this;
            $(that).off('popUp');            
            
            var obj = $(that).next();//.clone();
            $(obj).show();
            $(that).popUp({
                event: 'show',
                only_show: true,
                data: obj,
                close: function(){
                    $('.pop-up-close').click(function(){                        
                        $(obj).hide();
                        if ($(that).next().hasClass('special_settings')){
                            $(that).next().replaceWith($(obj));
                        } else {
                            $(that).parent().append($(obj));
                        }                        
                        $('.popup-box:last').trigger('popup.close');
                        $('.popup-box-wrap:last').remove();
                        return false;
                    });
                    
                },
                
            });
            
            eval('init_'+product_id+'()');
            //if (!$(that).hasClass('opened')){
                eval('init_radio_'+product_id+'()');
            //}
            eval('init_calendar_'+product_id+'()');
            $(that).addClass('opened');
        });
  }
  
  addItem = function(data){
    if (data.hasOwnProperty('product')){
        current_products_id = data.product.product_id;
        var product = [];
        product.push('<input type="hidden" name=products_id[] value="'+data.product.product_id+'"><img src="'+data.product.image+'">');
        product.push(data.product.name);
        product.push('<a href="javascript:void(0)" style="display: none;" class="btn btn-default popup set_'+data.product.product_id+'">Set Discounts</a><div style="display: none;" class="special_settings" data-id="'+data.product.product_id+'"></div>');
        product.push('<a href="javascript:void(0)" onclick="delRow(this)" class="remove-ast"></a>');
        
        kTable.fnAddData(product, true);
        current_row++;
        loadSpecials(data.product.product_id);
        assignPopup(data.product.product_id);
        
    }
  }
  
  {if $promo->settings['assigned_products']|count}                
    {foreach $promo->settings['assigned_products'] as $pKey => $assigned_product}
        current_products_id = {$assigned_product['product']->product_id};
        loadSpecials(current_products_id);        
        assignPopup(current_products_id);
    {/foreach}
  {/if}

  
  function addSelectedElement(value) {
    if (!$('#element-search-products li[value='+value+']').hasClass('selected')) $('#element-search-products li[value='+value+']').addClass('selected');    
    var item = $( '#element-search-products li.selected' );
    var item_value = $(item).attr('value');
    if ( kTable.children().find('input[type=hidden][value='+ item_value.substr(5) +']').size() == 0) {
        if ($(item).attr('disabled') != 'disabled'){
            observe({ 'action' : 'addItem', 'params' : { 'item_id': item_value } , 'promo_class': '{$promo_class}' }, addItem);
        }
    }
    return false;
  }
  
  function selectItem(){
   $('.btn-select-item').attr('disabled', false);
    addSelectedElement($('#element-search-products li.selected').attr('value'));
  }
    
  $(document).ready(function() {
    
    {if $promo->settings['hide_promo_start_date']}
        $('input[name=promo_date_start]').parent().hide();
    {/if}
    
    kTable =  $('.table').dataTable({ 
        columnDefs: [ 
        { orderable: false, targets: [0] },
        { orderable: true, targets: [1] },
        { orderable: false, targets: [2,3] }
        ],
    });
    
    $('#element-search-products li').on('dblclick', function(){
        $('#element-search-products li').removeClass('selected');
        addSelectedElement($(this).attr('value'));
    }).on('click', function(){
        $('#element-search-products li').removeClass('selected');
        $('.btn-select-item').attr('disabled', false);
        $(this).addClass('selected');
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