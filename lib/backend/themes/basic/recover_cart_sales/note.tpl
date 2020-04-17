<div class="popup-heading">{$smarty.const.ENTRY_NOTIFY_COMMENTS}</div>
<div class="popup-content pop-mess-cont">
  <textarea name="comments" id="" rows="10" style="width: 100%">{$note}</textarea>
</div>
<div class="note-block noti-btn">
  <div><span class="btn btn-cancel">{$smarty.const.IMAGE_CANCEL}</span></div>
  <div><span class="btn btn-primary btn-save">{$smarty.const.TEXT_BTN_OK}</span></div>
</div>

<script>
 $(document).ready(function(){
    document.querySelector('textarea[name=comments]').setSelectionRange(document.querySelector('textarea[name=comments]').value.length,document.querySelector('textarea[name=comments]').value.length);
    $('textarea[name=comments]').focus();
    $('.btn-save').click(function(){
      $.post('recover_cart_sales/noteedit', {
        'comments' : encodeURIComponent($('textarea[name=comments]').val()),
        'cid' : {$cid} 
      },
      function(data, status){
          $('.note-block .btn-cancel').click();
          return;
      }, 'html');    
    });
 })
</script>