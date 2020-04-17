<div class="gridBg">
    <div class="btn-bar btn-bar-top after">
        <div class="btn-left"><a href="javascript:void(0)" onclick="return resetStatement();" class="btn btn-back">{$smarty.const.IMAGE_BACK}</a></div>
        <div class="btn-right"><a href="javascript:void(0)" onclick="return deleteOrder({$orders_id});" class="btn btn-delete">{$smarty.const.IMAGE_DELETE}</a>
        </div>
        <a href="{$app->urlManager->createUrl(['editor/quote-edit', 'orders_id' => $orders_id])}" class="btn btn-delete btn-edit btn-right">{$smarty.const.IMAGE_EDIT}</a>
    </div>
<!--=== Page Header ===-->
<div class="page-header">
    <div class="page-title">
        <h3>{$app->controller->view->headingTitle}</h3>
    </div>
</div>
<!-- /Page Header -->

<!--=== Page Content ===-->
<link href="{{$smarty.const.DIR_WS_ADMIN}}/css/fancybox.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{$smarty.const.DIR_WS_ADMIN}/js/jquery.fancybox.pack.js"></script>

<!--===Process Order ===-->
<div class="row w-or-prev-next">
    {if $app->controller->view->order_prev > 0}
    <a href="{$app->urlManager->createUrl(['quotation/process-quotation', 'quotation_id' => $app->controller->view->order_prev])}" class="btn-next-prev-or btn-prev-or" title="{$smarty.const.TEXT_GO_PREV_ORDER} (#{$app->controller->view->order_prev})"></a>
    {else}
    <a href="javascript:void(0)" class="btn-next-prev-or btn-prev-or btn-next-prev-or-dis" title="{$smarty.const.TEXT_GO_PREV_ORDER}"></a>
    {/if}
    {if $app->controller->view->order_next > 0}
    <a href="{$app->urlManager->createUrl(['quotation/process-quotation', 'quotation_id' => $app->controller->view->order_next])}" class="btn-next-prev-or btn-next-or" title="{$smarty.const.TEXT_GO_NEXT_ORDER} (#{$app->controller->view->order_next})"></a>
    {else}
    <a href="javascript:void(0)" class="btn-next-prev-or btn-next-or btn-next-prev-or-dis" title="{$smarty.const.TEXT_GO_NEXT_ORDER}"></a>
    {/if}
    <div class="col-md-12" id="order_management_data">
        {$content}
    </div>
</div>
<!-- Process Order -->
<script type="text/javascript">
function deleteOrder(orders_id) {
    var r = confirm("{$smarty.const.TEXT_INFO_HEADING_DELETE_QUOTE}");
    if (r == true) {
        $("#order_management").hide();
        $.post("{$app->urlManager->createUrl('quotation/orderdelete')}", {
                    'orders_id': orders_id,
                }, function(data, status){
            if (status == "success") {
                $("#order_management_data").html('<div class="alert alert-success fade in"><i data-dismiss="alert" class="icon-remove close"></i>Order deleted. Please wait before continuing.</div>');
                window.location.href= "{$app->urlManager->createUrl('quotation/')}";
            } else {
                alert("Request error.");
            }
        },"html");
    }
    return false;
}
function addProduct(id){
    //$("#order_management").hide();
    $(window).scrollTop(0);
    $.get("{$app->urlManager->createUrl('quotation/addproduct')}", $('form[name=search]').serialize()+'&oID='+id, function(data, status){
        if (status == "success") {
            $("#order_management_data").html(data);
            //$('#order_management_data .scroll_col').html(data);
            //$("#order_management").show();
            //switchOffCollapse('customers_list_collapse');
        } else {
            alert("Request error.");
            //$("#customer_management").hide();
        }
    },"html");
    return false;
}                                
                              
function check_form() {
    //return false;
//ajax save
    //$("#order_management").hide();
    //var orders_id = $( "input[name='orders_id']" ).val();
    $.post("{$app->urlManager->createUrl('quotation/submit-quotation')}", $('#status_edit').serialize(), function(data, status){
        if (status == "success") {
            //$('#order_management_data .scroll_col').html(data);
            $("#order_management_data").html(data);
    /*        
            switchOnCollapse('orders_list_collapse');
            var table = $('.table').DataTable();
            table.draw(false);
            setTimeout('$(".cell_identify[value=\''+orders_id+'\']").click();', 500);
            //$(".cell_identify[value='"+orders_id+"']").click();
    */        
        } else {
            alert("Request error.");
            //$("#order_management").hide();
        }
    },"html");
    //$('#order_management_data').html('');
    return false;
}
function resetStatement() {
     window.history.back();
    return false;
}
function closePopup() {
    $('.popup-box').trigger('popup.close');
    $('.popup-box-wrap').remove();
    return false;
}
$(document).ready(function() { 
    $('a.popup').popUp();
     $('a.edit-tracking').popUp({
      box: "<div class='popup-box-wrap trackWrap'><div class='around-pop-up'></div><div class='popup-box'><div class='pop-up-close'></div><div class='popup-heading cat-head'>{$smarty.const.TEXT_EDIT_TRACKING_NUMBER}</div><div class='pop-up-content'><div class='preloader'></div></div></div></div>"
     });


    $('.fancybox').fancybox({
      nextEffect: 'fade',
      prevEffect: 'fade',
      padding: 10
    });

	$('body').on('click', '.fancybox-wrap', function(){
		$.fancybox.close();
	})

});
function printDiv() { 
 window.print();
 window.close();
}
</script>
<style>
@media print {
a[href]:after {
   content:"" !important;
}
#content, #container, #container > #content > .container{
	margin:0 !important;
}
#sidebar, header, .btn-bar, .top_header, .pra-sub-box .pra-sub-box-map:nth-child(2), .btn-next-prev-or, .btn-next-prev-or.btn-next-or, .footer{
	display:none !important;
}
.pr-add-det-box.pr-add-det-box02.pr-add-det-box03 .pra-sub-box-map{
	width:100%;
}
.pr-add-det-box.pr-add-det-box03 .pra-sub-box-map .barcode{
margin-top:-132px !important;
}
.box-or-prod-wrap{
padding:0 !important;
}
.filter-wrapp{
display:none;
}
}
</style>
        <!-- /Page Content -->
</div>