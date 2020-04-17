{use class="yii\helpers\Html"}
<style>
    .np_warehouseForm .Sender, .np_warehouseForm .Recipient {
        /*display: none;/**/
    }
    #backwardCostWrap {
        display: none;
    }
    #errorNPWrap {
        color: red;
    }
</style>

<div class="np_warehouseForm" id="np_warehouseForm">
    <div class="errors" id="errorNPWrap"></div>
    {Html::activeHiddenInput($form, 'deliveryDate', [])}
    {Html::activeHiddenInput($form, 'senderContactRef', [])}
    {Html::activeHiddenInput($form, 'ordersLabelId', [])}
    {Html::activeHiddenInput($form, 'orderId', [])}
    <div class="toogle">
        <label>
            {$smarty.const.TEXT_TYPE_DELIVERY}
            {Html::activeDropDownList($form, 'type', \common\modules\label\np::TYPE_DELIVERIES, ['class' => 'form-control', 'id' => 'np_type'])}
        </label>
    </div>
    <div class="deliveryWrap">
        <div class="Sender deliveryBlock">
            <h3>{$smarty.const.TEXT_SENDER}</h3>
            {Html::activeHiddenInput($form, 'senderRef', [])}
            <div class="form-group">
                <label>
                    {$form->getLabelByField('areaSender')}
                    {Html::activeDropDownList($form, 'areaSender', $form->getAreasArray(), ['class' => 'form-control', 'id' => 'np_areaSender'])}
                </label>
            </div>
            <div class="form-group">
                <label>
                    {$form->getLabelByField('citySender')}
                    {Html::activeDropDownList($form, 'citySender', $form->getCitiesSenderArray(), ['class' => 'form-control', 'id' => 'np_citySender'])}
                </label>
            </div>
            <div class="form-group">
                <label>
                    {$form->getLabelByField('warehouseSender')}
                    {Html::activeDropDownList($form, 'warehouseSender', $form->getWarehousesSenderArray(), ['class' => 'form-control', 'id' => 'np_warehouseSender'])}
                </label>
            </div>
            <div class="form-group">
                <label>
                    {$form->getLabelByField('telephone')}
                    {Html::activeTextInput($form, 'telephoneSender', ['class' => 'form-control', 'id' => 'np_telephone_sender'])}
                </label>
            </div>
        </div>
        <div class="Recipient deliveryBlock">
            <h3>{$smarty.const.TEXT_RECIPIENT}</h3>
            <div class="{\common\modules\label\np::TYPE_DELIVERY_WAREHOUSE_DOORS} np_delivery">
                <div class="form-group">
                    <label>
                        {$form->getLabelByField('areaRecipient')}
                        {Html::activeTextInput($form, 'areaNameRecipient', ['class' => 'form-control', 'id' => 'np_areaNameRecipient'])}
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        {$form->getLabelByField('cityRecipient')}
                        {Html::activeTextInput($form, 'cityNameRecipient', ['class' => 'form-control', 'id' => 'np_cityNameRecipient'])}
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        {$form->getLabelByField('addressNameRecipient')}
                        {Html::activeTextInput($form, 'addressNameRecipient', ['class' => 'form-control', 'id' => 'np_addressNameRecipient'])}
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        {$form->getLabelByField('houseRecipient')}
                        {Html::activeTextInput($form, 'houseRecipient', ['class' => 'form-control', 'id' => 'np_houseRecipient'])}
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        {$form->getLabelByField('flatRecipient')}
                        {Html::activeTextInput($form, 'flatRecipient', ['class' => 'form-control', 'id' => 'np_flatRecipient'])}
                    </label>
                </div>
            </div>
            <div class="{\common\modules\label\np::TYPE_DELIVERY_WAREHOUSE_WAREHOUSE} np_delivery">
                <div class="form-group">
                    <label>
                        {$form->getLabelByField('areaRecipient')}
                        {Html::activeDropDownList($form, 'areaRecipient', $form->getAreasArray(), ['class' => 'form-control', 'id' => 'np_areaRecipient'])}
                        {Html::activeHiddenInput($form, 'areaNameWarehouseRecipient', ['id' => 'np_areaNameRecipientName'])}
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        {$form->getLabelByField('cityRecipient')}
                        {Html::activeDropDownList($form, 'cityRecipient', $form->getCitiesRecipientArray(), ['class' => 'form-control', 'id' => 'np_cityRecipient'])}
                        {Html::activeHiddenInput($form, 'cityNameWarehouseRecipient', ['id' => 'np_cityNameRecipientName'])}
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        {$form->getLabelByField('warehouseRecipient')}
                        {Html::activeDropDownList($form, 'warehouseRecipient', $form->getWarehousesRecipientArray(), ['class' => 'form-control', 'id' => 'np_warehouseRecipient'])}
                        {Html::activeHiddenInput($form, 'addressNameWarehouseRecipient', ['id' => 'np_addressNameRecipientName'])}
                    </label>
                </div>

            </div>
        </div>
    </div>
    <div class="form-group">
        <label>
            {$form->getLabelByField('firstname')}
            {Html::activeTextInput($form, 'firstname', ['class' => 'form-control', 'id' => 'np_firstname'])}
        </label>
    </div>
    <div class="form-group">
        <label>
            {$form->getLabelByField('lastname')}
            {Html::activeTextInput($form, 'lastname', ['class' => 'form-control', 'id' => 'np_lastname'])}
        </label>
    </div>
    <div class="form-group">
        <label>
            {$form->getLabelByField('middlename')}
            {Html::activeTextInput($form, 'middlename', ['class' => 'form-control', 'id' => 'np_lastname'])}
        </label>
    </div>
    <div class="form-group">
        <label>
            {$form->getLabelByField('telephone')}
            {Html::activeTextInput($form, 'telephone', ['class' => 'form-control', 'id' => 'np_telephone'])}
        </label>
    </div>
    <div class="form-group">
        <label>
            {$form->getLabelByField('email')}
            {Html::activeTextInput($form, 'email', ['class' => 'form-control', 'id' => 'np_email'])}
        </label>
    </div>
    <div class="form-group">
        <label>
            {$form->getLabelByField('cargoType')}
            {Html::activeDropDownList($form, 'cargo', $form->getCargoTypeArray(), ['class' => 'form-control'])}
        </label>
    </div>
    <div class="form-group">
        <label>
            {$form->getLabelByField('cargoDescription')}
            {Html::activeTextInput($form, 'description', ['class' => 'form-control', 'id' => 'cargoDescriptionSuggest'])}
            <div id="cargoDescriptionSuggestWrap" style="position: relative;"></div>
        </label>
    </div>
    <div class="form-group">
        <label>
            {$form->getLabelByField('seatsAmount')}
            {Html::activeInput('number', $form, 'seatsAmount', ['class' => 'form-control'])}
        </label>
    </div>
    <div class="form-group">
        <label>
            {$form->getLabelByField('weight')}
            {Html::activeInput('number', $form, 'weight', ['class' => 'form-control', 'step' => '0.01'])}
        </label>
    </div>
    <div class="form-group">
        <label>
            {$form->getLabelByField('volumeGeneral')}
            {Html::activeInput('number', $form, 'volumeGeneral', ['class' => 'form-control', 'step' => '0.01'])}
        </label>
    </div>
    <div class="form-group">
        <label>
            {$form->getLabelByField('cost')}
            {Html::activeInput('number', $form, 'cost', ['class' => 'form-control', 'step' => '0.01'])}
        </label>
    </div>
    <div class="form-group">
        <label>
            {Html::activeCheckbox($form, 'backwardDelivery', ['class' => 'uniform', 'value' => '1', 'id' => 'backwardDelivery'])}
        </label>
        <div class="form-group" id="backwardCostWrap">
            <label>
                {$form->getLabelByField('cost')}
                {Html::activeInput('number', $form, 'backwardCost', ['class' => 'form-control', 'step' => '0.01', 'id' => 'backwardDeliveryCost'])}
            </label>
        </div>
    </div>

    <div class="form-group">
        <div class="weight_field">
            <label>
                {$form->getLabelByField('payerType')}
                {Html::activeRadioList($form, 'payerType', $form->getPayers(), [])}
            </label>
        </div>
    </div>
</div>
<script type="text/javascript">
    var cities = {$form->getCitiesJson()};
    var bd = $('body');

    $(function () {
        bd.on('change', '#np_type', function (e) {
            e.preventDefault();
            $('.np_delivery').hide();
            $('.np_warehouseForm .' + $('#np_type').val()).show();
        });
        $('#np_type').change();

        bd.on('change', '#np_areaSender', function (e) {
            emptySelect.call(this);
            getCitiesByArea($(this).val(), $('#np_citySender'));
        });

        bd.on('change', '#np_citySender', function (e) {
            emptySelect.call(this);
            getWarehouseByCity($(this).val(), $('#np_warehouseSender'));
        });

        bd.on('change', '#np_areaRecipient', function (e) {
            emptySelect.call(this);
            getCitiesByArea($(this).val(), $('#np_cityRecipient'));
            setUpNamesWarehouseRecipient();
        });

        bd.on('change', '#np_cityRecipient', function (e) {
            emptySelect.call(this);
            getWarehouseByCity($(this).val(), $('#np_warehouseRecipient'));
            setUpNamesWarehouseRecipient();
        });
        bd.on('change', '#backwardDelivery', function (e) {
            $('#backwardCostWrap').hide();
           if ($(this).prop('checked')) {
               $('#backwardCostWrap').show();
           }
        });
        $('#backwardDelivery').change();
        var cargoDescr = {$form->cargoDescriptions}
        $('#cargoDescriptionSuggest').autocomplete({
            source: function (req, res) {
                var words = req.term.split(' ');
                var results = $.grep(cargoDescr, function(item, index) {
                    var sentence = item.toLowerCase();
                    return words.every(function(word) {
                        return sentence.indexOf(word.toLowerCase()) >= 0;
                    });
                });
                res(results);
            },
            appendTo: '#cargoDescriptionSuggestWrap',
            autoFocus: true,
            delay: 0,
            minLength: 0,
        }).focus(function () {
            $(this).autocomplete("search",$(this).val());
        });
        /*
        $('body').on('submit', '#select_method_form',function (e) {
            console.log('1');
            if ($('input[name="method"]:checked').val() === 'np_np') {
                console.log('2');
                var postVars = $('#np_warehouseForm input, #np_warehouseForm select').serialize()
                $.post("{\Yii::$app->urlManager->createUrl(['nova-poshta/save-internet-document'])}",
                    postVars,
                    function (response) {
                        if (response.hasOwnProperty('status')) {
                            if (response.status) {
                                // alertMessage(response.result);
                            } else {
                                $('#errorNPWrap').text(response.result);
                                e.preventDefault();
                                e.stopPropagation();
                                return false;
                            }
                        }
                    });
            }
        });/**/
    });

    function getCitiesByArea(area, selector) {
        $.each(cities, function (index, item) {
            if (item.Area === area) {
                selector.append($("<option></option>").attr('value', item.Ref).text(item.Description))
            }
        });
    }

    function getWarehouseByCity(city, selector) {
        $.get("{\Yii::$app->urlManager->createUrl(['nova-poshta/get-warehouses'])}", {
            cityRef: city,
        }, function (response) {
            if (response.success && response.data.length > 0) {
                $.each(response.data, function (index, item) {
                    selector.append($("<option></option>").attr('value', item.Ref).text(item.Description))
                });
                setUpNamesWarehouseRecipient();
            }
        });
    }

    var emptySelect = function () {
        var next = $(this).closest('div').nextAll();
        $.each(next, function (index, item) {
            $(item).find('select').find('option').remove();
        })
    }

    function setUpNamesWarehouseRecipient() {
        $('#np_areaNameRecipientName').val($('#np_areaRecipient').find('option:selected').text());
        $('#np_cityNameRecipientName').val($('#np_cityRecipient').find('option:selected').text());
        $('#np_addressNameRecipientName').val($('#np_warehouseRecipient').find('option:selected').text());
    }
</script>
