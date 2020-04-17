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
                    <label>Department API WSDL URL:</label> {Html::textInput('datasource['|cat:$code|cat:'][client][wsdl_location]', $client['wsdl_location'], ['class' => 'form-control'])}
                </div>
            </div>
            <div class="w-line-row w-line-row-1">
                <div class="wl-td">
                    <label>Department API KEY:</label> {Html::textInput('datasource['|cat:$code|cat:'][client][department_api_key]', $client['department_api_key'], ['class' => 'form-control'])}
                </div>
            </div>
            <div class="w-line-row w-line-row-1">
                <div class="wl-td">
                    <label></label><button type="button" class="btn" onclick="return ep_command('configure_datasource_settings:update')">Update access details</button>
                </div>
            </div>
        </div>
    </div>
