{use class ="yii\helpers\Html"}
{use class="frontend\design\Info"}
<link rel="stylesheet" type="text/css" href="//services.postcodeanywhere.co.uk/css/captureplus-2.30.min.css?key={$key}" />
<script type="text/javascript" src="//services.postcodeanywhere.co.uk/js/captureplus-2.30.min.js?key={$key}"></script>
<script type="text/javascript">
    {if !Info::isTotallyAdmin()}
    tl([ '{Info::themeFile('/js/yii-cookie/js.cookie.js')}', '{Info::themeFile('/js/postcode.decorator.js')}'], function () {
    {else}
    jQuery.getScript('{Info::themeFile('/js/postcode.decorator.js')}');
    {/if}
        
        setTimeout(function(){ 
            if (typeof (pca) != "undefined") {
                var decorator = new Decorator();
                {if $searchType == 'inline'}
                decorator.setInlineBuildType();
                {else}
                decorator.setPopupBuildType();
                decorator.setSource('PostCodeAnywhere');
                decorator.setPrompt('{$smarty.const.CATEGORY_ADDRESS|escape:html}');
                {/if}
                var capturePlusFields = [];
                decorator.setControlFields([ $('input[name="{Html::getInputName($model, 'postcode')}"]'), $('input[name="{Html::getInputName($model, 'street_address')}"]') ], function(field){
                    {if $searchType == 'popup'}
                    capturePlusFields.push({
                        element: field,
                        field: 'PostalCode',
                        mode: pca.fieldMode.DEFAULT
                    });
                    {/if}
                });
            {if $model->has('STREET_ADDRESS')}
                    capturePlusFields.push({
                        element: '{Html::getInputName($model, 'street_address')}',
                        field: {literal}'{{Company}, }{{BuildingName}, }{{SubBuilding} }{{BuildingNumber} }{Street}'{/literal},
                        mode: {if $searchType == 'inline'}pca.fieldMode.DEFAULT{else}pca.fieldMode.POPULATE{/if}
                    });
            {/if}
            {if $model->has('SUBURB')}
                    capturePlusFields.push({
                        element: '{Html::getInputName($model, 'suburb')}',
                        field: 'District',
                        mode: pca.fieldMode.POPULATE
                    });
            {/if}
            {if $model->has('POSTCODE')}
                    capturePlusFields.push({
                        element: '{Html::getInputName($model, 'postcode')}',
                        field: 'PostalCode',
                        mode: {if $searchType == 'inline'}pca.fieldMode.DEFAULT{else}pca.fieldMode.POPULATE{/if}
                    });
            {/if}
            {if $model->has('CITY')}
                    capturePlusFields.push({
                        element: '{Html::getInputName($model, 'city')}',
                        field: 'City',
                        mode: pca.fieldMode.POPULATE
                    });
            {/if}
            {if $model->has('STATE')}
                    capturePlusFields.push({
                        element: '{Html::getInputName($model, 'state')}',
                        field: 'ProvinceName',
                        mode: pca.fieldMode.POPULATE
                    });
            {/if}
                decorator.setControlFunction(function(target){
                    var capturePlusOptions = { key: '{$key}', countries: { defaultCode: 'GBR', codesList: 'GBR', fillOthers: false }};
                    var control = new pca.Address(capturePlusFields, capturePlusOptions);
                    control.listen('populate', function () {
                        {$callback}
                        {if $maxAllowed}
                            var counter = parseInt(Cookies.getProtected('{$pc_cookie}'));
                            counter++;
                            Cookies.setProtected('{$pc_cookie}', parseInt(counter), cookieConfig||{});
                            if (counter >= {$maxAllowed}) {
                                control.destroy();
                            }
                        {/if}
                        decorator.done();
                    });
                })
                
            }
        }, 1000);
        
    {if !Info::isTotallyAdmin()}
    });
    {/if}
</script>