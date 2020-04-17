{use class = "yii\helpers\Html"}
  <h1>{$smarty.const.HEADING_TITLE}</h1>
    {\frontend\design\Info::addBoxToCss('info')}
    {\frontend\design\Info::addBoxToCss('form')}
    {Html::beginForm(['affiliate/password-forgotten', 'action'=>'process'], 'post', ['name' => 'password_forgotten', 'id' => 'frmPasswordForgotten'])}
  <div class="middle-form">
      {$messages_password_forgotten}
    <div style="margin-bottom: 20px">
      <p>{$smarty.const.TEXT_MAIN}</p>
    </div>
    <div class="col-full">
      <label for="email">{field_label const="ENTRY_EMAIL_ADDRESS" required_text=""}</label>
      <input type="email" name="email_address" value="{$email_address|escape:'html'}" id="email">
    </div>
    <div class="buttons">
      <div class="right-buttons"><button class="btn-1" type="submit">{$smarty.const.IMAGE_BUTTON_CONTINUE}</button></div>
      <div class="left-buttons"><a href="{$link_back_href}" class="btn">{$smarty.const.IMAGE_BUTTON_BACK}</a></div>
    </div>
  </div>
    {Html::endForm()}
