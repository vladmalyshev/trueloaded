  <div class="widget {if !$inrec['expanded'] }widget-closed{/if} widget-header filter-title table-bordered">
    <div class="contact-line contactcid-{$inrec['cid']}">
      <div class="contact-status {$class_contacted}">
            {tep_draw_hidden_field('custid[]', $inrec['cid'], 'class="contact-him"')}&nbsp;{$sentInfo}
      </div>
      <div class="ord-name ord-gender ord-gender-{$inrec['customers_gender']}">
        <a href="javascript:void(0)"> {$inrec['fname']} {$inrec['lname']}</a>
      </div>
      <div class="ord-name cust-email cust-style"><a class="mailto" href="javascript:void(0)" data-cid="{$inrec['cid']}" data-bid="{$inrec['basket_id']}">{$inrec['changed_email']}</a></div>
      <div class="cr-ord-cust-phone cust-style">{$inrec['phone']}</div>
      <div class="icon-phone cust-style">{$inrec['fax']}</div>
      <div class="toolbar no-padding">
        <div class="btn-group">
          <span class="btn btn-xs widget-collapse"><i class="icon-angle-{if !$inrec['expanded'] }up{else}down{/if}"></i></span>
        </div>
      </div>
    </div>
    <div class="widget-content after info-details" {if !$inrec['expanded'] } style="display:none;"{/if}>
        {Yii::$app->controller->renderDetailsAjax($details)}
    </div>
  </div>
