{use class="\yii\helpers\Html"}
<style>
.promo-code-content { padding: 10px 0 10px 20px; }
</style>
    <a href="javascript:void(0)" class="btn btn-primary popup btn-promo-code">{$smarty.const.TEXT_PROMO_CODE}</a>
      <div class="promo-code-holder" style="display:none;">
        <div class="popup-heading">{$smarty.const.TEXT_PROMO_CODE} {$smarty.const.TEXT_SETTINGS}</div>
        <div class="promo-code-content">
          <table>
            <tr>
             <td width="35%">
                <label style="display:inline-block;text-align: right;">{$smarty.const.ENTRY_PROMO_CODE}<div class="info-hint"><div class="info-hint-box"><div class="info-hint-mustache"></div>{$smarty.const.TEXT_PROMO_CODE_CONDITION}</div></div></label>
             </td>
             <td>
             {assign var=isUnsedCode value = $promo->isPromoCodeUnused()}
          {Html::textInput('promo_code', $promo->promo_code, ['class' => 'form-control form-control-small', 'style' => "display:inline-block;width:auto;", 'readonly' => !$isUnsedCode])}
          {if $isUnsedCode}
            <a href="" class="btn-default btn btn-generate"  style="display:inline-block;text-align: right;">{$smarty.const.TEXT_GENERATE}</a>
          {else}
            <small style="color:#ff0000;">{$smarty.const.TEXT_PROMO_CODE_IS_USED}</small>
          {/if}
            </td>
           </tr>
          <tr>
             <td>
                <label>Uses per code:</label>
            </td>
             <td>
            {Html::textInput('uses_per_code', (int)$promo->uses_per_code, ['class' => 'form-control form-control-small', 'style' => "display:inline-block;width:auto;"])}
          </td>
           </tr>
          <tr>
             <td>
                <label>Uses per customer</label>
            </td>
             <td>
            {Html::textInput('uses_per_customer', (int)$promo->uses_per_customer, ['class' => 'form-control form-control-small', 'style' => "display:inline-block;width:auto;"])}
          </td>
           </tr>
          </table>
        </div>
        <div class="note-block noti-btn">
          <div class="btn-left"><button class="btn btn-cancel">{$smarty.const.IMAGE_CANCEL}</button></div>
          <div class="btn-right"><button class="btn btn-primary btn-promo-save">{$smarty.const.IMAGE_SAVE}</button></div>
        </div>
      </div>
      
    <script>
        $(document).ready(function(){
            $('body').on('click', '.pop-up-content .btn-promo-save', function(){
                $('.pop-up-content .promo-code-holder').hide();
                $('.widget-content .promo-code-holder').replaceWith($('.pop-up-content .promo-code-holder').clone());
                $('.pop-up-close').trigger('click');
            })
            
            $('body').on('click', '.btn-generate', function(e){
                e.preventDefault();
                $.get('promotions/generate-promo-code',{}, function(data){
                    if (data.hasOwnProperty('code')){
                        $('input[name=promo_code]').val(data.code);
                    }
                }, 'json');
            });
        })
    </script>