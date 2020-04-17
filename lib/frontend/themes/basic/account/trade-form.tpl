{use class="frontend\design\Info"}
{use class = "yii\helpers\Html"}
{use class="frontend\design\Block"}
{Html::beginForm('', 'customer_edit', ['id' => 'customers_edit', 'onSubmit' => 'return check_form()'], false)}
    <input name="customers_id" value="{$customers_id}" type="hidden">

{Block::widget(['name' => 'trade_form', 'params' => ['type' => 'trade_form', 'params' => $params]])}
{*
    <div class="buttons">
        <div class="left-buttons">
            {if !$create}
            <a href="javascript:void(0)" class="btn btn-cancel-foot" onclick="return backStatement()">{$smarty.const.CANCEL}</a>
            {/if}
        </div>
        <div class="right-buttons">
            {if $create}
                <input type="hidden" name="create" value="1"/>
                <button class="btn">{$smarty.const.IMAGE_BUTTON_CONTINUE}</button>
            {else}
                <a href="{$app->urlManager->createUrl('account/trade-acc')}?customers_id={$customers_id}" target="_blank" class="btn-1 btn-pdf">PDF</a>
                <button class="btn btn-confirm">{$smarty.const.TEXT_WISHLIST_SAVE}</button>
            {/if}
        </div>
    </div>
*}
{Html::endForm()}
<script>
    function check_form() {

        var customers_edit = $('#customers_edit');
        var values = customers_edit.serializeArray()

        $.post("{$app->urlManager->createUrl('account/trade-form-submit')}", values, function(data, status){
            if (status == "success") {
                {if $create}
                window.location.href = '{$app->urlManager->createUrl(['account', 'page_name' => 'created_success'])}';
                {else}
                location.reload();
                {/if}
            } else {
                alert("Request error.");
            }
        },"html");
        return false;
    }

    tl('{Info::themeFile('/js/main.js')}', function(){
        var customers_edit = $('#customers_edit');
        $('.btn-confirm', customers_edit).hide();
        customers_edit.on('change', function(){
            $('.btn-confirm', customers_edit).show();
            $('.btn-pdf', customers_edit).hide();
        });
        $('input', customers_edit).on('keyup change', function(){
            $('.btn-confirm', customers_edit).show();
            $('.btn-pdf', customers_edit).hide();
        });

        $('.trade_form').inRow(['.w-account-addresses-list'], 2)
    })
</script>