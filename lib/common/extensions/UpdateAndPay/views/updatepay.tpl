<div class="popup-heading up-head">Update and pay</div>
<div class="update_pay_box">
	<ul class="update_pay_ul">
		<li>
			<div class="up_title">{$smarty.const.TEXT_ORIGINAL_AMOUNT}</div>
			<div class="up_value">{$old_ot_total}</div>
		</li>
		<li>
			<div class="up_title">{$smarty.const.TABLE_HEADING_TOTAL}</div>
			<div class="up_value">{$new_ot_total}</div>
		</li>
		<li>
			<div class="up_title">{$difference_desc}</div>
			<div class="up_value {if $difference == true}plus_ballance{else}minus_ballance{/if}">{$difference_ot_total}</div>
		</li>
	</ul>
        {foreach $actions as $k => $action}
	<div class="up_radio">
		<input type="radio" name="pay_choose" value="{$action.value}" {$action.disabled} {$action.checked} id="p{$k}">
		<label for="p{$k}">{$action.name}</label>
	</div>
        {/foreach}
</div>
<div class="preloader" style="display:none;"></div>
<input type="hidden" name="pay_difference" value="{$pay_difference}">
    {$manager->render('OrderStatusesList', ['manager' => $manager, 'hide' => true])}
	<div class="noti-btn">
		<div class="btn-left"><button class="btn btn-cancel" onclick="return closePopup()">{$smarty.const.IMAGE_CANCEL}</button></div>
		<div class="btn-right"><button class="btn btn-confirm" onclick="return updatePayOrder()">{$smarty.const.IMAGE_SAVE}</button></div>
	</div>

<script type="text/javascript">
{if $checkRefund}
    //order.checkRefund('{$checkRefund}');
{/if}
function updatePayOrder() {
    var extra = [];
    extra.push({ 'name':'status','value':$('select[name=status]').val() });
    extra.push({ 'name':'comments','value':$('textarea.status-comment').val() });
    $('.preloader').show();
    order.saveOrder($('#checkoutForm'), extra, $('input:checked[name="pay_choose"]').val(), $('input[name="pay_difference"]').val(), function(data){
      $('.preloader').hide();
      if($('input:checked[name="pay_choose"]').val()=='on_behalf') {
        $('.pop-up-close:last').hide();
        $('.order-status-table-box').hide();

        var paymentPopup = $('.update_pay_box');
        paymentPopup.html(data.message);
        var w = Math.max(300, Math.round(screen.width/2));
        var h = Math.max(300, Math.round(screen.height*0.65));

        paymentPopup.css("width", w +'px').css("height", h +'px');
        var d = ($(window).height() - $('.popup-box').height()) / 2;
        if (d < 0) d = 0;
        $('.popup-box-wrap').css('top', $(window).scrollTop() + d);
        //paymentPopup.position($('.popup-box:last'));
        $(".pop-up-content:last").html('<iframe src="{$onBehalfUrl}&order_id=' + data.order_id + '" frameborder="0" style="width:' + (w-15) +'px;height:' + (h-15) +'px"></iframe><div class="noti-btn"><div class="btn-right"><button class="btn btn-confirm" id="paymentCloseBtn">{$smarty.const.IMAGE_CLOSE}</button></div></div>');
          $("#paymentCloseBtn").on('click', function() {
            $.ajax({
                url: "{tep_catalog_href_link('account/logoff')}",
                complete: function(data, status, xhr) {
                  order.removeCart(function(data){
                      window.location.href=data.redirect;
                  });
                  //  window.location.reload();
                }
            });

          });


        /// open frontend pay page in new window.
        //var pay_win = window.open('{$onBehalfUrl}&order_id=' + data.order_id);
        //pay_win.focus();
        //window.location.reload();
      } else {
        if (data.hasOwnProperty('prompt')){
            $('.pop-up-close:last').hide();
            $('.order-status-table-box').hide();
            $('.update_pay_box').html(data.message);
            $('.update_pay_box').append('<br>Do you want to continue working with this order?');
            history.replaceState({}, '', data.redirect);
            $('.pop-up-content:last .btn-cancel').html('No');
            $('.pop-up-content:last .btn-cancel').attr('onclick', '');
            $('.pop-up-content:last .btn-cancel').click(function(){
                if (data.hasOwnProperty('urlCheckout')){
                    $urlCheckout = data.urlCheckout;
                }
                order.removeCart(function(data){ 
                    window.location.href=data.redirect;
                });
            })
            $('.pop-up-content:last .btn-confirm').html('Yes');
            $('.pop-up-content:last .btn-confirm').attr('onclick', '');
            $('.pop-up-content:last .btn-confirm').click(function(){
                window.location.reload();
            })
        }
      }
    });
    return false;
}
</script>
