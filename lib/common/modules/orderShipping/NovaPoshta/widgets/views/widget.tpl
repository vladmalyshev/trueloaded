{use class = "yii\helpers\Html"}
{assign var=re1 value='.{'}
{assign var=re2 value='}'}
<style>
    .ui-autocomplete { max-height: 200px; overflow-y: scroll; overflow-x: hidden;}
</style>
<div id="np_shipping_widget" {*if \frontend\design\Info::isTotallyAdmin() && $select_shipping != 'np_warehouse'}style="display: none;"{/if*}>
    <div>
        <div class="row">
            <select name="shippingparam[np][area]" class="select2 select2-offscreen" id="np_area" disabled="disabled" style="width: 100%;" data-pattern="{$re1}3{$re2}" data-required="{$smarty.const.SHIP_STATE_ERROR_SELECT}">
                <option value="-1">{$smarty.const.ENTRY_STATE_TEXT}</option>
                {foreach $areas as $area}
                    <option value="{$area->Ref}" {if $area->Ref == $selectedArea || $area->Description == $selectedArea}selected{/if}>{$area->Description}</option>
                {/foreach}
                {Html::hiddenInput('shippingparam[np][areaText]', '', ['id'=>'np_areaText'])}
            </select>
        </div>
        <div class="row">
            <select name="shippingparam[np][city]" class="select2 select2-offscreen" id="np_city"  disabled="disabled" style="width: 100%;" data-pattern="{$re1}3{$re2}" data-required="{$smarty.const.ENTRY_TOWN_TEXT}">
                <option value="-1">{$smarty.const.ENTRY_TOWN_TEXT}</option>
            </select>
            {Html::hiddenInput('shippingparam[np][cityText]', '', ['id'=>'np_cityText'])}
        </div>
        <div class="row">
            {if \frontend\design\Info::isTotallyAdmin()}<div class="col-md-12"><span class="label_name">{$smarty.const.ENTRY_DEPARTMENT_TEXT}</span></div>{/if}
            {Html::textInput('shippingparamTemp[np][warehouse]', $selectedWarehouseText, ['id'=>'np_warehouseNam', 'disabled' => 'disabled', 'data-pattern' => $re1|cat:'3'|cat:$re2, 'data-required' => $smarty.const.ENTRY_DEPARTMENT_TEXT])}
            {Html::hiddenInput('shippingparam[np][warehouse]', $selectedWarehouse, ['id'=>'np_warehouseRef'])}
            {Html::hiddenInput('shippingparam[np][warehouseText]', $selectedWarehouseText, ['id'=>'np_warehouseText'])}
        </div>
    </div>
    <div class="row">
        <div class="col-md-12"><span class="label_name">{$smarty.const.TEXT_GIFT_CARD_RECIPIENTS_EMAIL}</span></div>
    </div>
    <div class="row">
        <label>
            <span>{field_label const="ENTRY_FIRST_NAME" configuration="ACCOUNT_FIRSTNAME"}</span>
            {Html::textInput('shippingparam[np][firstname]', $selectedFirstName, ['id'=>'np_firstname', 'data-required' => "{sprintf($smarty.const.ENTRY_FIRST_NAME_ERROR, $smarty.const.ENTRY_FIRST_NAME_MIN_LENGTH)}", 'data-pattern' => "{$re1}{$smarty.const.ENTRY_FIRST_NAME_MIN_LENGTH}{$re2}"])}
        </label>
    </div>
    <div class="row">
        <label>
            <span>{field_label const="ENTRY_LAST_NAME" configuration="ACCOUNT_LASTNAME"}</span>
            {Html::textInput('shippingparam[np][lastname]', $selectedLastName, ['id'=>'np_lastname', 'data-required' => "{sprintf($smarty.const.ENTRY_LAST_NAME_ERROR, $smarty.const.ENTRY_LAST_NAME_MIN_LENGTH)}", 'data-pattern' => "{$re1}{$smarty.const.ENTRY_LAST_NAME_MIN_LENGTH}{$re2}"])}
        </label>
    </div>
    <div class="row">
        <label>
            <span>{field_label const="ENTRY_TELEPHONE_NUMBER" configuration="ACCOUNT_TELEPHONE"}</span>
            {Html::textInput('shippingparam[np][telephone]', $selectedTelephone, ['id'=>'np_telephone', 'data-required' => "{sprintf($smarty.const.ENTRY_TELEPHONE_NUMBER_ERROR, $smarty.const.ENTRY_TELEPHONE_MIN_LENGTH)}", 'data-pattern' => "{$re1}{$smarty.const.ENTRY_TELEPHONE_MIN_LENGTH}{$re2}"])}
        </label>
    </div>
    </div>
<script>
    var selectedCity = "{$selectedCity}";
    var selectedArea = "{$selectedArea}";
    var selectedWarehouse = "{$selectedWarehouse}";
    var warehouses = [];
    var cities = [];
    var areas = [];
    {if !\frontend\design\Info::isTotallyAdmin()}
    tl(['{\frontend\design\Info::themeFile('/js/jquery-ui.min.js')}'], function () {
    {/if}
        $(document).ready(function () {
            $('#np_area').on('change', function (e) {
                emptySelect.call(this);
                getCitiesByArea($(this).val());
                // np_fillAddressFields();
                setNpSessionShippingParams();
            });

            $('#np_city').on('change', function (e) {
                emptySelect.call(this);
                getWarehouse($(this).val());
                // np_fillAddressFields();
                setNpSessionShippingParams();

            });

            $('#np_warehouse').on('change', function (e) {
                setNpSessionShippingParams();
                // np_fillAddressFields();
            });

            $('#np_firstname').on('change', function (e) {
                setNpSessionShippingParams();
                // np_fillAddressFields();
            });
            $('#np_lastname').on('change', function (e) {
                setNpSessionShippingParams();
                // np_fillAddressFields();
            });
            $('#np_telephone').on('change', function (e) {
                setNpSessionShippingParams();
                // np_fillAddressFields();
            });
            getAreas();
            autoCompleteWarehouses();
        });
    {if !\frontend\design\Info::isTotallyAdmin()}
    });
    {/if}

    function getSelectedAreaRef(val) {
        var ref = '';
        $.each(areas, function (index, item) {
            if (item.Description == val || item.Ref == val) {
                ref = item.Ref;
            }
        });
        return ref;
    }

    function getCitiesByArea(area) {
        $.each(cities, function (index, item) {
            if (item.Area === area) {
                var selected = '';
                if(selectedCity && (selectedCity == item.Ref || selectedCity == item.Description)){
                    selected = 'selected';
                    selectedCity = item.Ref;
                }
                $('#np_city').append('<option value="' + item.Ref + '" '+ selected +'>' + item.Description + '</option>');
            }
        });
        $('#np_city').prop('disabled', false);
        setNpSessionShippingParams();
    }

    function getAreas(){
        $.get("{\Yii::$app->urlManager->createUrl(['nova-poshta/get-areas'])}", {

        }, function (response) {
            areas = [];
            if (response.success && response.data.length > 0) {
                $.each(response.data, function (index, item) {
                    var selected = '';
                    if(selectedArea){
                        if(item.Ref == selectedArea || item.Description == selectedArea){
                            selected = 'selected';
                        }
                    }
                    $('#np_area').append('<option value="' + item.Ref + '"' + selected  +'>' + item.Description + '</option>');
                    areas.push(item);
                });
                //selectedArea = getSelectedAreaRef(selectedArea);
                $('#np_areaText').val($('#np_area').find('option:selected').text());
                getCities();
                //setSessionShippingParams();
            }

        });
    }

    function getCities(areaRef = '') {
        if (areaRef === '-1') {
            return false;
        }
        $.get('{\Yii::$app->urlManager->createUrl(['nova-poshta/get-cities'])}', {

            }, function (response) {
            cities = [];
            if (response.success && response.data.length > 0) {
                $.each(response.data, function (index, item) {
                    if (item.Area === selectedArea) {
                        var selected = '';
                        if(selectedCity && (selectedCity === item.Ref || selectedCity === item.Description)){
                            selected = 'selected';
                            selectedCity = item.Ref;
                        }
                        $('#np_city').append('<option value="' + item.Ref + '" '+ selected +'>' + item.Description + '</option>');
                    }
                    cities.push(item);
                });
                //getCitiesByArea(selectedArea);
                $('#np_cityText').val($('#np_city').find('option:selected').text());
                if (selectedCity) {
                    $('#np_city').prop('disabled', false);
                    getWarehouse(selectedCity);
                }
                $('#np_area').prop('disabled', false);
            }
        });
    }

    function getWarehouse(city) {
        if (city === '-1') {
            return false;
        }
        $.get('{\Yii::$app->urlManager->createUrl(['nova-poshta/get-warehouses'])}', {
            cityRef: city,
        }, function (response) {
            warehouses = [];
            if (response.success && response.data.length > 0) {
                $.each(response.data, function (index, item) {

                    var selected = '';
                    if(selectedWarehouse && (selectedWarehouse === item.Ref || selectedWarehouse === item.Description)){
                        selected = 'selected';
                        selectedWarehouse = item.Ref;
                    }
                    $('#np_warehouseNam').append('<option value="' + item.Ref + '" '+ selected +'>' + item.Description + '</option>');
                    warehouses.push(item)
                })
            }
            autoCompleteWarehouses();
            $('#np_warehouseNam').prop('disabled', false);
        });

    }

    function setNpSessionShippingParams() {
        $('#np_areaText').val($('#np_area').find('option:selected').text());
        $('#np_cityText').val($('#np_city').find('option:selected').text());
        {if \frontend\design\Info::isTotallyAdmin()}
            return false;
        {else}
        var url = "{$url}";
        var post_data = {
            np: {
                area: $('#np_area').val(),
                areaText: $('#np_areaText').val(),
                city: $('#np_city').val(),
                cityText: $('#np_cityText').val(),
                warehouse: $('#np_warehouseRef').val(),
                warehouseText: $('#np_warehouseText').val(),
                firstname: $('#np_firstname').val(),
                lastname: $('#np_lastname').val(),
                telephone: $('#np_telephone').val(),
            }
        };
        $.post(
            url,
            post_data,
            function (data){
                // console.log(data);
            }
        );
        {/if}
    }

    var emptySelect = function () {
        var next = $(this).closest('div').nextAll();
        $.each(next, function (index, item) {
            $(item).find('select').prop('disabled', true).find('option:not(:first)').remove();
            $(item).find('input').val('');
            $(item).find(':input:not([type="hidden"])').prop('disabled', true);
        })
    }

    function autoCompleteWarehouses(){
        $( "#np_warehouseNam" ).autocomplete({
            source: function (req, res) {
                var words = req.term.split(' ');
                var results = $.grep(warehouses, function(item, index) {
                    var sentence = item.Description.toLowerCase();
                    return words.every(function(word) {
                        return sentence.indexOf(word.toLowerCase()) >= 0;
                    });
                });
                res ($.map(results, function (item) {
                    return {
                        label: item.Description,
                        value: item.Ref
                    }
                }));
            },
            appendTo: '#shipping_method',
            autoFocus: true,
            delay: 0,
            minLength: 0,
            select: function( event, ui ) {
                $('#np_warehouseNam').val(ui.item.label).trigger('change');
                $('#np_warehouseText').val(ui.item.label);
                $('#np_warehouseRef').val( ui.item.value );
                setTimeout(function(){
                    // np_fillAddressFields();
                    setNpSessionShippingParams();
                }, 200)
                return false;
            },

        }).focus(function () {
            $(this).autocomplete("search",$(this).val());
        });
    }
</script>
