{use class="\yii\helpers\Html"}
<h1>Your Summary</h1>
<br><br>
<h2>{$smarty.const.TEXT_GREETING} {$affiliate->affiliate_firstname} {$affiliate->affiliate_lastname}</h2>
<h2>{$smarty.const.TEXT_AFFILIATE_ID}: {$affiliate->affiliate_id}</h2>
<h2>{$smarty.const.TEXT_AFFILIATE_URL}: {$platformUrl}</h2>
<br>
<h3>{$smarty.const.TEXT_SUMMARY_TITLE_TODATE}</h3>
<br>
<table width="100%" border="0" cellpadding="4" cellspacing="2">
<tr>
  <td width="35%" align="right" class="boxtext">{$smarty.const.TEXT_IMPRESSIONS}:</td>
  <td width="15%" class="boxtext">{$affiliate_impressions}</td>
  <td width="35%" align="right" class="boxtext">{$smarty.const.TEXT_VISITS}:</td>
  <td width="15%" class="boxtext">{$affiliate_clickthroughs}</td>
</tr>
<tr>
   <td align="right" class="boxtext">{$smarty.const.TEXT_CLICKTHROUGH_RATE}:</td>
   <td class="boxtext">{$currencies->display_price(AFFILIATE_PAY_PER_CLICK, '')}</td>
   <td align="right" class="boxtext">{$smarty.const.TEXT_PAYPERSALE_RATE}:</td>
   <td class="boxtext">{$currencies->display_price(AFFILIATE_PAYMENT, '')}</td>
</tr>
<tr>
  <td width="35%" align="right" class="boxtext">{$smarty.const.TEXT_TRANSACTIONS}:</td>
  <td width="15%" class="boxtext">{$affiliate_transactions}</td>
  <td width="35%" align="right" class="boxtext">{$smarty.const.TEXT_CONVERSION}:</td>
  <td width="15%" class="boxtext">{$affiliate_conversions}</td>
</tr>
<tr>
  <td width="35%" align="right" class="boxtext">{$smarty.const.TEXT_AMOUNT}:</td>
  <td width="15%" class="boxtext">{$currencies->display_price($affiliate_amount, '')}</td>
  <td width="35%" align="right" class="boxtext">{$smarty.const.TEXT_AVERAGE}:</td>
  <td width="15%" class="boxtext">{$currencies->display_price($affiliate_average, '')}</td>
</tr>
<tr>
  <td width="35%" align="right" class="boxtext">{$smarty.const.TEXT_COMMISSION_RATE}:</td>
  <td width="15%" class="boxtext">{round($affiliate_percent, 2)}%</td>
  <td width="35%" align="right" class="boxtext">{$smarty.const.TEXT_COMMISSION}:</td>
  <td width="15%" class="boxtext">{$currencies->display_price($affiliate_commission, '')}</td>
</tr>
</table>
<h3>{$smarty.const.TEXT_SUMMARY_TITLE_PENDING}</h3>
<table width="100%" border="0" cellpadding="4" cellspacing="2">
<tr>
  <td width="35%" align="right" class="boxtext">{$smarty.const.TEXT_TRANSACTIONS}:</td>
  <td width="15%" class="boxtext">{$affiliate_pending_transactions}</td>
  <td width="35%" align="right" class="boxtext">{$smarty.const.TEXT_AMOUNT}:</td>
  <td width="15%" class="boxtext">{$currencies->display_price($affiliate_pending_amount, '')}</td>
</tr>
<tr>
  <td width="35%" align="right" class="boxtext">{$smarty.const.TEXT_COMMISSION}:</td>
  <td width="15%" class="boxtext">{$currencies->display_price($affiliate_pending_commission, '')}</td>
  <td width="35%" align="right" class="boxtext">{$smarty.const.TEXT_AVERAGE}:</td>
  <td width="15%" class="boxtext">{$currencies->display_price($affiliate_pending_average, '')}</td>
</tr>
</table>


<div class="center-buttons">
<a class="btn-1" href="{Yii::$app->urlManager->createUrl('affiliate/logoff')}">Logoff</a>
</div>