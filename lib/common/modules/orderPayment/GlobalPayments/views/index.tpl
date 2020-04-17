<div id="pay_but_wrap">
    <button type="button" id="payButtonId" class="btn" style="display:none">Checkout Now</button>
</div>

<script type="text/javascript" src="/themes/basic/js/rxp-js.min.js"></script>
<script>
    tl(function(){
        /*$('body').one('click','#sendConfirmButton',function(){
            $("#payButtonId").click();
            return false;
        });/**/
        $(document).ready(function () {
            $.getJSON("{$app->urlManager->createUrl(['global-payments/authorization-request'])}",{
                id:$('input[name="order_id"]').val()
            }, function (jsonFromServerSdk) {
                RealexHpp.setHppUrl('https://pay.sandbox.realexpayments.com/pay');
                RealexHpp.init("payButtonId", "http://tl.local/", jsonFromServerSdk);
                $("#payButtonId").click();/**/
            });
        });
    });
</script>
