{use class="Yii"}
<form action="{$app->request->baseUrl}/design/box-save" method="post" id="box-save">
  <input type="hidden" name="id" value="{$id}"/>
  <div class="popup-heading">
    Chat
  </div>
    <div class="popup-content box-img">
        <div class="tabbable tabbable-custom">
            <div class="nav nav-tabs">
                <div class="active"><a href="#type" data-toggle="tab">Chat</a></div>
                <div><a href="#style" data-toggle="tab">{$smarty.const.HEADING_STYLE}</a></div>
                <div><a href="#align" data-toggle="tab">{$smarty.const.HEADING_WIDGET_ALIGN}</a></div>
                <div><a href="#visibility" data-toggle="tab">{$smarty.const.TEXT_VISIBILITY_ON_PAGES}</a></div>
            </div>
            <div class="tab-content">
                <div class="tab-pane active menu-list" id="type">
                    Tawk.to script: <br><textarea name="setting[0][chat]" class="form-control" style="width: 100%;">{htmlspecialchars($settings[0].chat)}</textarea><br>
                    {include 'include/ajax.tpl'}
                </div>
                <div class="tab-pane" id="style">
                    {include 'include/style.tpl'}
                </div>
                <div class="tab-pane" id="align">
                    {include 'include/align.tpl'}
                </div>
                <div class="tab-pane" id="visibility">
                    {include 'include/visibility.tpl'}
                </div>
            </div>
        </div>
    </div>
  <div class="popup-buttons">
    <button type="submit" class="btn btn-primary btn-save">{$smarty.const.IMAGE_SAVE}</button>
    <span class="btn btn-cancel">{$smarty.const.IMAGE_CANCEL}</span>
    <script type="text/javascript">
      $('.btn-cancel').on('click', function(){
        $('.popup-box-wrap').remove()
      })
    </script>

  </div>
</form>