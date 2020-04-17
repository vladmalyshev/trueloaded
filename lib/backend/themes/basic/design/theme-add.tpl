<form action="" id="add-theme">
<div class="popup-heading">{$smarty.const.TEXT_ADD_THEME}</div>
<div class="popup-content pop-mess-cont popup-new-theme">

  <div class="setting-row" style="display: none">
    <label for="">Theme name</label>
    <input type="text" name="theme_name" value="" class="form-control theme_name" style="width: 243px" placeholder="only lowercase letters and numbers" />

  </div>

  <div class="setting-row">
    <label for="">{$smarty.const.TEXT_THEME_TITLE}</label>
    <input type="text" name="title" value="" class="form-control" style="width: 243px" required/>
  </div>

  <div class="setting-row">
    <label>Theme Source</label>
    <div class="theme-source" style="display: inline-block">
      <label><input type="radio" name="theme_source" value="empty" checked/> Empty Theme</label>
      <label><input type="radio" name="theme_source" value="theme"/> {$smarty.const.COPY_FROM_THEME}</label>
      <label><input type="radio" name="theme_source" value="computer"/> Upload from computer</label>
      <label><input type="radio" name="theme_source" value="url"/> Upload from URL</label>
      <div class="theme-source-content empty"></div>
      <div class="theme-source-content theme" style="display: none">
        <select name="parent_theme" id="" class="form-control">
          {foreach $themes as $theme}
            <option value="{$theme.theme_name}">{$theme.title}</option>
          {/foreach}
        </select>
        <label><input type="radio" name="parent_theme_files" value="link" checked/> Link theme files to parent theme</label>
        <label><input type="radio" name="parent_theme_files" value="copy"/> Copy theme files</label>
      </div>
      <div class="theme-source-content computer" style="display: none">
        <div class="upload" data-name="theme_source_computer"></div>
      </div>
      <div class="theme-source-content url" style="display: none">
        <input type="url" name="theme_source_url" class="form-control" style="width: 243px">
      </div>
    </div>
  </div>

  <div class="setting-row">
    <label for=""> Landing page</label>
    <input type="checkbox" name="landing" class="uniform" style="position: relative; top: 3px"/>
  </div>

</div>
<div class="noti-btn">
  <div><span class="btn btn-cancel">{$smarty.const.IMAGE_CANCEL}</span></div>
  <div><button type="submit" class="btn btn-primary btn-save">{$smarty.const.TEXT_BTN_OK}</button></div>
</div>
</form>
<script type="text/javascript" src="{$app->request->baseUrl}/plugins/html2canvas.js"></script>
<script type="text/javascript">
  (function($){
    $(function(){
      $('.upload[data-name="theme_source_computer"]').uploads();

      $('input[name="theme_source"]').on('change', function(){
        $('.theme-source-content').hide();
        $('.'+$(this).val()).show()
      });

      $('#add-theme').on('submit', function(){
        $('.popup-box').append('<div class="popup-preloader preloader"></div>');
        var theme_name = $('.theme_name').val();
        $.get('{$action}', $('#add-theme').serializeArray(), function(d){
          $('.pop-mess-cont .error').remove();
          if (d.code == 1){
            $('.pop-mess-cont').prepend('<div class="error">'+d.text+'</div>');
            $('.popup-box .popup-preloader').remove();
          }
          if (d.code == 2){
            $('.pop-mess-cont').prepend('<div class="info">'+d.text+'</div>');

            $('body').append('<iframe src="{$app->request->baseUrl}/../?theme_name='+theme_name+'" width="100%" height="0" frameborder="no" id="home-page"></iframe>');
            var home_page = $('#home-page');
            home_page.on('load', function(){
              setTimeout(function(){
                home_page.contents().find('body').append('<div>&nbsp;</div>');
                html2canvas(home_page.contents().find('body').get(0), {
                  background: '#ffffff',
                  onrendered: function(canvas) {
                    $.post('upload/screenshot', { theme_name: theme_name, image: canvas.toDataURL('image/png')}, function(){
                      location.reload();
                    });
                    home_page.remove()
                  }
                })
              }, 500)
            });
          }
        }, 'json');

        return false
      })
    })
  })(jQuery)
</script>