<!--=== Page Content ===-->
<div id="customer_management_data">
</div>

<link href="{$app->view->theme->baseUrl}/css/trade-form.css" rel="stylesheet" type="text/css" />

<form name="customer_edit" id="customers_edit" onSubmit="return check_form();">

    {\frontend\design\Block::widget(['name' => 'trade_form', 'params' => ['type' => 'trade_form', 'params' => [
     'customers_id' => $customers_id,
     'inline_styles' => true
    ]]])}


    <input name="customers_id" value="{$customers_id}" type="hidden">

    <div class="btn-bar">
        <div class="btn-left"><a href="javascript:void(0)" class="btn btn-cancel-foot" onclick="return backStatement()">{$smarty.const.IMAGE_CANCEL}</a></div>
        <div class="btn-right">
            <a href="{$app->urlManager->createUrl('customers/trade-acc')}?customers_id={$customers_id}" target="_blank" class="btn btn-primary btn-pdf">PDF</a>
            <button class="btn btn-confirm">{$smarty.const.IMAGE_SAVE}</button>
        </div>
    </div>
</form>


<script>
    function backStatement() {
        window.history.back();
        return false;
    }
    function check_form() {

        var customers_edit = $('#customers_edit');
        var values = customers_edit.serializeArray()
        values = values.concat(
                $('input[type=checkbox]:not(:checked)', customers_edit).map(function() {
                    console.log(this.name);
                    return { "name": this.name, "value": 0}
                }).get()
        );

        $.post("{$app->urlManager->createUrl('customers/customer-additional-fields-submit')}", values, function(data, status){
            if (status == "success") {
                $('#customer_management_data').html(data);
                $('.btn-confirm', customers_edit).hide();
                $('.btn-pdf', customers_edit).show();
            } else {
                alert("Request error.");
            }
        },"html");
        return false;
    }
    $(document).ready(function(){


        $('.w-account-addresses-list').each(function(){

            var box = $(this);

            var group = $('input[name="group_id"]', box).val();

            var $groupInputs = $('input[data-group-id="' + group + '"]')

            if (!$groupInputs.filter('input[data-type="firstname"], input[data-type="lastname"]').length){
                $('.name', box).remove()
            }
            if (!$groupInputs.filter('input[data-type="company"]').length){
                $('.company', box).remove()
            }
            if (!$groupInputs.filter('input[data-type="phone"]').length){
                $('.phone', box).remove()
            }
            if (!$groupInputs.filter('input[data-type="email"]').length){
                $('.email', box).remove()
            }


            $('input[type="radio"]', box).on('change', function(){
                var $addressFields = $(this).closest('.address-fields');
                $groupInputs.each(function () {
                    var type = $(this).data('type');
                    var val = $('input[name="' + type + '"]', $addressFields).val()
                    $(this).val(val)
                })
            })
        })





        $(window).resize(function(){
            $('.cbox-right .box-no-shadow').css('min-height', $('.cbox-left').height() - 20);
        });
        $(window).resize();


        var customers_edit = $('#customers_edit');
        $('.btn-confirm', customers_edit).hide();
        customers_edit.on('change', function(){
            $('.btn-confirm', customers_edit).show();
            $('.btn-pdf', customers_edit).hide();
        });
        $('input', customers_edit).on('keyup', function(){
            $('.btn-confirm', customers_edit).show();
            $('.btn-pdf', customers_edit).hide();
        })
    });
</script>