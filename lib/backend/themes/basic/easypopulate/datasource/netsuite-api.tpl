{*
This file is part of True Loaded.

@link http://www.holbi.co.uk
@copyright Copyright (c) 2005 Holbi Group LTD

For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
*}

{use class="yii\helpers\Html"}


<div class="widget box">
    <div class="widget-header">
        <h4>API Access details</h4>
        <div class="toolbar no-padding">
          <div class="btn-group">
            <span class="btn btn-xs widget-collapse"><i class="icon-angle-down"></i></span>
          </div>
        </div>
    </div>
    <div class="widget-content">
      <div class="w-line-row w-line-row-1">
          <div class="wl-td">
              <label>{$smarty.const.TEXT_INFO_APP_ID}</label> {Html::textInput('datasource['|cat:$code|cat:'][client][appid]', $client['appid'], ['class' => 'form-control'])}
          </div>
      </div>
      <div class="w-line-row w-line-row-1">
          <div class="wl-td">
              <label>{$smarty.const.TEXT_INFO_USERNAME}</label> {Html::textInput('datasource['|cat:$code|cat:'][client][username]', $client['username'], ['class' => 'form-control'])}
          </div>
      </div>
      <div class="w-line-row w-line-row-1">
          <div class="wl-td">
              <label>{$smarty.const.ENTRY_PASSWORD}</label> {Html::textInput('datasource['|cat:$code|cat:'][client][password]', $client['password'], ['class' => 'form-control'])}
          </div>
      </div>
      <div class="w-line-row w-line-row-1">
          <div class="wl-td">
              <label>{$smarty.const.TEXT_ACCOUNT}</label> {Html::textInput('datasource['|cat:$code|cat:'][client][account]', $client['account'], ['class' => 'form-control'])}
          </div>
      </div>
      <div class="w-line-row w-line-row-1">
          <div class="wl-td">
              <label></label><button type="button" class="btn" onclick="return ep_command('configure_datasource_settings:update')">Update access details</button>
          </div>
      </div>
    </div>
</div>
