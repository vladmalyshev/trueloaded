<style>
.tab-content .tab-content{ margin:0!important; }
</style>
<div class="widget box">
<div class="widget-header">Specials</div>

{use class="\yii\helpers\Html"}
    {if $useMarketPrices eq true}
        <div class="tabbable tabbable-custom tab-content tab-content-vertical">
              <ul class="nav nav-tabs">
                {foreach $currencies as $currId => $currTitle}
                  <li{if $defaultCurrency == $currId} class="active"{/if}><a href="#markettab_{$currId}_{$product->products_id}" data-toggle="tab"><span>{$currTitle}</span></a></li>
                {/foreach}
              </ul>
              {foreach $currencies as $currId => $currTitle}
                <div class="tab-pane{if $defaultCurrency == $currId} active{/if}" id="markettab_{$currId}_{$product->products_id}">
                  <ul class="nav nav-tabs nav-tabs-vertical-">
                    <li class="active"><a href="#markettab_{$currId}_0_{$product->products_id}" data-toggle="tab"><span>{$smarty.const.TEXT_MAIN}</span></a></li>
                    {if {$groups|@count} > 0}
                      {foreach $groups as $group}
                        <li><a href="#markettab_{$currId}_{$group->groups_id}_{$product->products_id}" data-toggle="tab"><span>{$group->groups_name}</span></a></li>
                      {/foreach}
                    {/if}
                  </ul>
                  <div class="tab-content tab-content-vertical-">
                    <div class="tab-pane active" id="markettab_{$currId}_0_{$product->products_id}">
                      <div class="our-pr-line after">
                        <div>
                          <label>{$smarty.const.TEXT_NET_PRICE}</label>
                          {Html::textInput('products_price['|cat:$product->products_id|cat:']['|cat:$currId|cat:']', \common\helpers\Product::get_products_price_for_edit($product->products_id, $currId), ['class'=>'form-control','id'=>'base_price_'|cat:$product->products_id])}
                        </div>
                      </div>
                      <div class="our-pr-line after div_sale_prod">
                        <div>
                          <label class="sale-info">{$smarty.const.TEXT_SALE}:</label>
                          {Html::textInput('specials_price['|cat:$product->products_id|cat:']['|cat:$currId|cat:']', \common\helpers\Product::get_specials_price($specials['specials_id']), ['class'=>'form-control','id'=>'base_sale_price_'|cat:$product->products_id|cat:'_'|cat:$currId])}
                        </div>
                      </div>
                      <div class="our-pr-line after div_sale_prod">
                        {if $defaultCurrency == $currId}
                          <div class="disable-btn">
                            <label>{$smarty.const.TEXT_EXPIRY_DATE}</label>
                            {Html::textInput('specials_expires_date['|cat:$product->products_id|cat:']', \common\helpers\Date::datepicker_date($specials['expires_date']), ['class'=>'datepicker_'|cat:$product->products_id|cat:' form-control form-control-small'])}
                          </div>
                        {/if}
                      </div>
                    </div>
                    {if {$groups|@count} > 0}
                      {foreach $groups as $group}
                        <div class="tab-pane" id="markettab_{$currId}_{$group->groups_id}_{$product->products_id}">
                          <div class="js_price_dep">
                            <div class="our-pr-line after div_sale_prod js_group_price_{$product->products_id}" data-curr="{$currId}" data-group_id="{$group->groups_id}" data-group_discount="{if $group->apply_groups_discount_to_specials}{$group->groups_discount}{else}0{/if}">
                              <label class="sale-info">{$smarty.const.TEXT_SALE}:</label>
                              <div class="our-pr-line after">
                                <label><input type="radio" name="spopt_{$currId}_{$group->groups_id}" value="-2">{$smarty.const.TEXT_PRICE_SWITCH_MAIN_PRICE}</label>
                                <label><input type="radio" name="spopt_{$currId}_{$group->groups_id}" value="1">{sprintf($smarty.const.TEXT_PRICE_SWITCH_OWN_PRICE, $group->groups_name)}</label>
                                <label><input type="radio" name="spopt_{$currId}_{$group->groups_id}" value="-1">{sprintf($smarty.const.TEXT_PRICE_SWITCH_DISABLE, $group->groups_name)}</label>
                              </div>
                              <div class="js_price_block">
                                {Html::textInput('specials_groups_prices_'|cat:$currId|cat:'_'|cat:$group->groups_id|cat:'[]', \common\helpers\Product::get_specials_price($specials['specials_id'], 0, $group->groups_id, '-2'), ['class'=>'form-control js_price_input'])}
                              </div>
                            </div>                            
                          </div>
                        </div>
                      {/foreach}
                    {/if}
                    </div>
                </div>
              {/foreach}
        </div>
    {*end usemarketPrice*}
    {else}    
     <div class="tabbable tabbable-custom widget-content">
              <ul class="nav nav-tabs">
                <li class="active"><a href="#tab_3_0_{$product->products_id}" data-toggle="tab"><span>{$smarty.const.TEXT_MAIN}</span></a></li>
                {if {$groups|@count} > 0}
                  {foreach $groups as $group}
                    <li><a href="#tab_3_{$group->groups_id}_{$product->products_id}" data-toggle="tab"><span>{$group->groups_name}</span></a></li>
                  {/foreach}
                {/if}
              </ul>
              <div class="tab-content">
                <div class="tab-pane active" id="tab_3_0_{$product->products_id}">
                  <div class="our-pr-line after">
                    <div>
                      <label>{$smarty.const.TEXT_NET_PRICE}</label>
                      {Html::textInput('products_price[]', $product->products_price, ['class'=>'form-control','id'=>'base_price_'|cat:$product->products_id])}
                    </div>                    
                  </div>
                  <div class="our-pr-line after div_sale_prod" >
                    <div>
                      <label class="sale-info">{$smarty.const.TEXT_SALE}:</label>
                      {Html::textInput('specials_price[]', \common\helpers\Product::get_specials_price($specials['specials_id']), ['class'=>'form-control','id'=>'base_sale_price_'|cat:$product->products_id])}
                    </div>
                    <div class="disable-btn">
                      <label>{$smarty.const.TEXT_EXPIRY_DATE}</label>
                      {Html::textInput('specials_expires_date[]', \common\helpers\Date::datepicker_date($specials['expires_date']), ['class'=>'datepicker_'|cat:$product->products_id|cat:' form-control form-control-small'])}
                    </div>
                  </div>
                </div>
                {if {$groups|@count} > 0}                
                  {foreach $groups as $group}
                    <div class="tab-pane" id="tab_3_{$group->groups_id}_{$product->products_id}">
                      <div class="js_price_dep">
                        <div class="our-pr-line after div_sale_prod js_group_price_{$product->products_id}" data-group_id="{$group->groups_id}" data-group_discount="{if $group->apply_groups_discount_to_specials}{$group->groups_discount}{else}0{/if}">
                          <label class="sale-info">{$smarty.const.TEXT_SALE}:</label>
                          <div class="our-pr-line after">
                            <label><input type="radio" name="spopt_{$group->groups_id}" value="-2">{$smarty.const.TEXT_PRICE_SWITCH_MAIN_PRICE}</label>
                            <label><input type="radio" name="spopt_{$group->groups_id}" value="1">{sprintf($smarty.const.TEXT_PRICE_SWITCH_OWN_PRICE, $group->groups_name)}</label>
                            <label><input type="radio" name="spopt_{$group->groups_id}" value="-1">{sprintf($smarty.const.TEXT_PRICE_SWITCH_DISABLE, $group->groups_name)}</label>
                          </div>
                          <div class="js_price_block">
                            {Html::textInput('specials_groups_prices_'|cat:$group->groups_id|cat:'[]', \common\helpers\Product::get_specials_price($specials['specials_id'], 0, $group->groups_id, '-2'), ['class'=>'form-control js_price_input'])}
                          </div>
                        </div>
                      </div>
                    </div>
                  {/foreach}
                {/if}
                <div class="notest">{$smarty.const.TEXT_SPECIALS_PRICE_TIP}</div>
            </div>
       </div>
    {/if}    
        <div class="btn-bar">
          <div class="btn-right"><button class="btn btn-primary" onclick="return applySpecials()">{$smarty.const.IMAGE_APPLY}</button></div>
        </div>
 </div>


<script>
function applySpecials(){
   var data = $('.pop-up-content .special_settings').get(0);
   if ($(data).is('div')){       
       $(data).hide();
       var id = $(data).attr('data-id');
       
       var container = kTable.find('input[type=hidden][value='+id+']').parents('tr').find('td:last').prev().find('.special_settings');
       if ($(container).is('div')){
          $(container).replaceWith($(data));
       } else {
          kTable.find('input[type=hidden][value='+id+']').parents('tr').find('td:last').prev().append($(data));
       }       
   }       
   closePopup();
   return false;
}

function init_calendar_{$product->products_id}(){
    $( ".datepicker_{$product->products_id}" ).removeClass('hasDatepicker');
    $( ".datepicker_{$product->products_id}" ).datepicker({
        changeMonth: true,
        changeYear: true,
        showOtherMonths:true,
        autoSize: false,
        dateFormat: '{$smarty.const.DATE_FORMAT_DATEPICKER}'
    });
}
    
function init_{$product->products_id}(){
    //$('.nav-tabs').scrollingTabs();
        
    $('.js_group_price_{$product->products_id}').on('change_state',function(event, state){       
          var $block = $(this);
          var $all_input = $block.find('[name^="specials_groups_prices_"]');
          var base_ref = '#base_sale_price_{$product->products_id}';
          {if $useMarketPrices}
            base_ref = '#base_sale_price_{$product->products_id}_'+$block.attr('data-curr');
          {/if}
          var $main_input = $block.find('.js_price_input');
          //
          var base_val = parseFloat($(base_ref).val()) || 0;
          if ( $(base_ref).val().indexOf('%')!==-1 ) {
            var main_price = $('#base_price_{$product->products_id}').val(),
                base_percent = parseFloat($(base_ref).val().substring(0,$(base_ref).val().indexOf('%')));
            base_val = main_price - ((base_percent/100)*main_price);
          }
          var new_val = ((100-parseFloat($block.attr('data-group_discount')))/100*base_val);
          //
          var $dep_block = $block.closest('.tab-pane').find('.js_price_dep');
          /*if (base_ref == '#base_sale_price_{$product->products_id}')*/ $dep_block = $([]);
          if ( parseFloat(state)==-1 ) {
            $all_input.removeAttr('readonly');
            $all_input.removeAttr('disabled');
            $main_input.val('-1');
            $block.find('.js_price_block').hide();
            $dep_block.hide();
          }else if(parseFloat(state)==-2){
            if ( $dep_block.is(':hidden') ) $dep_block.show();
            $all_input.removeAttr('readonly');
            $all_input.removeAttr('disabled');
            $main_input.val(new_val);
            $main_input.trigger('keyup');
            $all_input.attr({ readonly:'readonly',disabled:'disabled' });
            $block.find('.js_price_block').show();
          }else{
            if ( $dep_block.is(':hidden') ) $dep_block.show();
            $all_input.removeAttr('readonly');
            $all_input.removeAttr('disabled');
            if ( parseFloat($main_input.val())<=0 ) {
              $main_input.val(new_val);
              $main_input.trigger('keyup');
            }
            $block.find('.js_price_block').show();
          }
        });

        $('.js_group_price_{$product->products_id} [name^="popt_"]').off('click');
        
        $('.js_group_price_{$product->products_id} [name^="popt_"]').on('click',function(){
          $(this).parents('.js_group_price_{$product->products_id}').trigger('change_state',[$(this).val()]);
          if ( parseFloat($(this).val()) ==-1) {
            $('.js_group_price_{$product->products_id}').find('[name^="s'+this.name+'"]').filter('[value="-1"]').trigger('click');
          }
        });
        
        $('.js_group_price_{$product->products_id} [name^="spopt_"]').off('click');
        $('.js_group_price_{$product->products_id} [name^="spopt_"]').on('click',function(){        
          $(this).parents('.js_group_price_{$product->products_id}').trigger('change_state',[$(this).val()]);
        });

        $('#base_price_{$product->products_id}').on('change',function(){
          $('.js_group_price_{$product->products_id} [name^="popt_"]').filter('[value="-2"]').trigger('click');
        });
        
        $('#base_sale_price_{$product->products_id}').on('change',function(){
          $('.js_group_price_{$product->products_id} [name^="spopt_"]').filter('[value="-2"]').trigger('click');
        });
}

function init_radio_{$product->products_id}(){
    // init on load
    
    $('.js_group_price_{$product->products_id}').each(function(){
      
      var $block = $(this);
      var $all_input = $block.find('[name^="products_groups_prices_"]');
      var  base_ref = '#base_sale_price_{$product->products_id}';
      {if $useMarketPrices}
        base_ref = '#base_sale_price_{$product->products_id}_'+$block.attr('data-curr');
      {/if}
      //
      var base_val = parseFloat($(base_ref).val()) || 0;
      if ( $(base_ref).val().indexOf('%')!==-1 ) {
        var main_price = $('#base_price_{$product->products_id}').val(),
            base_percent = parseFloat($(base_ref).val().substring(0,$(base_ref).val().indexOf('%')));
        base_val = main_price - ((base_percent/100)*main_price);
      }
      var new_val = ((100-parseFloat($block.attr('data-group_discount')))/100*base_val);
      
      
      var $main_input = $(this).find('.js_price_input');
      var switch_name_locate = ($main_input.length>0 && $main_input[0].name.indexOf('specials_groups_prices_')===0)?'spopt_':'popt_';
      var price = parseFloat($main_input.val());
      
      if (price==-1) {
        $(this).find('[name^="'+switch_name_locate+'"]').filter('[value="-1"]').trigger('click');
      }else if (price==-2 || new_val == price) {
        $(this).find('[name^="'+switch_name_locate+'"]').filter('[value="-2"]').trigger('click');
      }else {
        $(this).find('[name^="'+switch_name_locate+'"]').filter('[value="1"]').trigger('click');
      }
    });
}

</script>        
</div>
