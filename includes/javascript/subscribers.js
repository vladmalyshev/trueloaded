<SCRIPT LANGUAGE="JavaScript">
<!--

function check_unsub_form()
{
  var f = document.subscribers_edit_unsub;
  if (f.unsub_email_address.value=='' || !isEmail(f.unsub_email_address.value))
  {
    alert('<?php echo JS_SPAMER_INVALID_EMAIL;?>');
    f.unsub_email_address.focus();
    return false;
  }
}

function check_form()
{
  var f = document.subscribers_edit;
  if (f.firstname.value==''){
    alert('<?php echo JS_SPAMER_INVALID_FIRSTNAME;?>');
    f.firstname.focus();
    return false;
  }
  if (f.lastname.value==''){
    alert('<?php echo JS_SPAMER_INVALID_LASTNAME;?>');
    f.lastname.focus();
    return false;
  }
  if (f.email_address.value=='' || !isEmail(f.email_address.value)){
    alert('<?php echo JS_SPAMER_INVALID_EMAIL;?>');
    f.email_address.focus();
    return false;
  }
  return true;
}

function isEmail(str)
{
    var supported = 0;
    if (window.RegExp) 
    {
      var tempStr = "a";
      var tempReg = new RegExp(tempStr);
      if (tempReg.test(tempStr)) supported = 1;
    }
    if(str.indexOf(" ") >= 0)
      return false;
    if (!supported) 
      return (str.indexOf(".") > 2) && (str.indexOf("@") > 0);
    var r1 = new RegExp("(@.*@)|(\\.\\.)|(@\\.)|(^\\.)");
    var r2 = new RegExp("^.+\\@(\\[?)[a-zA-Z0-9\\-\\.]+\\.([a-zA-Z]{2,5}|[0-9]{1,3})(\\]?)$");
    return (!r1.test(str) && r2.test(str));
} 
// -->
</script>