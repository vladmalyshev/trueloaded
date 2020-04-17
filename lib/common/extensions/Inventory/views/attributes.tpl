{use class="common\helpers\Html"}

{include file="@backend/themes/basic/categories/productedit/attribute-inventory-switch.tpl"}

<div class="btn-box-inv-price btn-in-pr after inventory-price-data">
  <span class="full-attr-price" data-value="1">{$smarty.const.TEXT_FULL_PRICE}</span><span class="add-attr-price" data-value="0">{$smarty.const.TEXT_ADDITIONAL_PRICE}</span>  
</div>
<input type="hidden" name="products_price_full" id="full_add_price" value="{$pInfo->products_price_full}"/>
<div class="product-attribute-setting" {if !$app->controller->view->selectedAttributes || $pInfo->is_bundle} style="display:none;"{/if}><span>{$smarty.const.TEXT_ATTRIBUTES_W_QUANTITY}</span> {\yii\helpers\Html::checkbox('show_attributes_quantity', $pInfo->settings->show_attributes_quantity, ['value' => 1, 'class' => "check_bot_switch_on_off"])}</div>
<script type="text/javascript">
  (function(){
    $(function(){
      var full_add_price = $('#full_add_price');
      var btn_in_pr = $('.btn-in-pr span');
      btn_in_pr.each(function(){
        if ($(this).data('value') == full_add_price.val()){
          $(this).addClass('active')
        }
        $(this).on('click', function(){
          if (btn_in_pr.attr('disabled')) return;
          btn_in_pr.removeClass('active');
          $(this).addClass('active');
          full_add_price.val($(this).data('value'));
          change_full_add_price($(this).data('value'));
        })
      })
    })
  })(jQuery);

  function change_full_add_price(full, el='') {
  // change title, reload inventory box
    if (el !== '') {
      if (full > 0) {
        $('.inventory-price-title', el).html('{$smarty.const.TEXT_FULL_PRICE}');
      } else {
        $('.inventory-price-title', el).html('{$smarty.const.TEXT_ADDITIONAL_PRICE}');
      }
    } else {
      if (full > 0) {
        $('.inventory-price-title').html('{$smarty.const.TEXT_FULL_PRICE}');
      } else {
        $('.inventory-price-title').html('{$smarty.const.TEXT_ADDITIONAL_PRICE}');
      }
    }
    ///2do ask to calculate full price
    updateInventoryBox();
  }
/*deprecated - no marketing
  function update_inventory_prices(full, el = '') {
  // updates Gross/Net prices in inventory listing
  // el must be .popup-box-wrap-page
  //2do - move initial (empty el) to server part
    if (el !== '') {
      proc = el;
    } else {
      proc = $('.popup-box-wrap-page');
    }
    proc.each(function() {
      var id = $(this).attr('id').replace(/id-/, '');
      if (full > 0) {
        var price_net = $(this).find('input[name^="inventoryfullprice_"][name$="[0]"]').val();
        var price_gross = $(this).find('input[name^="inventorygrossfullprice_"][name$="[0]"]').val();
      } else {
        var price_net = $(this).find('input[name^="inventoryprice_"][name$="[0]"]').val();
        var price_gross = $(this).find('input[name^="inventorygrossprice_"][name$="[0]"]').val();
      }
      $('.inventory-price-net-' + id).html(currencyFormat(price_net));
      $('.inventory-price-gross-' + id).html(currencyFormat(price_gross));
    });
  }*/
</script>


<div class="attr-box-wrap after">
  <div class="attr-box attr-box-1">
    <div class="widget widget-attr-box box box-no-shadow" style="margin-bottom: 0;">
      <div class="widget-header">
        <h4>{$smarty.const.TAB_ATTRIBUTES}</h4>
        <div class="box-head-serch after">
          <input type="search" value="" id="search-by-attributes" placeholder="{$smarty.const.TAB_SEARCH_ATTR}" class="form-control" />
          <button onclick="return false"></button>
        </div>
      </div>
      <div class="widget-content">
        <select class="attr-tree" size="25" name="attributes" ondblclick="addSelectedAttribute()" style="width: 100%; height: 100%; border: none;" multiple="multiple">
            {if $app->controller->view->attributeTemplates['options']}
            <optgroup label="{$app->controller->view->attributeTemplates['label']}" id="tpl" {if $optgroup['disable']} disabled="disabled"{/if}>
                {foreach $app->controller->view->attributeTemplates['options'] as $option}
                  <option value="{$option['value']}">{$option['name']}</option>
                {/foreach}
            </optgroup>
            {/if}
            {foreach $app->controller->view->attributes as $optgroup}
            <optgroup label="{$optgroup['label']}" id="{$optgroup['id']}"{if $optgroup['disable']} disabled="disabled"{/if}>
              {foreach $optgroup['options'] as $option}
                <option value="{$option['value']}">{$option['name']}</option>
              {/foreach}
            </optgroup>
          {/foreach}
        </select>
      </div>
    </div>
  </div>
  <div class="attr-box attr-box-2">
    <span class="btn btn-primary" onclick="addSelectedAttribute()"></span>
  </div>
  <div class="attr-box attr-box-3">
    <div class="widget-new widget-attr-box box box-no-shadow" style="margin-bottom: 0;">
      <div class="widget-header">
        <h4>{$smarty.const.TEXT_ASSIGNED_ATTR}<small class="found-result"></small></h4>
        <div class="box-head-serch after">
          <input type="search" placeholder="{$smarty.const.TEXT_SEARCH_ASSIGNED_ATTR}" class="form-control"  id="attribute_search"/>
          <button onclick="return false"></button>
        </div>
      </div>
      <div class="widget-content selected_attributes_box" id="selected_attributes_box">
{$attributes=$app->controller->view->selectedAttributes}
{$products_id={$pInfo->products_id} }
{if $pInfo->without_inventory}
    {include file='@backend/themes/basic/categories/product-new-option.tpl'}
{else}
    {include file='./product-new-option.tpl'}
{/if}
      </div>
    </div>
  </div>
</div>
{if $app->controller->view->showInventory }
  <div class="widget box box-no-shadow inventory-box" style="margin-bottom: 0;">
    <div class="widget-header"><h4>{$smarty.const.BOX_CATALOG_INVENTORY}</h4></div>
    <div class="widget-content widget-inv" id="product-inventory-box">
    </div>
  </div>
{/if}

<script type="text/javascript">
  var ajax_attribute_add_in_progress = 0;
  function addSelectedAttribute(option_values_array) {
    var new_products_options_ids = '', assigned_products_options_ids = '', products_options_values_ids = ''; // pass comma separated ids
    var template_apply = [];

    var collectAttrIds = function(products_options_id, products_options_values_id){
        if ( products_options_id=='tpl' ) {
            template_apply[0] = products_options_values_id;
            return;
        }
        if ($('#products_attributes_id_' + products_options_id + '_' + products_options_values_id).length) {
            // skip assigned
        } else {
            products_options_values_ids += products_options_values_id + ',';
            if ($("#attr-option-" + products_options_id).length) {
                assigned_products_options_ids += products_options_id + ',';
            } else {
                new_products_options_ids += products_options_id + ',';
            }
        }
    };
    if ( option_values_array && Object.prototype.toString.call(option_values_array) === '[object Array]' ){
        for( var _i=0; _i<option_values_array.length; _i++ ){
            collectAttrIds(option_values_array[_i][0], option_values_array[_i][1]);
        }
    }else{
        $('select[name="attributes"] option:selected').each(function () {
            collectAttrIds($(this).parent().attr('id'), $(this).val());
        });
    }

    if ( template_apply.length>0 ) {
        ajax_attribute_add_in_progress += 1;
        bootbox.confirm('{$smarty.const.TEXT_CONFIRM_APPLY_OPTION_TEMPLATE|escape:'javascript'} ',function(result) {
            if (result) {
                $.post(
                    "{Yii::$app->urlManager->createUrl('categories/selected-attributes')}",
                    {
                        'products_id': '{$pInfo->products_id}',
                        'options_templates_id': template_apply[0],
                        'products_tax_class_id': $('input[name="products_tax_class_id"]').val(),
                        'without_inventory': $('#productInventorySwitch').val()
                    },
                    function (data, status, xhr, dataType) {
                        if (status == "success") {
                            $('#selected_attributes_box').html(data);
                            ajax_attribute_add_in_progress -= 1;
                            updateInventoryBox();
                        } else {
                            alert("Request error.");
                        }
                    });
            }
        });
        assigned_products_options_ids = '';
        new_products_options_ids = '';
    }

    if (assigned_products_options_ids != '') {
      ajax_attribute_add_in_progress += 1;
      $.post(
        "{Yii::$app->urlManager->createUrl('categories/product-new-attribute')}",
        { 'products_id': '{$pInfo->products_id}', 'products_options_id' : assigned_products_options_ids, 'products_options_values_id' : products_options_values_ids, 'without_inventory': $('#productInventorySwitch').val() },
        function(dataJ, status, xhr, dataType) {
          if (status == "success") {
            for (i=0;i<dataJ.length; i++) {
              products_options = dataJ[i]['products_options_id'];
              products_options_values = dataJ[i]['products_options_values_id'];
              data = dataJ[i]['data'];

              var $target_tbody = $(".attr-option-"+products_options+" tbody");
              var insert_order_locate = ',';
              $target_tbody.find('input[name^="products_attributes_id\['+products_options+'\]"]').each(function(){
                var val_id_match = this.name.match(/products_attributes_id\[\d+\]\[(\d+)\]/);
                if ( val_id_match ) {
                  insert_order_locate = insert_order_locate + val_id_match[1] + ',';
                }
              });
              var after_val_id = '', before_val_id = '', id_pass = false;
              $('select[name="attributes"] optgroup#' + products_options + ' option').each(function(){
                if ( before_val_id ) return;
                if ( this.value==products_options_values ) {
                  id_pass = true;
                }else
                if ( insert_order_locate.indexOf(','+this.value+',')!=-1 ){
                  if ( id_pass ) {
                    before_val_id = this.value;
                  }else{
                    after_val_id = this.value;
                  }
                }
              });
              if ( after_val_id ) {
                $target_tbody.find('input[name^="products_attributes_id\['+products_options+'\]\['+after_val_id+'\]"]').parents('tr[role="row"]').after(data);
              }else if( before_val_id ) {
                $target_tbody.find('input[name^="products_attributes_id\['+products_options+'\]\['+before_val_id+'\]"]').parents('tr[role="row"]').before(data);
              }else {
                $target_tbody.append(data);
              }
              if ( $(".attr-option-"+products_options+" tbody").find('.js-option-value').length>1 ) {
                $(".attr-option-"+products_options+" tbody").sortable('enable');
              }
            }
            ajax_attribute_add_in_progress -= 1;
            updateInventoryBox();
          } else {
            alert("Request error.");
          }
        },"json");
    }

    if (new_products_options_ids != '') {
      ajax_attribute_add_in_progress += 1;
      $.post(
        "{Yii::$app->urlManager->createUrl('categories/product-new-option')}",
        { 'products_id': '{$pInfo->products_id}', 'products_options_id' : new_products_options_ids, 'products_options_values_id' : products_options_values_ids, 'without_inventory': $('#productInventorySwitch').val() },
        function(dataJ, status, xhr, dataType) {
          if (status == "success") {
            for (i=0;i<dataJ.length; i++) {
              products_options = dataJ[i]['products_options_id'];
              data = dataJ[i]['data'];
              // insert new option according its location in the select
              var insert_order_locate = ',';
              var $added_options = $("#selected_attributes_box .js-option");
              $added_options.each(function(){
                insert_order_locate = insert_order_locate + $(this).attr('data-option_id')+',';
              });
              var after_opt_id = '', before_opt_id = '', id_pass = false;
              $('select[name="attributes"] optgroup').each(function() {
                if ( before_opt_id ) return;
                if ( this.id==products_options ) {
                  id_pass = true;
                }else
                if ( insert_order_locate.indexOf(','+this.id+',')!=-1 ){
                  if ( id_pass ) {
                    before_opt_id = this.id;
                  }else{
                    after_opt_id = this.id;
                  }
                }
              });
              if ( after_opt_id ) {
                $added_options.filter('[data-option_id="'+after_opt_id+'"]').after(data);
              }else if( before_opt_id ) {
                $added_options.filter('[data-option_id="'+before_opt_id+'"]').before(data);
              }else {
                $("#selected_attributes_box").append(data);
              }
              if ( $(".attr-option-"+products_options+" tbody").find('.js-option-value').length>1 ) {
                $(".attr-option-"+products_options+" tbody").sortable('enable');
              }else{
                $(".attr-option-"+products_options+" tbody").sortable('disable');
              }
            }
            ajax_attribute_add_in_progress -= 1;
            updateInventoryBox();
          } else {
            alert("Request error.");
          }
      },"json");
    }
    $(".product-attribute-setting").show();

    return false;
  }

  function deleteSelectedAttribute(obj) {
    var optionBox = $(obj).parent().parent();
    var option_value_id = $(obj).parents('.js-option-value').attr('data-option_value_id');
    var option_id = $(obj).parents('.js-option').attr('data-option_id');
    if ($('.attr-option-'+option_id).hasClass('readonly-option')) return false;
    $(obj).parent().remove();
    var $sort_input = $('input[name="products_option_values_sort_order['+option_id+']"]');
    if ($sort_input.length>0) $sort_input.val($sort_input.val().replace(','+option_value_id+',',','));
    var findtr = $(optionBox).find('tr');
    if (findtr[0] == undefined) {
      $(optionBox).parent().parent().parent().remove();
    }
    if ( $(".attr-option-"+option_id +" tbody").find('.js-option-value').length==1 ) {
      $(".attr-option-"+option_id +" tbody").sortable('disable');
    }

    updateInventoryBox();
    return false;
  }

  function updateInventoryBox() {
    if (ajax_attribute_add_in_progress != 0 ) return; // not all ajax requests are finished
{if \common\helpers\Acl::checkExtension('Inventory', 'allowed') && (PRODUCTS_INVENTORY == 'True')}
    if ( $('#productInventorySwitch').val()!='1' ) {
        //VL2check $('#save_product_form').trigger('attributes_changed');
        $.post("{Yii::$app->urlManager->createUrl('categories/product-inventory-box')}", $('#save_product_form').serialize(), function (data, status) {
            if (status == "success") {
                $("#product-inventory-box").html(data);
                $('#save_product_form').trigger('inventory_arrived');

                if ($("table[class^='attr-option-'],table[class*=' attr-option-']").not('.is-virtual').length <= 1) {
                    //vl2check - images
                    $('.one-attribute').show();
                    //reset prices in the attributes list.
                    // came in inventory-price-net-0-33-170 id= inv_list_price-0-33-170
                    //shown as inventory-price-net-0-33-170 id=attr_list_price-0-33-170
                    // inventory-price-gross-0-33-170  inv_list_price_gross-0-33-170
                    // inventory-price-gross-0-33-170 attr_list_price_gross-0-33-170
                    $("td.one-attribute span[class^='inventory-price-']").each(function () {
                        var t = $('#' + $(this).attr('id').replace('attr_', 'inv_')).text();
                        $(this).text(t);
                    });
                    $('.more-attributes').hide();
                    $('.inventory-box').css({
                        'height': 0, 'overflow': 'hidden'
                    });
                } else {
                    $('.one-attribute').hide();
                    $('.more-attributes').show();
                    $('.inventory-box').css({
                        'height': '', 'overflow': ''
                    });
                }

            } else {
                alert("Request error.");
            }
        }, "html");
    }else{
        $('ul[id^="invPrice"] a[data-toggle="tab"]').off('shown.bs.tab').on('shown.bs.tab', invPriceTabsShown);
        $('ul[id^="attr_popup"] a[data-toggle="tab"]').off('shown.bs.tab').on('shown.bs.tab', invPriceTabsShown);
        $('.js_inventory_group_price input.price-options').off('click').on('click', priceOptionsClick);
    }
{else}
        //or not
        if (ajax_attribute_add_in_progress == 0 ){
          $('ul[id^="invPrice"] a[data-toggle="tab"]').off('shown.bs.tab').on('shown.bs.tab', invPriceTabsShown);
          $('ul[id^="attr_popup"] a[data-toggle="tab"]').off('shown.bs.tab').on('shown.bs.tab', invPriceTabsShown);
          $('.js_inventory_group_price input.price-options').off('click').on('click', priceOptionsClick);
        }
{/if}
  }

  var color = '#ff0000';
  var athighlight = function(obj, reg){
    if (reg.length == 0) return;
    $(obj).html($(obj).text().replace( new RegExp( "(" +  reg  + ")" , 'gi' ), '<font style="color:'+color+'">$1</font>'));
    return;
  }
  var atunhighlight = function(obj){
    $(obj).html($(obj).text());
  }
  var atsearch = null;
  var atstarted = false;
  $(document).ready(function() {
    $('#search-by-attributes').on('focus keyup', function(e){
      $('select[name="attributes"]').find('option').parent().hide();
      if ($(this).val().length == 0){
        atstarted = false;
      }
      if (!atstarted && e.type == 'focus'){
        $('select[name="attributes"]').find('option').show();
        $('select[name="attributes"]').find('option').parent().show();
      }
      atstarted = true;
      var str = $(this).val();
      atsearch = new RegExp(str, 'i');
      $.each($('select[name="attributes"]').find('option'), function(i, e){
        atunhighlight(e);
        if (!atsearch.test($(e).text())){
          $(e).hide();
        } else {
          $(e).show();
          $(e).parent().show();
          athighlight(e, str);
        }
      });
    });

    {if $app->controller->view->showInventory }
    updateInventoryBox();
    {/if}
  });
</script>