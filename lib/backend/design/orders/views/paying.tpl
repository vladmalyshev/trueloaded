{use class="yii\helpers\Html"}
<div class="update_paid_on_process_holder">
    <div class="f_td">
    </div>
    <div class="f_td">
        {Html::checkbox('use_update_amount', false, ['label' => TEXT_UPDATE_PAID_AMOUNT, 'class' => 'upade_paid_on_process', 'id' => 'use_update_amount_checkbox'])}
        {Html::dropDownList('paid_prefix', '+', ['+' => '+', '-' => '-'], ['class' => 'form-control', 'style' => 'margin-left:5px; width: 45px; display: none;', 'id' => 'use_update_amount_paid_prefix'])}
        {Html::input('hidden', 'update_paid_amount', 0, ['class' => 'form-control', 'style' => 'margin-left:5px; width: 100px; display: inline-block;', 'id' => 'update_paid_amount_input'])}
         <script>
            (function($) {
                $('.upade_paid_on_process').change(function (e) {
                    e.preventDefault();
                    let parent = $(this).closest('div.update_paid_on_process_holder');
                    if ($(this).prop('checked')){
                        $(parent).find('input[name=update_paid_amount]').attr('type', 'input');
                        $(parent).find('select[name=paid_prefix]').css('display', 'inline-block');
                    } else {
                        $(parent).find('input[name=update_paid_amount]').attr('type', 'hidden').val('0');
                        $(parent).find('select[name=paid_prefix]').css('display', 'none');
                    }
                });
            }(jQuery));
        </script>
    </div>
</div>