{use class="yii\helpers\Html"}
<tr role="row" prefix="bundles-box-{$bundles['bundles_id']}">
    <td class="sort-pointer"></td>
    <td class="img-ast img-ast-img">
        {$bundles['image']}
    </td>
    <td class="name-ast">
        {$bundles['products_name']}
        <input type="hidden" name="bundles_id[]" value="{$bundles['bundles_id']}" />
    </td>
    <td class="bu-num plus_td">
        <span class="pr_plus pr-plus-{$bundles['bundles_id']}"></span><input type="text" name="sets_num_product[]" value="{$bundles['num_product']}" class="form-control" /><span class='pr_minus pr-minus-{$bundles['bundles_id']}'></span>
    </td>
    <!-- {*
    <td class="bu-price">
        <input type="text" name="sets_price[]" value="{$bundles['price']}" class="form-control" />
    </td> *} -->
    <td class="bu-disc">
        <input type="text" name="sets_discount[]" value="{$bundles['discount']}" class="form-control" placeholder="0.00%" />
    </td>
    <td class="bu-price-formula">
        <div class="input-group js-price-formula-group">
            {Html::textInput('price_formula_text['|cat:$bundles['bundles_id']|cat:']', $price_formula_text, ['maxlength'=>'64', 'size'=>'32', 'class'=>'form-control js-price-formula-text', 'readonly'=>'readonly'])}
            {Html::hiddenInput('price_formula['|cat:$bundles['bundles_id']|cat:']', $bundles['price_formula'], ['class'=>'js-price-formula-data'])}
            <div class="input-group-addon js-price-formula" data-formula-allow-params="PRICE,DISCOUNT"><i class="icon-money"></i></div>
        </div>
    </td>
    <td class="remove-ast" onclick="deleteSelectedBundles(this)"></td>
</tr>
<script type="text/javascript">
$('.pr-plus-{$bundles['bundles_id']}').click(function(){ 
    val = $(this).next('input').attr('value');
    if (val < 9){ 
      val++;          
    }
    if (val == 9){ 
        $(this).addClass('disableM');
    }
    var input = $(this).next('input');
    input.attr('value', val);
    if (val > 1) input.siblings('.pr_minus').removeClass('disable');
});
 $('.pr-minus-{$bundles['bundles_id']}').click(function(){ 
    //productButtonCell = $('#qty').parents('.qty-buttons');
    val = $(this).prev('input').attr('value');
    if (val > 1){ 
      val--;
      $(this).prev('input').siblings('.more').removeClass('disableM');
    }
    var input = $(this).prev('input');
    input.attr('value', val);
    if (val < 2) $('.pr_minus').addClass('disable');
});
</script>