{use class="yii\helpers\Html"}
{use class="frontend\design\Info"}
{Info::addBoxToCss('autocomplete')}
<script>
    {if !Info::isTotallyAdmin()}
    tl(['{Info::themeFile('/js/jquery-ui.min.js')}', '{Info::themeFile('/js/yii-cookie/js.cookie.js')}', '{Info::themeFile('/js/postcode.decorator.js')}'], function () {
    {else}
        jQuery.getScript('{Info::themeFile('/js/postcode.decorator.js')}').then(function(){
    {/if}
            var decorator = new Decorator();
            {if $searchType == 'inline'}
            decorator.setInlineBuildType();
            {else}
            decorator.setPopupBuildType();
            decorator.setSource('Postcoder');
            decorator.setPrompt('{$smarty.const.STREET_ADDRESS|escape:html}');
            decorator.setImageName('{$smarty.const.IMAGE_BUTTON_SEARCH|escape:html}');
            decorator.buttonFire();
            {/if}
            decorator.setControlFields([ $('input[name="{Html::getInputName($model, 'postcode')}"]'), $('input[name="{Html::getInputName($model, 'street_address')}"]') ]);
            decorator.setControlFunction(function(target){
                $(target).autocomplete({
                    create: function( event, ui ) {
                        $(event.target).autocomplete( "option", "appendTo", $(event.target).closest('div') );
                    },
                    source: function (request, response) {
                        var status = $(target).autocomplete( "option", "manual");
                        if (!status){
                            var countryIso = 'GB';
                            var country = $('select[name="{Html::getInputName($model, 'country')}"]');
                            if (country.is('select')) {
                                var iso = country.data('iso');
                                try {
                                    if (Object.keys(iso).length) {
                                        if (iso.hasOwnProperty(country.val())) {
                                            countryIso = iso[country.val()];
                                        }
                                    }
                                } catch (error) {
                                    console.error('Invalid iso list');
                                }
                            }
                            $.getJSON("https://ws.postcoder.com/pcw/{$key}/address/" + countryIso + "/" + escape(request.term), {}, function (data) {
                                if (Array.isArray(data)) {
                                    values = [];
                                    $.each(data, function (i, item) {
                                        values.push({ 'label': item.summaryline, 'value': item });
                                    });
                                    return response(values);
                                }
                            });
                        }
                    },
                    minLength: 2,
                    autoFocus: true,
                    delay: 500,
                    select: function (event, ui) {
                        if (ui.hasOwnProperty('item')) {
        {if $model->has('POSTCODE')}
                            if (ui.item.value.hasOwnProperty('postcode')) {
                                $('input[name="{Html::getInputName($model, 'postcode')}"]').val(ui.item.value.postcode);
                            }
        {/if}
        {if $model->has('STATE')}
                            if (ui.item.value.hasOwnProperty('county')) {
                                $('input[name="{Html::getInputName($model, 'state')}"]').val(ui.item.value.county);
                            }
        {/if}
        {if $model->has('CITY')}
                            if (ui.item.value.hasOwnProperty('posttown')) {
                                $('input[name="{Html::getInputName($model, 'city')}"]').val(ui.item.value.posttown);
                            }
        {/if}
        {if $model->has('COMPANY')}
                            if (ui.item.value.hasOwnProperty('organisation')) {
                                $('input[name="{Html::getInputName($model, 'company')}"]').val(ui.item.value.organisation);
                            }
        {/if}
        {if $model->has('STREET_ADDRESS')}
                            var street = [];
            {if !$model->has('COMPANY')}
                            if (ui.item.value.hasOwnProperty('organisation')) {
                                street.push(ui.item.value.organisation);
                            }
            {/if}
                            if (ui.item.value.hasOwnProperty('premise')) {
                                street.push(ui.item.value.premise);
                            }
                            if (ui.item.value.hasOwnProperty('street')) {
                                street.push(ui.item.value.street);
                            }
                            if (ui.item.value.hasOwnProperty('number')) {
                                street.push(ui.item.value.number);
                            }
                            if (ui.item.value.hasOwnProperty('dependentlocality')) {
                                if ($('input[name="{Html::getInputName($model, 'suburb')}"]').is('input')) {
                                    $('input[name="{Html::getInputName($model, 'suburb')}"]').val(ui.item.value.dependentlocality);
                                } else {
                                    street.push(ui.item.value.dependentlocality);
                                }
                            }
                            $('input[name="{Html::getInputName($model, 'street_address')}"]').val(street.join(', '));
        {/if}

                            {$callback}
                            {if $maxAllowed}
                                var counter = parseInt(Cookies.getProtected('{$pc_cookie}'));
                                counter++;
                                Cookies.setProtected('{$pc_cookie}', parseInt(counter), cookieConfig||{});
                                if (counter >= {$maxAllowed}) {
                                    $('input[name="{Html::getInputName($model, 'postcode')}"], input[name="{Html::getInputName($model, 'street_address')}"]').autocomplete('destroy');
                                }
                            {/if}
                            decorator.done();
                        }
                        return false;
                    }
                });
                
                $(target).on('keyup keypress keydown', function(){
                    $(target).autocomplete( "option", "manual", true );
                    $(target).autocomplete( "close" );
                })
            
                $(target).autocomplete( "option", "manual", false );
                $(target).autocomplete( "search" );
            })
            
    {if !Info::isTotallyAdmin()}
        })
    {else}
    })
    {/if}
</script>