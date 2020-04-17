<div class="subscribe_box">
  <div class="left_box">
    <div class="stitle">{$smarty.const.NEWSLETTER_BOX1}</div>
    <h3>{$smarty.const.NEWSLETTER_BOX2}</h3>
    <p>{$smarty.const.NEWSLETTER_BOX3}</p>
  </div>
  <div class="right_box">
    <form action="{Yii::$app->urlManager->createAbsoluteUrl('subscribers')}" onsubmit="return check_email();" method="get">
      <div class="sb_ta">
        <div class="sb_tc">
          <input type="email" name="subscribers_email_address" id="subscribers_email_address" required data-pattern="email">
        </div>
        <div class="sb_tc">
          <input type="submit" value="Submit">
        </div>
      </div>
    </form>
  </div>
</div>

<script type="text/javascript">
function check_email() {
    var subscribers_email_address = $("#subscribers_email_address").val();
    if (!isValidEmailAddress(subscribers_email_address)) {
      alert('{$smarty.const.EMAIL_REQUIRED}');
      return false;
    }
    return true;
  }
{literal}
  function isValidEmailAddress(emailAddress) {
    var pattern = new RegExp(/^(("[\w-\s]+")|([\w-]+(?:\.[\w-]+)*)|("[\w-\s]+")([\w-]+(?:\.[\w-]+)*))(@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$)|(@\[?((25[0-5]\.|2[0-4][0-9]\.|1[0-9]{2}\.|[0-9]{1,2}\.))((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\.) {2}(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\]?$)/i);
    return pattern.test(emailAddress);
  }
{/literal}
</script>