{$id = rand()}
<div class="review-login login-{$id}">
{\frontend\design\boxes\login\Returning::widget(['params' => $params])}
</div>

<script>
    tl(function(){
        var box = $('.login-{$id}');
        box.on('click', 'button', login);
        box.on('submit', 'input', login);

        function login(){
            $.post('{Yii::$app->urlManager->createUrl(['account/login', 'action' => 'process'])}', $('input', box).serializeArray(), function(d){
                if (d === 'ok') {
                    $.get('{Yii::$app->urlManager->createUrl(['reviews/write'])}', function(write){
                        $('.product-reviews').html(write)
                    })
                    $(window).trigger('logged-in')
                } else {
                    box.html(d)
                }
            });
            return false;
        }
    })
</script>