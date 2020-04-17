{use class="\yii\helpers\Html"}
<style>
.row_fields { width:100%; display: inline-block; }
.buttons_hours { border-top:none; }
{if $popup == 1}
.popup-box { width:850px; }
{/if}
</style>
    {Html::beginForm(\yii\helpers\Url::to(['submit', 'page' => $page, 'gID' => $mInfo->groups_id, 'action' => 'save']), 'post', ['id' => 'save_item_form', 'enctype' => 'multipart/form-data', 'onSubmit'=> "return saveItem();" ])}
    {if $popup == 1}
        <div class="popup-content popup-content-cgr">    
    {/if}
    
    <div class="col-md-12" style="display:inline-block;">
        <div class="col-md-6">
            <div class="row_or">{$smarty.const.TEXT_EDIT_INTRO}</div>
            <div class="row_fields">
                <div class="row_fields_text">{$smarty.const.TEXT_GROUPS_NAME}</div>
                <div class="row_fields_value">{tep_draw_input_field('groups_name', $mInfo->groups_name, 'class="form-control"')}</div>
            </div>
            <div class="row_fields">
                <div class="row_fields_text">{$smarty.const.TEXT_GROUPS_DISCOUNT}&nbsp;%</div>
                <div class="row_fields_value">{tep_draw_input_field('groups_discount', $mInfo->groups_discount, 'size="5" class="form-control"')}</div>
            </div>
            
            <div class="row_fields">
                {tep_draw_checkbox_field('apply_groups_discount_to_specials', '1', $mInfo->apply_groups_discount_to_specials, '', 'class="uniform"')}<span>{$smarty.const.TEXT_GROUPS_APPLY_DISCOUNT_TO_SPECIALS}</span>
            </div>
            {if $ext = \common\helpers\Acl::checkExtension('BusinessToBusiness', 'showGroupFields')}
                {$ext::showGroupFields($mInfo)}
            {else}
                <div class="row_fields dis_module"><input disabled class="uniform" type="checkbox"><span>{$smarty.const.TEXT_GROUPS_IS_TAX_APPLICABLE}</span></div>
                <div class="row_fields dis_module"><input disabled class="uniform" type="checkbox"><span>{$smarty.const.TEXT_GROUPS_IS_SHOW_PRICE}</span></div>
                <div class="row_fields dis_module"><input disabled class="uniform" type="checkbox"><span>{$smarty.const.TEXT_GROUPS_DISABLE_CHECKOUT}</span></div>
            {/if}
            <div class="row_fields">
                {tep_draw_checkbox_field('new_approve', '1', $mInfo->new_approve, '', 'class="uniform"')}<span>{$smarty.const.TEXT_GROUPS_NEW_APPROVE}</span>
            </div>
            <div class="row_fields">
                {tep_draw_checkbox_field('groups_is_reseller', '1', $mInfo->groups_is_reseller, '', 'class="uniform"')}<span>{$smarty.const.TEXT_GROUPS_IS_RESELLER}</span>
            </div>
            
            <div class="row_fields">
                {tep_draw_checkbox_field('disable_watermark', '1', $mInfo->disable_watermark, '', 'class="uniform"')}<span>{$smarty.const.TEXT_NO_WATERMARK}</span>
            </div>
            
            {if $popup eq 0}
                <div class="row_fields">
                    <div class="row_fields_text">{$smarty.const.TEXT_ACTIVE_IMAGE}</div>
                    <div class="row_fields_value">{tep_draw_file_field('image_active')}</div>
                    {if $active neq ''}
                        <div class="row_img"><img src="{$active}" border="0" width="24" height="24"></div>
                    {/if}
                </div>
                <div class="row_fields">
                    <div class="row_fields_text">{$smarty.const.TEXT_INACTIVE_IMAGE}</div>
                    <div class="row_fields_value">{tep_draw_file_field('image_inactive')}</div>
                    {if $inactive neq ''}
                        <div class="row_img"><img src="{$inactive}" border="0" width="24" height="24"></div>
                    {/if}
                </div>
            {/if}
            
            
        </div>
    
        <div class="col-md-6">
            <div class="row_or">{Html::checkbox('groups_use_more_discount', $mInfo->groups_use_more_discount, ['class' => 'check_on_off', 'label' => ''])}&nbsp;{$smarty.const.TEXT_CUMULATIVE_DISCOUNTS}</div>
            <div class="additional-discount-holder" {if !$mInfo->groups_use_more_discount}style="display:none"{/if}>
                <div class="row_fields">
                    <div class="row_fields_text col-md-8">{$smarty.const.TEXT_SUPERSUM}</div>
                    <div class="row_fields_value col-md-2">{tep_draw_input_field('superdiscount_summ', $mInfo->superdiscount_summ, 'size=8 class="form-control"')}</div>
                </div>
                <div class="d-list">
                {if is_array($mInfo->additionalDiscounts)}
                    {foreach $mInfo->additionalDiscounts as $aDiscount}
                        <div class="row_fields">
                            <div class="row_fields_text col-md-2">{$smarty.const.TEXT_AMOUNT}</div>
                            <div class="row_fields_value col-md-3">{tep_draw_input_field('groups_discounts_amount[]',$aDiscount->groups_discounts_amount, 'size=8 class="form-control"')}</div>
                            <div class="row_fields_text col-md-2">{$smarty.const.TEXT_DISCOUNT}</div>
                            <div class="row_fields_value col-md-3">
                            {tep_draw_input_field('groups_discounts_value[]', $aDiscount->groups_discounts_value, 'size=3 class="form-control"')}
                            </div>
                            <div class="row_fields_value col-md-2">
                             {Html::checkbox('check_supersum[]', $aDiscount->check_supersum, ['class' => 'uniform'])}
                            </div>
                        </div>
                    {/foreach}
                {/if}
                </div>
                <div class="hid" style="display:none;">
                    <div class="row_fields">
                            <div class="row_fields_text col-md-2">{$smarty.const.TEXT_AMOUNT}</div>
                            <div class="row_fields_value col-md-3">{tep_draw_input_field('groups_discounts_amount[]', '', 'size=8 class="form-control"')}</div>
                            <div class="row_fields_text col-md-2">{$smarty.const.TEXT_DISCOUNT}</div>
                            <div class="row_fields_value col-md-3">
                            {tep_draw_input_field('groups_discounts_value[]', '', 'size=3 class="form-control"')}
                            </div>
                            <div class="row_fields_value col-md-2">
                            {Html::checkbox('check_supersum[]', false, [])}
                            </div>
                        </div>
                </div>
                <div class="buttons_hours">
                    <a href="javascript:void(0)" class="btn" id="more">{$smarty.const.TEXT_ADD_MORE}</a>
                </div>
            </div>
        </div>
    </div>
    {if $popup == 1}
        </div>
    {/if}

    <div class="noti-btn">
        <div><input class="btn btn-cancel" type="button" onclick="return backStatement()" value="{$smarty.const.IMAGE_CANCEL}"></div>
        <div><input class="btn btn-primary btn-no-margin" type="submit" value="{$smarty.const.IMAGE_SAVE}"></div>
    </div>
    {tep_draw_hidden_field('row_id', $row_id)}
    {tep_draw_hidden_field('item_id', $item_id)}
    {tep_draw_hidden_field('popup', $popup)}
</form>
<script>
    function backStatement() {
        {if $popup}
            $('.pop-up-close').trigger('click');
        {else}
            window.history.back();
        {/if}
        return false;
    }
    
    function switchStatement(show){
        if (show){
            $('.additional-discount-holder').show();
            
        } else {
            $('.additional-discount-holder').hide();
        }
    }
    
    function saveItem(){
    
        $.each($('[name="check_supersum[]"].uniform'), function(i, e){
            $('#save_item_form').append('<input type ="hidden" name="check_supersum_hidden[]" value="'+($(e).parent().hasClass('checked')?1:0)+'">');
        })
        {if $popup}
            $.post($('#save_item_form').attr('action'), $('#save_item_form').serialize(), function(data){
                
            });
            if ( $('.pop-up-content input[name=item_id]').val() == 0 ){
                window.location.reload();
            }
            $('.pop-up-close').trigger('click');
            return false;
        {else}            
            return true;
        {/if}
        
    }
        
    $(".uniform").uniform();
    
    $(".check_on_off").bootstrapSwitch(
        {
            onSwitchChange: function (element, arguments) {
                    switchStatement(arguments);
                    return true;  
            },
            onText: "{$smarty.const.SW_ON}",
            offText: "{$smarty.const.SW_OFF}",
            handleWidth: '20px',
            labelWidth: '24px'
        }
    );
    

    var max = 0;
    if ($('input[name=groups_use_more_discount]').prop('checked')){
          $('.more_discount').show();
    }
    $('input[name=groups_use_more_discount]').change(function(){
        if ($(this).prop('checked')){
          $('.more_discount').show();
        } else {
          $('.more_discount').hide();
        }
     })
     
      $('#more').click(function(){
        $('.d-list').append($('.hid').html());
        $('.d-list :checkbox:last').addClass('uniform');
        $(".uniform").uniform();
      })
      
        
</script>