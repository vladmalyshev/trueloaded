{use class="\yii\helpers\Url"}
<div class="popup-heading">{$smarty.const.IMAGE_SEND_EMAIL}</div>
<form action="{\yii\helpers\Url::to(['recover_cart_sales/mail'])}" method="post" name="form-mail" >
<div class="popup-content pop-mess-cont">
<div class="mail-block">
{if $type eq 'c'}
  {if $coupon_list neq ''}
    <div class="coupon-block">
      <div>      
        <h4><input type="radio" value="c" name="use_method" checked id="send-c"><label for="send-c">&nbsp;{$smarty.const.TEXT_SEND_COUPON}</label></h4>
      </div>
      <div class="coupon-block-body">
      {$coupon_list}  
      </div>
    </div>
  {/if}
    <div class="voucher-block">
      <div>
        <h4><input type="radio" value="v" name="use_method" {if $coupon_list eq ''}checked{/if} id="send-v"><label for="send-v">&nbsp;{$smarty.const.TEXT_SEND_VOUCHER}</label></h4>
      </div>
      <div>
        <label>{$smarty.const.TEXT_AMOUNT}</label>
        <table>
         <tr>
          <td>
          {tep_draw_input_field('amount', '', 'class="form-control"')}
          </td>
          <td>
          {$currencies_list}
          </td>
         </tr>
         <tr>
          <td colspan="2">{$sent_vouchers}</td>
         </tr>
        </table>
      </div>
    </div>
{/if}
  </div>
  <div>  
  <label>{$smarty.const.PSMSG} {$his_language}</label><br>
     {tep_draw_textarea_field('message', 'soft', 15, 5, '','class="form-control"')}
    <br>
  </div>
  <input type="hidden" name="cid" value="{$cid}">
  <input type="hidden" name="bid" value="{$bid}">
</div>
<div class="mail-sending noti-btn">
  <div><span class="btn btn-cancel">{$smarty.const.IMAGE_CANCEL}</span></div>
  <div><span class="btn btn-primary btn-save">{$smarty.const.TEXT_BTN_OK}</span></div>
</div>
</form>
<script>
 $(document).ready(function(){
 {if $coupon_list neq ''}
	$('input[name=amount]').attr('disabled', 'disabled');
	$('select[name=coupon_currency]').attr('disabled', 'disabled');
 {/if}
 
    $('input[name=use_method]').change(function(){
      if ($(this).val() == 'v'){        
        $('.coupon-list input:radio').attr('disabled', 'disabled');
        $('input[name=amount]').attr('disabled', false);
        $('select[name=coupon_currency]').attr('disabled', false);
        $('input[name=amount]').focus();
      } else {
        $('input[name=amount]').blur();
        $('.coupon-list input:radio').attr('disabled', false);
        $('input[name=amount]').attr('disabled', 'disabled');
        $('select[name=coupon_currency]').attr('disabled', 'disabled');
      }
    });

    
    $('.btn-save').click(function(){
      var send = false;
      if ( $('input[name=use_method][value=v]').prop('checked') && $('input[name=amount]').val().length > 0){
        send = true;
      }else if ( $('input[name=use_method][value=c]').prop('checked') && $('input[name^=coupon]:radio:checked').size() > 0){
        send = true;
      }
      if ($('input[name=use_method]').size() == 0){
       send = true;
      }
      if (send){
        $.post('recover_cart_sales/mail', {
          data:$('form[name=form-mail]').serialize()
        },
        function(data, status){
          $('.mail-sending .btn-cancel').click();
          bootbox.dialog({
            message: '<div class=""><label class="control-label">'+data.message+'</label></div>',
            title: "{$smarty.const.IMAGE_SEND_EMAIL}",
              buttons: {
                cancel: {
                  label: "{$smarty.const.TEXT_BTN_OK}",
                  className: "btn-cancel",
                  callback: function() {
                       resetStatement();
                    }
                }
              }
          });            
            return;
        }, 'json');
      } else {
        alert('{$smarty.const.TEXT_CHOOSE_DETAILS}');
      }
    });
 })
</script>