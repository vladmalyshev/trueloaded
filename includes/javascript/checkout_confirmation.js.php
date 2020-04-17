<script language="javascript">
<!--
function session_win() {
  window.open("<?php echo tep_href_link(FILENAME_INFO_SHOPPING_CART); ?>","info_shopping_cart","height=460,width=430,toolbar=no,statusbar=no,scrollbars=yes").focus();
}
//-->
</script>
<?php
if ($cart->count_contents() > 0)
{
?>
<script language="javascript">
<!--
var submitter = 0;
var payment_value = '<?php echo $payment; ?>';
function send_form(theForm){
  var error = 0;
  var error_message = "<?php echo JS_ERROR; ?>";
  if(submitter == 1){
    alert( "<?php echo JS_ERROR_SUBMITTED; ?>");
    return false;
  }
  
  if (error == 1) {
    alert(error_message);
    return false;
  } else {
    if (!check_form()) return false;
    submitter = 1;
    return true;
  }
}
//-->
</script>
<?php
  $javascript_validation = $payment_modules->javascript_validation();
  echo str_replace('document.checkout_payment', 'document.checkout_confirmation', $javascript_validation);
} // end if ($cart->count_contents() > 0)
?>