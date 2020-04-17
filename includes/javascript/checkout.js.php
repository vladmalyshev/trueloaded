<?php
// ??? $selection = $payment_modules->selection();
?>
<script language="javascript" type="text/javascript">
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
<script language="javascript" type="text/javascript">
<!--
var submitter = 0;

var aaa = null;
//alert(submitter);
function submitFunction() {
  aaa = null;
  if(document.one_page_checkout.cot_gv != null && document.one_page_checkout.cot_gv != 'undefined')
  {
    if(document.one_page_checkout.cot_gv.checked)
      aaa = 1;
  }
}

function check_addresses(theForm) {
  var error = 0;
  var error_message = "<?php echo JS_ERROR; ?>";

  if(submitter == 1){ 
    alert( "<?php echo JS_ERROR_SUBMITTED; ?>"); 
    return false; 
  }
   
  var email_address = theForm.email_address.value;  
  var street_address = theForm.street_address_line1.value;
  var postcode = theForm.postcode.value;
  var city = theForm.city.value;
  var telephone = theForm.telephone.value;
<?php
//if ($shipping !== false){
if (($order->content_type != 'virtual') && ($order->content_type != 'virtual_weight') ) {
?>
  var ship_firstname = theForm.ship_firstname.value;
  var ship_lastname = theForm.ship_lastname.value;
  var ship_street_address = theForm.ship_street_address_line1.value;
  var ship_postcode = theForm.ship_postcode.value;
  var ship_city = theForm.ship_city.value;
<?php
}
?>

<?php
   if (ACCOUNT_GENDER == 'required' || ACCOUNT_GENDER == 'required_register') {
?>
  if (theForm.elements['gender'].type != "hidden") {
    if (theForm.gender[0].checked || theForm.gender[1].checked) {
    } else {
      error_message = error_message + "<?php echo JS_GENDER; ?>";
      error = 1;
    }
  }
<?php
  }
?>

<?php
   if (ACCOUNT_FIRSTNAME == 'required' || ACCOUNT_FIRSTNAME == 'required_register') {
?>
  var first_name = theForm.firstname.value;
  if (theForm.elements['firstname'].type != "hidden") {
    if (first_name == '' || first_name.length < <?php echo ENTRY_FIRST_NAME_MIN_LENGTH; ?>) {
      error_message = error_message + "<?php echo JS_FIRST_NAME; ?>";
      error = 1;
    }
  }
<?php
  }
?>

<?php
   if (ACCOUNT_LASTNAME == 'required' || ACCOUNT_LASTNAME == 'required_register') {
?>
  var last_name = theForm.lastname.value;
  if (theForm.elements['lastname'].type != "hidden") {
    if (last_name == '' || last_name.length < <?php echo ENTRY_LAST_NAME_MIN_LENGTH; ?>) {
      error_message = error_message + "<?php echo JS_LAST_NAME; ?>";
      error = 1;
    }
  }
<?php
  }
?>

<?php 
  if (ACCOUNT_DOB == 'required' || ACCOUNT_DOB == 'required_register') {
?>
  if (theForm.elements['dob'] && (theForm.elements['dob'].type != "hidden")) {
    var dob = theForm.dob.value;
    if (dob == '' || dob.length < <?php echo ENTRY_DOB_MIN_LENGTH;?>) {
      error_message = error_message + "* " + "<?php echo ENTRY_DATE_OF_BIRTH_ERROR;?>" + "\n";
      error = 1;
    }
  }
<?php
  }
?>

  if (theForm.elements['street_address_line1'].type != "hidden") {
    if (street_address == '' || street_address.length < <?php echo ENTRY_STREET_ADDRESS_MIN_LENGTH; ?>) {
      error_message = error_message + "<?php echo JS_ADDRESS; ?>";
      error = 1;
    }
  }

  if (theForm.elements['postcode'].type != "hidden") {
    if (postcode == '' || postcode.length < <?php echo ENTRY_POSTCODE_MIN_LENGTH; ?>) {
      error_message = error_message + "<?php echo JS_POST_CODE; ?>";
      error = 1;
    }
  }

  if (theForm.elements['city'].type != "hidden") {
    if (city == '' || city.length < <?php echo ENTRY_CITY_MIN_LENGTH; ?>) {
      error_message = error_message + "<?php echo JS_CITY; ?>";
      error = 1;
    }
  }

<?php
  if (ACCOUNT_STATE == 'required') {
?>
  if (theForm.elements['state'].type != "hidden") {
    if (theForm.state.value == '' || theForm.state.value.length < <?php echo ENTRY_STATE_MIN_LENGTH; ?> ) {
       error_message = error_message + "<?php echo JS_STATE; ?>";
       error = 1;
    }
  }
<?php
  }
?>
  
  if (theForm.elements['country'].type != "hidden") {
    if (theForm.country.value == 0) {
      error_message = error_message + "<?php echo JS_COUNTRY; ?>";
      error = 1;
    }
  }
  
<?php
//if ($shipping !== false){
if (($order->content_type != 'virtual') && ($order->content_type != 'virtual_weight') ) {
?>
<?php
   if (ACCOUNT_GENDER == 'required' || ACCOUNT_GENDER == 'required_register') {
?>
  if (theForm.elements['shipping_gender'].type != "hidden") {
    if (theForm.shipping_gender[0].checked || theForm.shipping_gender[1].checked) {
    } else {
      error_message = error_message + "<?php echo JS_GENDER; ?>";
      error = 1;
    }
  }
<?php
  }
?>  

  if (theForm.elements['ship_firstname'].type != "hidden") {
    if (ship_firstname == '' || ship_firstname.length < <?php echo ENTRY_FIRST_NAME_MIN_LENGTH; ?>) {
      error_message = error_message + "<?php echo JS_FIRST_NAME; ?>";
      error = 1;
    }
  }

  if (theForm.elements['ship_lastname'].type != "hidden") {
    if (ship_lastname == '' || ship_lastname.length < <?php echo ENTRY_LAST_NAME_MIN_LENGTH; ?>) {
      error_message = error_message + "<?php echo JS_LAST_NAME; ?>";
      error = 1;
    }
  }

  if (theForm.elements['ship_street_address_line1'].type != "hidden") {
    if (ship_street_address == '' || ship_street_address.length < <?php echo ENTRY_STREET_ADDRESS_MIN_LENGTH; ?>) {
      error_message = error_message + "<?php echo JS_SHIP_ADDRESS; ?>";
      error = 1;
    }
  }

  if (theForm.elements['ship_postcode'].type != "hidden") {
    if (ship_postcode == '' || ship_postcode.length < <?php echo ENTRY_POSTCODE_MIN_LENGTH; ?>) {
      error_message = error_message + "<?php echo JS_SHIP_POST_CODE; ?>";
      error = 1;
    }
  }

  if (theForm.elements['ship_city'].type != "hidden") {
    if (ship_city == '' || ship_city.length < <?php echo ENTRY_CITY_MIN_LENGTH; ?>) {
      error_message = error_message + "<?php echo JS_SHIP_CITY; ?>";
      error = 1;
    }
  }

<?php
  if (ACCOUNT_STATE == 'required') {
?>
  var ship_state = document.getElementById('ctl_ship_state').firstChild
  if (ship_state.type != "hidden") {
    if (ship_state.value == '' || ship_state.value.length < <?php echo ENTRY_STATE_MIN_LENGTH; ?> ) {
       error_message = error_message + "<?php echo JS_SHIP_STATE; ?>";
       error = 1;
    }
  }
<?php
  }
?>
  
  if (theForm.elements['ship_country'].type != "hidden") {
    if (theForm.ship_country.value == 0) {
      error_message = error_message + "<?php echo JS_SHIP_COUNTRY; ?>";
      error = 1;
    }
  }
<?php
}
?>
  if (theForm.elements['email_address'].type != "hidden") {
    if (email_address == '' || email_address.length < <?php echo ENTRY_EMAIL_ADDRESS_MIN_LENGTH; ?>) {
      error_message = error_message + "<?php echo JS_EMAIL_ADDRESS; ?>";
      error = 1;
    }
  }

  if (theForm.elements['telephone'].type != "hidden") {
    if (telephone == '' || telephone.length < <?php echo ENTRY_TELEPHONE_MIN_LENGTH; ?>) {
      error_message = error_message + "<?php echo JS_TELEPHONE; ?>";
      error = 1;
    }
  }
<?php 
if (defined('ONE_PAGE_CREATE_ACCOUNT') && ONE_PAGE_CREATE_ACCOUNT!='false') { 
?>  
  if (theForm.elements['password_new'] && (theForm.elements['password_new'].type != "hidden")) {
    var password = theForm.elements['password_new'].value;
    var confirmation = theForm.elements['confirmation_new'].value;

    if (password != confirmation) {
      error_message = error_message + "* " + "The Password Confirmation must match your Password." + "\n";
      error = 1;
    }
  }
<?php
}
?>  
<?php if (GERMAN_SITE == 'True'){ ?>
 if (theForm.elements['conditions'] && !theForm.elements['conditions'].checked){
   error = 1;
   error_message = error_message + "<?php echo ERROR_JS_CONDITIONS_NOT_ACCEPTED;?>";
 }
<?php } ?>

  if (error == 1) {
    alert(error_message);
    return false;
  } else {
    <?php if ( !defined('ONE_PAGE_POST_PAYMENT') ) { ?> if (!check_form()) return false; <?php } ?>
    submitter = 1;
    return true;
  }
}

function copy_shipping(theCheckBox)
{
  var f = theCheckBox.form;
  if (theCheckBox.checked)
  {
<?php
  if (tep_session_is_registered('customer_id')) {
?>
    f.sendto.value = f.billto.value;
<?php
  }
?>
    f.ship_firstname.value = f.firstname.value;
    f.ship_lastname.value = f.lastname.value;
    f.ship_street_address_line1.value = f.street_address_line1.value;
<?php if (ACCOUNT_COMPANY == 'true'){
?>
  f.ship_company.value = f.company.value;
<?php
  }
?>
<?php
   if (ACCOUNT_STATE == 'required' || ACCOUNT_STATE == 'visible') {
?>
    f.shipping_gender[0].checked = f.gender[0].checked;
    f.shipping_gender[1].checked = f.gender[1].checked;
<?php
  }
?>      
<?php
  if (ACCOUNT_SUBURB == 'true') {
?>
    f.ship_street_address_line2.value = f.street_address_line2.value;
<?php
  }
?>
    f.ship_city.value = f.city.value;
<?php
  if (ACCOUNT_STATE == 'required' || ACCOUNT_STATE == 'visible') {
?>
    f.ship_state.value = f.state.value;
<?php
  }
?>
    f.ship_postcode.value = f.postcode.value;
    f.ship_country.value = f.country.value;
  }
  else
  {
<?php
  if (tep_session_is_registered('customer_id')) {
?>
    f.sendto.value = '';
<?php
  }
?>
    f.ship_firstname.value = '';
    f.ship_lastname.value = '';
    f.ship_street_address_line1.value = '';
<?php
  if (ACCOUNT_SUBURB == 'true') {
?>
    f.ship_street_address_line2.value = '';
<?php
  }
?>
    f.ship_city.value = '';
<?php
  if (ACCOUNT_STATE == 'required' || ACCOUNT_STATE == 'visible') {
?>
    document.getElementById('ctl_ship_state').firstChild.value = '';
<?php
  }
?>
    f.ship_postcode.value = '';
    f.ship_country.value = '';
  }
  DoFSCommand(2);
}

var selected_shipping;
var selected_shipping_id=-1;
var shipping_code='none';

function selectRowEffect_ship(object, buttonSelect) {
  if (!selected_shipping) {
    if (document.getElementById) {
      selected_shipping = document.getElementById('defaultSelected_ship');
    } else {
      selected_shipping = document.all['defaultSelected_ship'];
    }
  }

  if (selected_shipping) selected_shipping.className = 'moduleRow';
  object.className = 'moduleRowSelected';
  selected_shipping = object;

// one button is not an array
  shipping_code = 'none';
  if (document.getElementById('shipping_' + buttonSelect)){
    document.getElementById('shipping_' + buttonSelect).checked = true;
    shipping_code = document.getElementById('shipping_' + buttonSelect).value;
  }else{
    if (document.one_page_checkout.shipping[0]) {
      document.one_page_checkout.shipping[buttonSelect].checked=true;
      shipping_code = document.one_page_checkout.shipping[buttonSelect].value;
    } else {
      if (document.one_page_checkout.shipping != undefined){
        document.one_page_checkout.shipping.checked=true;
        shipping_code = document.one_page_checkout.shipping.value;
      }
    }
  }
  selected_shipping_id = buttonSelect;
<?php if(defined('ONE_PAGE_SHOW_TOTALS') && ONE_PAGE_SHOW_TOTALS=='true') { ?>
  onSelectShipping(shipping_code);
<?php } ?>
}

function rowOverEffect_ship(object) {
  if (object.className == 'moduleRow') object.className = 'moduleRowOver';
}

function rowOutEffect_ship(object) {
  if (object.className == 'moduleRowOver') object.className = 'moduleRow';
}

var selected_payment;

function selectRowEffect_paym(object, buttonSelect) {
  if (!selected_payment) {
    if (document.getElementById) {
      selected_payment = document.getElementById('defaultSelected_paym');
    } else {
      selected_payment = document.all['defaultSelected_paym'];
    }
  }

  if (selected_payment) selected_payment.className = 'moduleRow';
  object.className = 'moduleRowSelected';
  selected_payment = object;

// one button is not an array
  if (document.one_page_checkout.payment[0]) {
    document.one_page_checkout.payment[buttonSelect].checked=true;
  } else {
    document.one_page_checkout.payment.checked=true;
  }
}

function rowOverEffect_paym(object) {
  if (object.className == 'moduleRow') object.className = 'moduleRowOver';
}

function rowOutEffect_paym(object) {
  if (object.className == 'moduleRowOver') object.className = 'moduleRow';
}

function rowOverEffect(object) {
  if (object.className == 'moduleRow') object.className = 'moduleRowOver';
}

function rowOutEffect(object) {
  if (object.className == 'moduleRowOver') object.className = 'moduleRow';
}

//-->
</script>
<script src="<?php echo DIR_WS_JAVASCRIPT . 'utils.js';?>" type="text/javascript"></script>
<script language="JavaScript" type="text/javascript">
<!--
var def_cursor = null;
function doWait(){
  if ( def_cursor==null ) def_cursor = document.body.style.cursor; 
  document.body.style.cursor = 'wait'; 
}
function resetWait(){ 
  if ( def_cursor==null ) {
    document.body.style.cursor = def_cursor;
  }else{
    document.body.style.cursor = '';
  } 
}
var aj_variants = new Array();
var aj_payments;

var _fastCache = new Array();
function fastGet(elId){
  if ( _fastCache[elId]==undefined ) {
    var t = document.getElementById(elId);
    if ( t==null || t==undefined ) t=false;
    _fastCache[elId] = t;
  }
  return _fastCache[elId];
}
<?php if(defined('ONE_PAGE_SHOW_TOTALS') && ONE_PAGE_SHOW_TOTALS!='true') { ?>
function onSelectShipping( newOne ){}
<?php }?>
<?php if(defined('ONE_PAGE_SHOW_TOTALS') && ONE_PAGE_SHOW_TOTALS=='true') { ?>
function errc(){
 if (document.one_page_checkout.gv_redeem_code != undefined ) document.one_page_checkout.gv_redeem_code.value='';
}
function onSelectShipping( newOne ){
  if ( aj_variants.length==0 || aj_variants[newOne]==undefined ) return;
  shipping_code = newOne;
  var newTotals = aj_variants[newOne].ot;
  hideOtRows();
  var ot_table = fastGet('ot_table');
  if ( ot_table!=false ) ot_table.style.display = '';
  for( i=0; i<newTotals.length; i++ ) {
<?php if(defined('ONE_PAGE_SHOW_CART') && ONE_PAGE_SHOW_CART=='true') { ?>  
    if ( newTotals[i]['oc']=='ot_subtotal' ) {
      var cart_subtotal = fastGet('cart_subtotal');
      if ( cart_subtotal!=false ) cart_subtotal.innerHTML = newTotals[i]['cost'];
    }
<?php } ?>
    var otTR = fastGet(newTotals[i]['oc']);
    if ( otTR!=false ) otTR.style.display = '';
    var _cost = fastGet(newTotals[i]['oc']+'_cost');
    _cost.innerHTML = newTotals[i]['cost'];
    var _text = fastGet(newTotals[i]['oc']+'_text');
    _text.innerHTML = newTotals[i]['text'];
  }
  if ( aj_variants[newOne].label_coupon!=undefined ) {
    var label_coupon = fastGet('label_coupon');
    if ( label_coupon!=false ) label_coupon.innerHTML = aj_variants[newOne].label_coupon;
    if ( aj_variants[newOne].errc != undefined && aj_variants[newOne].errc ) errc(); 
  }  
}
function hideOtRows(){
<?php
  if (defined('MODULE_ORDER_TOTAL_INSTALLED') && tep_not_null(MODULE_ORDER_TOTAL_INSTALLED)) {
    $sot_modules = explode(';', MODULE_ORDER_TOTAL_INSTALLED);
    if (is_array($sot_modules)) foreach ($sot_modules as $value) {
      $sot_class = substr($value, 0, strrpos($value, '.'));
?>
   var otTR = fastGet('<?php echo $sot_class; ?>');
   if (otTR!=false) otTR.style.display = 'none';
<?php
    }
?>
   var label_coupon = fastGet('label_coupon');
   if ( label_coupon!=false ) label_coupon.innerHTML = '&nbsp;';
<?php
  }
?>    
}
function btnRedeemClick(){
  var quotes_result = new Subsys_JsHttpRequest_Js();
  quotes_result.onreadystatechange = function() {
    if (quotes_result.readyState == 4) {
      resetWait();
      if (quotes_result.responseJS) {
        hideOtRows();
        aj_variants = quotes_result.responseJS.ajot_array;
        onSelectShipping( shipping_code );
      }
    }
  }
  var label_coupon = fastGet('label_coupon');
  if ( label_coupon!=false ) label_coupon.innerHTML = '&nbsp;';
  
  quotes_result.caching = false;
  var opcForm = document.one_page_checkout;
  quotes_result.open('POST', 'opc_ajax.php', true);
  <?php if (($order->content_type == 'virtual') || ($order->content_type == 'virtual_weight') ) { ?>
var post_ = { q: 'apply_coupon', <?php echo tep_session_name()?>: '<?php echo tep_session_id() ;?>', postcode : opcForm.postcode.value , country : opcForm.country.options[opcForm.country.selectedIndex].value, state : (opcForm.state != null && opcForm.state != undefined?(opcForm.state.tagName.toUpperCase() == 'INPUT'?opcForm.state.value:opcForm.state.options[opcForm.state.selectedIndex].value):'')};
  <?php }else{ ?>
<?php if (ACCOUNT_STATE == 'required' || ACCOUNT_STATE == 'visible') { ?>
  var ship_state = document.getElementById('ctl_ship_state').firstChild;
<?php } ?>
var post_ = {q: 'apply_coupon', <?php echo tep_session_name()?>: '<?php echo tep_session_id() ;?>', 
  state : (opcForm.state != null && opcForm.state != undefined?(opcForm.state.tagName.toUpperCase() == 'INPUT'?opcForm.state.value:opcForm.state.options[opcForm.state.selectedIndex].value):''),
  country : (opcForm.country.type != "hidden")? opcForm.country.options[opcForm.country.selectedIndex].value: opcForm.country.value,
<?php if(defined('ONE_PAGE_SHOW_TOTALS') && ONE_PAGE_SHOW_TOTALS=='true') { ?>
  gv_redeem_code: (opcForm.gv_redeem_code!=undefined)?opcForm.gv_redeem_code.value:'',cot_gv: (opcForm.cot_gv!=undefined && opcForm.cot_gv.checked)?opcForm.cot_gv.value:'',
<?php }?>
  ship_country : (opcForm.ship_country.type != "hidden")? opcForm.ship_country.options[document.one_page_checkout.ship_country.selectedIndex].value: opcForm.ship_country.value , 
  ship_state : (opcForm.ship_state != null && opcForm.ship_state != undefined?(ship_state.tagName.toUpperCase() == 'INPUT'?ship_state.value:ship_state.options[ship_state.selectedIndex].value):''),
  ship_postcode : opcForm.ship_postcode.value }; 
  <?php } ?>
  doWait();
  quotes_result.send(post_);
}
<?php } ?>

function DoFSCommand(chk) {
<?php if (ACCOUNT_STATE == 'required' || ACCOUNT_STATE == 'visible') { ?>
  if (chk==2){
	  checkStateCtl();
  }
<?php } ?>
<?php if (($order->content_type != 'virtual') && ($order->content_type != 'virtual_weight') ) { ?>
  var cObj = document.getElementById('shipping_div')
  if ( cObj!=null && cObj!=undefined ){ 
    while (cObj.childNodes.length){
      cObj.removeChild(cObj.childNodes[cObj.childNodes.length - 1]);
    }
    cObj.innerHTML = '<table border="0" width="100%" cellspacing="1" cellpadding="0" class="contentBox"><tr><td><table border="0" width="100%" cellspacing="0" cellpadding="2" class="contentBoxContents"><tr><td align="center" class="main">Receiving data, please wait...</td></tr></table></td></tr></table>'; 
<?php    /*
  "
          <tr>
            <td><table border='0' width='100%' cellspacing='0' cellpadding='2' class='contentBoxContents'><tr><td align='center' class='main'>Receiving data, please wait...</td></tr></table></td></tr></table>";
            */ ?>
  }
<?php } ?>
  get_shipping_quotes();
}

function get_shipping_quotes() {
  var quotes_result = new Subsys_JsHttpRequest_Js();
  quotes_result.onreadystatechange = function() {
    if (quotes_result.readyState == 4) {
      if (quotes_result.responseJS) {console.log(quotes_result.responseJS);
        resetWait();
<?php if(defined('ONE_PAGE_SHOW_TOTALS') && ONE_PAGE_SHOW_TOTALS=='true') { ?>
        hideOtRows();
<?php } ?>
<?php if (($order->content_type != 'virtual') && ($order->content_type != 'virtual_weight') ) { ?>
        var cMainTable = document.createElement('table');
        var cMainTableBody = document.createElement('tbody');
        cMainTable.className = 'contentBox';
        cMainTable.style.width = '100%';
        cMainTable.cellPadding = 0;
        cMainTable.cellSpacing = 1;
        cMainTable.border = 0;
        var cMainTr = document.createElement('tr');
        var cMainTd = document.createElement('td');
        var cSubTable = document.createElement('table');
        var cSubTableBody = document.createElement('tbody');
        cSubTable.className = 'contentBoxContents';
        cSubTable.style.width = '100%';
        cSubTable.cellPadding = 2;
        cSubTable.cellSpacing = 0;
        cSubTable.border = 0;
                
        cMainTable.appendChild(cMainTableBody);
        cMainTableBody.appendChild(cMainTr);
        cMainTr.appendChild(cMainTd);
        cMainTd.appendChild(cSubTable);
        
        cSubTable.appendChild(cSubTableBody);
        
        var aj_first = quotes_result.responseJS.first;
        if ( aj_first.length>0 ) {
          var cFirstSubTr = document.createElement('tr');
          cSubTableBody.appendChild(cFirstSubTr);

          for (i=0;i<aj_first.length;i++){
            var cSubTd = document.createElement('td');
            if (aj_first[i]['width'] != undefined) cSubTd.style.width = aj_first[i]['width'];
            if (aj_first[i]['valign'] != undefined) cSubTd.style.verticalAlign = aj_first[i]['valign'];
            if (aj_first[i]['align'] != undefined) cSubTd.style.textAlign = aj_first[i]['align'];
            if (aj_first[i]['class'] != undefined) cSubTd.className = aj_first[i]['class'];
            cSubTd.innerHTML = aj_first[i]['text'];
            cFirstSubTr.appendChild(cSubTd);
          }
        }
        var cSubTr = document.createElement('tr');
        cSubTableBody.appendChild(cSubTr);
        

        var cRateTable = document.createElement('table');
        var cRateTableBody = document.createElement('tbody');
        cRateTable.style.width = '100%';
        cRateTable.cellPadding = 2;
        cRateTable.cellSpacing = 0;
        cRateTable.border = 0;
        cRateTable.appendChild(cRateTableBody);
        
        var aj_result_array = quotes_result.responseJS.result_array;
        for (i=0;i<aj_result_array.length;i++){
          var cRateTr = document.createElement('tr');
          var rateBill = false;
          var rateid = -1;
          for (j=0;j<aj_result_array[i].length;j++) {
            var cRateTd = document.createElement('td');
            if (aj_result_array[i][j]['class'] != undefined) cRateTd.className = aj_result_array[i][j]['class'];
            if (aj_result_array[i][j]['colspan'] != undefined) cRateTd.colSpan = aj_result_array[i][j]['colspan'];
            if (aj_result_array[i][j]['width'] != undefined) cRateTd.style.width = aj_result_array[i][j]['width'];

            cRateTd.innerHTML = aj_result_array[i][j]['text'];
            if (aj_result_array[i][j]['object'] != undefined) {
              var cObj;
              cRateTd.innerHTML = '<input type="' + aj_result_array[i][j]['object']['type'] + '" name="' + aj_result_array[i][j]['object']['name'] + '" value="' +  aj_result_array[i][j]['object']['value']+ '" id="shipping_' + aj_result_array[i][j]['id'] + '">';
              rateBill = true;
              if (aj_result_array[i][j]['id'] != undefined) {
                rateid = aj_result_array[i][j]['id'];
              }
            }
            
            cRateTr.appendChild(cRateTd);
          }
          if (rateBill) {
            cRateTr.className = 'moduleRow';
            cRateTr.onmouseover = function() { rowOverEffect_ship(this); }
            cRateTr.onmouseout = function() { rowOutEffect_ship(this); }
            if (rateid != -1) {
              cRateTr.rtId = rateid;
              cRateTr.onclick = function(){ selectRowEffect_ship(this, this.rtId); }
            }
          }
          cRateTableBody.appendChild(cRateTr);
        }

        var cSubTd = document.createElement('td');
        cSubTr.appendChild(cSubTd);
        var cSubTd = document.createElement('td');
        cSubTd.style.width = '100%';
        cSubTd.colSpan = 2;

        cSubTr.appendChild(cSubTd);
        cSubTd.appendChild(cRateTable);
        
        var cSubTd = document.createElement('td');
        cSubTr.appendChild(cSubTd);
        
        //cMainTable.innerHTML = cMainTable.innerHTML
        var cObj = document.getElementById('shipping_div');
        if ( cObj!=null && cObj!=undefined ){
          cObj.innerHTML = '';
        //cObj.removeNode(true);
          cObj.appendChild(cMainTable);
        }
<?php } ?>        
<?php if (ACCOUNT_STATE == 'required' || ACCOUNT_STATE == 'visible') { ?>
        if (quotes_result.responseJS.ctl_ship_state != undefined) {
          document.getElementById('ctl_ship_state').innerHTML = quotes_result.responseJS.ctl_ship_state;
          var ctlAttach = document.getElementById('ctl_ship_state').firstChild;
          ctlAttach.setAttribute("onchange", function() {DoFSCommand();} );
          ctlAttach.name = 'ship_state'; 
          ctlAttach.onchange = function(){DoFSCommand();};
        }
        if (quotes_result.responseJS.ctl_state != undefined) {
          document.getElementById('ctl_state').innerHTML = quotes_result.responseJS.ctl_state;
          var ctlAttach2 = document.getElementById('ctl_state').firstChild;
          ctlAttach2.setAttribute("onchange", function() {DoFSCommand();} );
          ctlAttach2.name = 'state'; 
          ctlAttach2.onchange = function(){DoFSCommand();};
        }
<?php } ?>
        if (quotes_result.responseJS.ajot_array != undefined) aj_variants = quotes_result.responseJS.ajot_array;
        selected_shipping = false;
        selected_shipping_id = -1;
        var hilite_ship = quotes_result.responseJS.prefered_shipping; 
        if ( shipping_code!='none' && aj_variants[shipping_code]!=undefined) {
          hilite_ship = shipping_code;
        }
        if ( hilite_ship!=null && hilite_ship!='none' ) {
          var html_ship = document.getElementsByName('shipping');
          for (x=0;x<html_ship.length;x++){
            if ( html_ship[x].tagName.toUpperCase()!='INPUT' ) continue;
            if ( html_ship[x].type.toUpperCase()=='RADIO' && html_ship[x].value==hilite_ship ) {
              var parentTR = html_ship[x].parentNode.parentNode;
              parentTR.onclick();
              break;
            }
            if ( html_ship[x].type.toUpperCase()=='HIDDEN' && html_ship[x].value==hilite_ship ) {
              onSelectShipping(hilite_ship);
              break;
            }
          }
        }else{
          onSelectShipping('none');
        }

        if ( quotes_result.responseJS.payments!=undefined ) {
          aj_payments = quotes_result.responseJS.payments;
          payment_check();
        }
<?php
   if(defined('ONE_PAGE_SHOW_TOTALS') && ONE_PAGE_SHOW_TOTALS=='true') {
?>
        if ( quotes_result.responseJS.label_coupon!= undefined ) {
          var label_coupon = fastGet('label_coupon');
          if ( label_coupon!=false ) label_coupon.innerHTML = quotes_result.responseJS.label_coupon;
        };
<?php
   }
?>
          
<?php if (defined('ONE_PAGE_SHOW_CART') && ONE_PAGE_SHOW_CART=='true') { ?>
        var cObjCart = document.getElementById('div_cart_contents');
        if ( cObjCart!=null && cObjCart!=undefined ) {
//document.getElementById('cart_subtotal').innerHTML = pot_subtotal;
          cObjCart.innerHTML = quotes_result.responseJS.cart_content;
//          var cObjCartST = document.getElementById('cart_subtotal');
//          if ( cObjCartST!=null && cObjCartST!='undefined' ) cObjCartST.innerHTML = formatNumber(pot_subtotal); 
        }
<?php } //ONE_PAGE_SHOW_CART ?>
      }
    }
  }
  quotes_result.caching = false;
  var opcForm = document.one_page_checkout;
  var country_difined = '<?php echo (!$cart_address_id && $cart_country_id?$cart_country_id:'')?>';
  var postcode_defined = '<?php echo (!$cart_address_id && $cart_zip_code?$cart_zip_code:'')?>';
  quotes_result.open('POST', 'opc_ajax.php', true);
  <?php if (($order->content_type == 'virtual') || ($order->content_type == 'virtual_weight') ) { ?>
var post_ = { q: 'get_rates', <?php echo tep_session_name()?>: '<?php echo tep_session_id() ;?>', 
  postcode : opcForm.postcode.value,
<?php if(defined('ONE_PAGE_SHOW_TOTALS') && ONE_PAGE_SHOW_TOTALS=='true') { ?>
  gv_redeem_code: (opcForm.gv_redeem_code!=undefined)?opcForm.gv_redeem_code.value:'',
<?php }?>
  country : opcForm.country.options[opcForm.country.selectedIndex].value, 
  state : (opcForm.state != null && opcForm.state != undefined?(opcForm.state.tagName.toUpperCase() == 'INPUT'?opcForm.state.value:opcForm.state.options[opcForm.state.selectedIndex].value):'')};  
  <?php }else{ ?>
<?php if (ACCOUNT_STATE == 'required' || ACCOUNT_STATE == 'visible') { ?>
  var ship_state = document.getElementById('ctl_ship_state').firstChild;
<?php } ?>
var post_ = {q: 'get_rates', <?php echo tep_session_name()?>: '<?php echo tep_session_id() ;?>', 
  state : (opcForm.state != null && opcForm.state != undefined?(opcForm.state.tagName.toUpperCase() == 'INPUT'?opcForm.state.value:opcForm.state.options[opcForm.state.selectedIndex].value):''),
  country : (opcForm.country.type != "hidden")? opcForm.country.options[opcForm.country.selectedIndex].value: opcForm.country.value,
<?php if(defined('ONE_PAGE_SHOW_TOTALS') && ONE_PAGE_SHOW_TOTALS=='true') { ?>
  gv_redeem_code: (opcForm.gv_redeem_code!=undefined)?opcForm.gv_redeem_code.value:'',
<?php }?>
  ship_country : (country_difined !='' ? country_difined :(opcForm.ship_country.type != "hidden")? opcForm.ship_country.options[document.one_page_checkout.ship_country.selectedIndex].value: opcForm.ship_country.value) , 
  ship_state : (opcForm.ship_state != null && opcForm.ship_state != undefined?(ship_state.tagName.toUpperCase() == 'INPUT'?ship_state.value:ship_state.options[ship_state.selectedIndex].value):''),
  ship_postcode : (postcode_defined !=''? postcode_defined :opcForm.ship_postcode.value) }; 
//alert(post_.postcode+'|'+post_.country+'|'+post_.state);    
  <?php } ?>
  doWait();
  quotes_result.send(post_);
}
<?php if (ACCOUNT_STATE == 'required' || ACCOUNT_STATE == 'visible') { ?>
function ship_state_reset(ctlText){
  document.getElementById('ctl_ship_state').innerHTML = '<input type="text" name="ship_state" value="'+ctlText+'" <?php echo CHECKOUT_CTLPARAM_COMMON;?>>';
}
function bill_state_reset(ctlText){
  document.getElementById('ctl_state').innerHTML = '<input type="text" name="state" value="'+ctlText+'" <?php echo CHECKOUT_CTLPARAM_COMMON;?>>';
}

function checkStateCtl(par){
  if ( par==null || par==undefined ) par=0;
  var country_with_state = [-1<?php
  $c_r = tep_db_query('select distinct zone_country_id as id from '.TABLE_ZONES.' order by id');
  while ( $c_d = tep_db_fetch_array($c_r) ) { echo ','.$c_d['id']; }
  ?>];  
  var have_ship_addr = false;
  if ( document.getElementById('ctl_ship_state')!=null && document.getElementById('ctl_ship_state')!=undefined ) have_ship_addr = true;
  if ( have_ship_addr ) {
  var select = document.getElementById('ctl_ship_state').firstChild;
  var chng_ship = (select.tagName.toUpperCase() == 'SELECT');
  }
  var select2 = document.getElementById('ctl_state').firstChild
  var chng_bill = (select2.tagName.toUpperCase() == 'SELECT');
  var load_bill = false;
  for ( i=0; i<country_with_state.length; i++ ) {
    if ( country_with_state[i]==document.one_page_checkout.country.value) { chng_bill = false; load_bill=true;}
    if ( document.one_page_checkout.ship_country!=undefined ) {
      if ( have_ship_addr && country_with_state[i]==document.one_page_checkout.ship_country.value) { chng_ship = false; }      
    }
  }
  var _ret = false;
  if ( (have_ship_addr && chng_ship) || par==3 ) {
    var ctlText = select.value;
    //if (select.selectedIndex!=0) ctlText = select.options[ select.selectedIndex ].text;
  	document.getElementById('ctl_ship_state').innerHTML = '<input type="text" name="ship_state" value="'+ctlText+'" <?php echo CHECKOUT_CTLPARAM_COMMON;?>>';
  }
  if ( chng_bill ) {
    var ctlText = select2.value;
    //if (select2.selectedIndex!=0) ctlText = select2.options[ select2.selectedIndex ].text;
  	document.getElementById('ctl_state').innerHTML = '<input type="text" name="state" value="'+ctlText+'" <?php echo CHECKOUT_CTLPARAM_COMMON;?>>';
  	_ret = false;
  }else{
    _ret = load_bill;
  }
  return _ret;
}
<?php } ?>

function getbillstate(){
  if ( true ) { //checkStateCtl() ) {
    var quotes_result = new Subsys_JsHttpRequest_Js();
    quotes_result.onreadystatechange = function() {
      if (quotes_result.readyState == 4) {      
        resetWait();
        if (quotes_result.responseJS) {
<?php if (ACCOUNT_STATE == 'required' || ACCOUNT_STATE == 'visible') { ?>        
          if (quotes_result.responseJS.ctl_state != undefined) {
            document.getElementById('ctl_state').innerHTML = quotes_result.responseJS.ctl_state;
            var ctlAttach = document.getElementById('ctl_state').firstChild;
            ctlAttach.name = 'state';
          }
<?php } ?>
          if ( quotes_result.responseJS.payments!=undefined ) {
            aj_payments = quotes_result.responseJS.payments;
            payment_check();
          }
        }
      }
    }
    quotes_result.caching = false;
<?php if (ACCOUNT_STATE == 'required' || ACCOUNT_STATE == 'visible') { ?>        
    var bill_state = document.getElementById('ctl_state').firstChild;
    //var ship_state = document.getElementById('ctl_ship_state').firstChild;
<?php } ?>
    var opcForm = document.one_page_checkout;
    quotes_result.open('POST', 'opc_ajax.php', true);
    doWait();
    quotes_result.send({ q: 'get_states', <?php echo tep_session_name()?>: '<?php echo tep_session_id() ;?>',
<?php if (($order->content_type != 'virtual') && ($order->content_type != 'virtual_weight') ) { ?>
  ship_country : (opcForm.ship_country.type != "hidden")? opcForm.ship_country.options[opcForm.ship_country.selectedIndex].value: opcForm.ship_country.value, 
  ship_state : (opcForm.ship_state != null && opcForm.ship_state != undefined?(opcForm.ship_state.value):''),
<?php }?>     
<?php if (ACCOUNT_STATE == 'required' || ACCOUNT_STATE == 'visible') { ?>        
    state : ((bill_state != undefined && bill_state != null)?(bill_state.tagName.toUpperCase() == 'INPUT'?bill_state.value:bill_state.options[bill_state.selectedIndex].value):''),
<?php }else{ ?>
    state : '',
<?php } ?>
    country : (opcForm.country.type != "hidden")? opcForm.country.options[opcForm.country.selectedIndex].value: opcForm.country.value 
        });
  }
}

function in_array(val,arr){
  var res = false;
  for ( x=0; x<arr.length; x++ ) {
    if ( arr[x]==val ) { res=true; break; }
  }
  return res;
}

function payment_check(){
  var _fp = ['none'<?php
    $selection = $payment_modules->selection(true);
    if ( is_array($selection) ) foreach($selection as $p_sel){
      echo ',\''.$p_sel['id'].'\'';
    }
?>];
  if( typeof aj_payments != 'object' || aj_payments.length<1 ) return; //????
  for ( i=0; i<_fp.length; i++ ) {
    var pr = fastGet( _fp[i]+'_payment' );
    if ( in_array( _fp[i], aj_payments ) ) {
      if ( pr!=false ) pr.style.display='';
    }else{
      if ( pr!=false ) pr.style.display='none';
    }
  }
  // checked payment is
  var form_payments = document.getElementsByName('payment');
  var current = '';
  for ( i=0; i<form_payments.length; i++ ){
    if ( form_payments[i].tagName.toUpperCase() != 'INPUT' ) continue;
    if ( form_payments[i].type.toUpperCase()=='RADIO' && form_payments[i].checked 
         || form_payments[i].type.toUpperCase()=='HIDDEN'
       ) {
      current = form_payments[i].value;
      if ( !in_array(current,aj_payments) && form_payments[i].type.toUpperCase()=='RADIO' ) {
        form_payments[i].checked = false;
        selected_payment = null;
      }
      break;
    }
  }
//  alert(current);
}
//-->
</script>


<?php
if ( !defined('ONE_PAGE_POST_PAYMENT') ) {
  $javascript_validation = $payment_modules->javascript_validation();
  echo str_replace('document.checkout_payment', 'document.one_page_checkout', $javascript_validation);
}  
} // end if ($cart->count_contents() > 0)
?>
