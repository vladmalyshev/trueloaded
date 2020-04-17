<div class="theme-list">
  {foreach $themes as $item}
    <div class="item">
      <div class="img">
        <img src="{DIR_WS_CATALOG}themes/basic/img/screenshot.png" alt="">
        <div><img src="{DIR_WS_CATALOG}themes/{$item.theme_name}/screenshot.png" alt=""></div>
      </div>
      <div class="title">{$item.title}</div>
      <div class="parent">
      {if $item.parent_theme_title}
      Parent theme: {$item.parent_theme_title}
      {/if}
      </div>
      <div class="description">{$item.description}</div>
      <div class="buttons">
        <!-- <a href="{Yii::$app->urlManager->createUrl(['design/theme', 'theme_name' => $item.theme_name])}" class="btn btn-primary">{$smarty.const.TEXT_CUSTOMIZE}</a> -->

        <a href="{Yii::$app->urlManager->createUrl(['design/backups', 'theme_name' => $item.theme_name])}" class="btn btn-primary" style="float: left">{$smarty.const.TEXT_BACKUPS}</a>
        <a href="{$item.link}" class="btn btn-primary">{$smarty.const.TEXT_CUSTOMIZE}</a>
      </div>
      {*<div class="buttons">
        <a class="confirm" style="font-size: 11px" data-theme_name="{$item.theme_name}">{$smarty.const.TEXT_RESTORE_DEFAULT_THEME}</a>
      </div>*}

      <span data-href="{Yii::$app->urlManager->createUrl(['design/theme-remove', 'theme_name' => $item.theme_name])}" class="remove"></span>
    </div>
  {/foreach}

</div>


<script type="text/javascript">
  (function(){
    $(function(){
      $('.confirm').on('click', function(){
        var _this = $(this);
        $.popUpConfirm('{$smarty.const.TEXT_CHANGES_WILL_BE_DESTROYED}', function(){
          $.get('{Yii::$app->urlManager->createUrl(['design/theme-restore'])}', { 'theme_name': _this.data('theme_name')}, function(){
            alertMessage('<div style="padding: 40px 20px; text-align: center">{$smarty.const.TEXT_THEME_RESTORED}</div>')
          })
        })
      });

      $('.theme-list .remove').on('click', function(){
        var _this = $(this);
        $.popUpConfirm('Do you really want to remove the theme: ' + _this.closest('.item').find('.title').text(), function(){
          document.location.href = _this.data('href')
        })
      });



      $('.create_item').popUp();

    })
  })(jQuery);
</script>