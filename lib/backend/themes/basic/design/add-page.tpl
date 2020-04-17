<form action="" id="add-page">
  <input type="hidden" name="theme_name" value="{$theme_name}"/>
  <div class="popup-heading">{$smarty.const.TEXT_ADD_PAGE}</div>
  <div class="popup-content pop-mess-cont">

    <div class="setting-row">
      <label for="">{$smarty.const.TEXT_PAGE_NAME}</label>
      <input type="text" name="page_name" value="" class="form-control" style="width: 243px" required="">
    </div>

    {if $page_type}
      <input type="hidden" name="page_type" value="{$page_type}"/>
      <div class="setting-row">
        <label for="">{$smarty.const.TEXT_PAGE_TYPE}</label>
          <span style="padding: 5px 0 0 0; font-weight: bold; display: inline-block">{$types[$page_type]}</span>
      </div>
    {elseif $types|count > 1}
      <div class="setting-row">
        <label for="">{$smarty.const.TEXT_PAGE_TYPE}</label>
        <select name="page_type" id="" class="form-control" required="">
          {foreach $types as $page_type => $title}
          <option value="{$page_type}">{$title}</option>
              {if $page_type == 'invoice' || $page_type == 'packingslip' || $page_type == 'label'}
                  {$pdfPageSettings = true}
              {/if}
          {/foreach}
        </select>
      </div>
    {/if}

    {if $pdfPageSettings}{include 'settings/pdf-page-settings.tpl'}{/if}

  </div>
  <div class="noti-btn">
    <div><span class="btn btn-cancel">{$smarty.const.IMAGE_CANCEL}</span></div>
    <div><button type="submit" class="btn btn-primary btn-save">{$smarty.const.TEXT_BTN_OK}</button></div>
  </div>
</form>
<script type="text/javascript">
  (function($){
    $(function(){
      $('#add-page').on('submit', function(){
        $.get('{$action}', $('#add-page').serializeArray(), function(d){
          $('.pop-mess-cont .error').remove();
          if (d.code == 1){
            $('.pop-mess-cont').prepend('<div class="error">'+d.text+'</div>')
          }
          if (d.code == 2){
            $('.pop-mess-cont').prepend('<div class="info">'+d.text+'</div>');
            setTimeout(function(){
              location.reload();
            }, 1000)
          }
        }, 'json');

        return false
      })
    })
  })(jQuery)
</script>