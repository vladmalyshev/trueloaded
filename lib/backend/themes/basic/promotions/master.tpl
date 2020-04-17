{use class="\yii\helpers\Html"}
{\backend\assets\BDTPAsset::register($this)|void}
 <div class="show_promo_settings" {if !$promo->promo_id } style="display:none;"{/if}>
  <div class=" after dfullcheck">
    <div class="att-box" style="width:175px;display:inline-block;text-align:left;">
      <label>{$smarty.const.ENTRY_STATUS}</label>
      {Html::checkbox('promo_status', $promo->promo_status, ['class' => 'check_on_off'])}
    </div>
    <div class="att-box" style="display:inline-block;text-align:left;">
      {include file='./promo-code.tpl'}
    </div>
    {if $promo->promo_id}
    <a href="{\Yii::$app->urlManager->createUrl(['promotions/personalize', 'platform_id' => $promo->platform_id, 'promo_id' => $promo->promo_id])}" class="btn btn-no-margin btn-personalize">{$smarty.const.TEXT_PERSONALIZE}</a>
    {/if}
    <div class="att-box" style="display:inline-block;text-align:right;float:right;">
       <div class="current_icon" style="padding-left: 20px;display:inline-block;">
        {if $promo->promo_icon}
            <img src="{$path}{$promo->promo_icon}" style="max-width:{$max_width}px;max-height:{$max_height}px;"><input type="hidden" name="promo_icon" value="{$promo->promo_icon}">
        {/if}
       </div>
      <a href="{\yii\helpers\Url::to(['promotions/icons'])}" class="btn btn-default btn-icons popup" style="display:inline-block;">{$smarty.const.TEXT_SELECT_ICON}</a>
    </div>
  </div>
   <div class="our-pr-line after our-pr-line-check-box dfullcheck">
    <div>
      <label>{$smarty.const.TEXT_LABEL}:</label>
      {Html::textInput('promo_label', $promo->promo_label, ['class' => 'form-control'])}
    </div>
  </div> 
    <div class="our-pr-line after our-pr-line-check-box" >
        <div>
          <label>{$smarty.const.TEXT_START_DATE} (Server time):</label>
          {Html::textInput('promo_date_start', \common\helpers\Date::formatDateTimeJS($promo->promo_date_start), ['class'=>'pttime form-control form-control-small'])}
        </div>
        <div>
          <label>{$smarty.const.TEXT_END_DATE} (Server time):</label>
          {Html::textInput('promo_date_expired', \common\helpers\Date::formatDateTimeJS($promo->promo_date_expired), ['class'=>'pttime form-control form-control-small'])}
        </div>    
    </div>
    <div class="settings_details">
    </div>
</div>
<script>

    var $clone;
    $(document).ready(function(){
      $('.check_on_off').bootstrapSwitch({
        onText: "{$smarty.const.SW_ON}",
        offText: "{$smarty.const.SW_OFF}",
        handleWidth: '20px',
        labelWidth: '24px'
      });
        
      {if $promo->promo_id}
        loadSettings('{$promo->promo_class}');
      {/if}
            
      $('.btn-icons').popUp();
      $('.btn-personalize').popUp();
     
      $('.pttime').datetimepicker({
        format: 'DD MMM YYYY h:mm A'
      });
      
      $('.btn-promo-code.popup').click(function(){
        $clone = $('.promo-code-holder').clone();
          $(this).popUp({
            event:'show',
            only_show: true,
            data: $clone,
            position: function(popup_box){
              var d = ($(window).height() - $('.popup-box').height()) / 3;
              if (d < 0) d = 30;
              $('.popup-box-wrap').css('top', $(window).scrollTop() + d);
            }
          });
          $($clone).show();
      })
      
    });
</script>

