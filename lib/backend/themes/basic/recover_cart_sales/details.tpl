{use class="\yii\helpers\Url"}
<table class="ord-desc-tab table-striped table-bordered table-responsive table-recovery-details">
    <thead>
    <tr>
        {foreach $app->controller->view->detailTable as $tableItem}
            <th{if $tableItem['not_important'] == 1} class="hidden-xs"{/if} {if $tableItem['width']} width="{$tableItem['width']}"{/if}>{$tableItem['title']}</th>
        {/foreach}
    </tr>
    </thead>
    {assign var='row' value="0"}
    {foreach $details['products'] as $detail}
      <tr>
        {if $row eq 0}
        <td rowspan="{$details['rows']}" align="left">{$details['batch']}</td>
        <td rowspan="{$details['rows']}" align="left">{$details['status']}</td>
        <td rowspan="{$details['rows']}" align="left">{$details['date']}</td>
        <td rowspan="{$details['rows']}" align="left">{$details['platform']}</td>
        {/if}
        <td align="left">{$detail['qty']}</td>
        <td align="left">{$detail['name']}</td>
        <td align="left">{$detail['model']}</td>
        <td align="left"><b>{$detail['price']}</b></td>
        <td align="left"><b>{$detail['stotal']}</b></td>
        {if $row eq 0}
        <td rowspan="{$details['rows']+1}" align="center" valign="middle">{if $details['cid']}<a href="javascript:void(0)" disabled><div class="btn-delete delete"></div></a>{/if}</td>
        {/if}
      </tr>
      {capture assign=var}{$row++}{/capture}
    {/foreach}
    <tr>
      <td colspan="8"></td>
      <td colspan="2"><b>{$smarty.const.TABLE_HEADING_TOTAL}: {$details['total']}</b></td>
    </tr>
</table>
<div class="btn-toolbar">
  <a href="javascript:void(0)" class="btn btn-default popup btn-note btn-right" disabled>{$smarty.const.TEXT_NOTE}</a>
  <a href="javascript:void(0)" class="btn btn-coup-cus btn-default popup btn-right" disabled>{$smarty.const.PSMSG}</a>
  <a href="javascript:void(0)" class="btn btn-coup-cus btn-default popup btn-right" disabled>{$smarty.const.TEXT_SEND_COUPON}</a>
  <a href="javascript:void(0)" class="btn btn-process-order-cus btn-default btn-right" disabled>{$smarty.const.TEXT_CONVERT_TO_ORDER}</a>
  <a href="javascript:void(0)" class="btn btn-process-order-cus btn-primary btn-right" disabled>{$smarty.const.TEXT_CREATE_NEW_OREDER}</a>

</div>
  