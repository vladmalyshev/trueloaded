{use class="yii\helpers\Html"}
<div>{$promo_description}</div>
<div class="our-pr-line after">
<div class="">
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
                      <label class="sale-info">{$smarty.const.TEXT_SALE}:</label>
                      {Html::textInput('deduction[0]', $promo->settings['conditions'][0]['promo_deduction'], ['class'=>'form-control', 'placeholder' => ''])}
                    </div>
                </div>
            </div>
            {if is_array($promo->settings['groups']) && count($promo->settings['groups'])}
                {foreach $promo->settings['groups'] as $group}
                    {assign var="visible" value=$promo->settings['conditions'][$group->groups_id]['promo_deduction'] && $promo->settings['conditions'][$group->groups_id]['promo_deduction'] > 0}
                    
                    {if $promo->settings['conditions'][$group->groups_id]['promo_deduction'] == $promo->settings['conditions'][0]['promo_deduction']}
                    {$visible = false}
                    {/if}
                    <div class="tab-pane" id="tab_1_{$group->groups_id}">
                    <input type="checkbox" class="groups_on_off" name="use_settings[{$group->groups_id}]" {if $visible}checked{/if} value="1"> set {$group->groups_name} details
                        <div {if $visible}style="display:block;"{else}style="display:none;"{/if}>
                        <div class="our-pr-line after " >
                            <div>
                              <label class="sale-info">Order Total:</label>
                              {Html::textInput('deduction['|cat:$group->groups_id|cat:']', $promo->settings['conditions'][$group->groups_id]['promo_deduction'], ['class'=>'form-control', 'placeholder' => ''])}
                            </div>
                        </div>
                      </div>  
                    </div>
                {/foreach}
            {/if}
      </div>
    </div>
</div>
<div>
    <div class="widget widget-attr-box box box-no-shadow" style="margin-bottom: 0;">
        <div class="widget-header">
          <h4>Including conditions</h4>                                    
        </div>
        <div class="widget-content">
            {include file="tree.tpl"}
        </div>
    </div>
</div>
</div>
<script type="text/javascript">
    
   function beforeSave(form){
    if (Array.isArray(selected_data)){
        $('.temporary_cats').remove();
        var input;
        $.each(selected_data, function(i, e){
			
             input = document.createElement('input');
             input.setAttribute('name', 'cat_id[]');
             input.setAttribute('type', 'hidden');
             input.className = "temporary_cats";
             input.value = e; 
             form.append(input);
        });
    }
    return true;
   }
    
  $(document).ready(function() {
  
    $('input[name=promo_date_start]').parent().show();
       
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
    
    $( ".datepicker" ).datepicker({
        changeMonth: true,
        changeYear: true,
        showOtherMonths:true,
        autoSize: false,
        dateFormat: '{$smarty.const.DATE_FORMAT_DATEPICKER}'
    });    
    
  });
</script>