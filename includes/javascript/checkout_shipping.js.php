<?php require(DIR_WS_JAVASCRIPT . 'form_check.js.php'); ?>
<script language="javascript"><!--
var selected;

function selectRowEffect(object, buttonSelect) {
  if (!selected) {
    if (document.getElementById) {
      selected = document.getElementById('defaultSelected');
    } else {
      selected = document.all['defaultSelected'];
    }
  }

  if (selected) selected.className = 'moduleRow';
  object.className = 'moduleRowSelected';
  selected = object;

// one button is not an array
  if (document.checkout_address.shipping[0]) {
    document.checkout_address.shipping[buttonSelect].checked=true;
    document.checkout_address.shipping[buttonSelect].onclick();
  } else {
    document.checkout_address.shipping.checked=true;
    document.checkout_address.shipping.onclick();
  }
}

function rowOverEffect(object) {
  if (object.className == 'moduleRow') object.className = 'moduleRowOver';
}

function rowOutEffect(object) {
  if (object.className == 'moduleRowOver') object.className = 'moduleRow';
}

var prev_ship_selected = '';
var prev_country_selected = '';
function shipping_changed() {
  var ship_selected = '';
  var ship = $("input[name=shipping]");
  for (i = 0; i < ship.length; i++) {
    if (ship[i].checked) {
      ship_selected = ship[i].value;
    }
  }

  var sendto_selected = '';
  var sendto = $("input[name=sendto]");
  for (i = 0; i < sendto.length; i++) {
    if (sendto[i].checked) {
      sendto_selected = sendto[i].value;
    }
  }
  if (sendto_selected == '') sendto_selected = $(sendto).val();

  var state = document.checkout_address.state.value;
  var postcode = document.checkout_address.postcode.value;
  var country_selected = document.checkout_address.country.value;

  if ( (ship_selected != '' && ship_selected != prev_ship_selected) || (country_selected != '' && country_selected != prev_country_selected) ) {
    var async = (country_selected != prev_country_selected ? false : true);
    prev_ship_selected = ship_selected;
    prev_country_selected = country_selected;

    $.ajax({
      async : async,
      timeout : 5000,
      type : "get",
      data : { ship: ship_selected, state: state, postcode: postcode, country: country_selected, sendto: sendto_selected },
      dataType : "json",
      url : "jcs_calc.php",
      success : function(res) {
        $("#checkout-order-totals").html(res.order_totals);
        $("#checkout-shipping-quotes").html(res.shipping_quotes);
        old_country = $("input[name=old_country]");
        old_country[0].value = res.country_selected;
      }
    });

  }

  return false;
}

//--></script>
