{use class="yii\helpers\Html"}
<div class="btn-bar btn-bar-top after">
    <div class="btn-left"><a href="javascript:void(0)" onclick="return backStatement();" class="btn btn-back">Back</a></div>
</div>

<div class="row">
    <div class="col-md-12 editing" id="order_management_data">
        <div class="widget box box-no-shadow">
            <div class="widget-header widget-header-contact">
                <h4>{$smarty.const.T_CONTACT}</h4>
                <div class="toolbar no-padding">
                    <div class="btn-group">
                        <span class="btn btn-xs widget-collapse"><i class="icon-angle-down"></i></span>
                    </div>
                </div>
            </div>
            <div class="widget-content">
                <div class="row">
                    <div class="col-xs-6">
                        <div class="cr-ord-cust">
                            <span>{$smarty.const.ENTRY_CUSTOMER}</span>
                            <div><a href="{Yii::$app->urlManager->createUrl(['customers/customeredit','customers_id' => $model->customer_id])}">
                                    {$model->customer_firstname|cat:' '|cat:$model->customer_lastname}<br>
                                    {$model->shippingCountry[0]->countries_name}<br>
                                    {if $model->ship_state}
                                        {$model->ship_state}<br>
                                    {/if}
                                    {if $model->ship_city}
                                       {if $model->ship_postcode}{$model->ship_postcode},{/if}  {$model->ship_city}<br>
                                    {/if}
                                    {if $model->ship_address_line1}
                                        {$model->ship_address_line1}<br>
                                    {/if}
                                    {if $model->ship_address_line2}
                                        {$model->ship_address_line2}<br>
                                    {/if}
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="w-line-row col-xs-6">
                        <div class="edp-line">
                            <label>{$smarty.const.ENTRY_EMAIL_ADDRESS}</label>
                            {Html::input('text', 'update_customer_email', $model->customer->customers_email_address, ['size' => "15" , 'disabled' => true, 'class'=>"form-control"])}
                        </div>
                        <div class="edp-line">
                            <label>{$smarty.const.ENTRY_TELEPHONE_NUMBER}</label>
                            {Html::input('text', 'update_customer_telephone', $model->customer->customers_telephone, ['size' => "15" ,'disabled' => true, 'class'=>"form-control"])}
                        </div>
                        <div class="edp-line">
                            <label>{$smarty.const.ENTRY_LANDLINE}</label>
                            {Html::input('text', 'update_customer_landline', $model->customer->customers_landline, ['size' => "15" ,'disabled' => true, 'class'=>"form-control"])}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!--begin select shipping and payment Block-->
        <div class="widget box box-no-shadow">
            <div class="box-or-prod-wrap-">
                <div id="products_holder">
                    {include 'product_listing.tpl'}
                </div>
                {*<div class="">*}
                    {*<div id="totals_holder">*}
                        {*{$order_total_details}*}
                    {*</div>*}
                {*</div>*}
            </div>

        </div>

    </div>
</div>
<script>
    function backStatement() {
        window.history.back();
        return false;
    }
</script>