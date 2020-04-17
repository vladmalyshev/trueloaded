{use class="\yii\helpers\Html"}
<div class="after bundl-box">
    <div class="attr-box attr-box-1">
        <div class="widget widget-attr-box box box-no-shadow" style="margin-bottom: 0;">
            <div class="widget-header">
                <h4>{$smarty.const.FIND_PRODUCTS}</h4>
                <div class="box-head-serch after">
                    <input type="search" id="linked-search-by-products" placeholder="{$smarty.const.SEARCH_BY_ATTR}" class="form-control">
                    <button onclick="return false"></button>
                </div>
            </div>
            <div class="widget-content">
                <select id="linked-search-products" size="25" style="width: 100%; height: 100%; border: none;" ondblclick="addSelectedLinked()">
                </select>
            </div>
        </div>
    </div>
    <div class="attr-box attr-box-2">
        <span class="btn btn-primary" onclick="addSelectedLinked()"></span>
    </div>
    <div class="attr-box attr-box-3">
        <div class="widget-new widget-attr-box box box-no-shadow" style="margin-bottom: 0;">
            <div class="widget-header">
                <h4>{$smarty.const.FIELDSET_ASSIGNED_PRODUCTS}</h4>
                <div class="box-head-serch after">
                    <input type="search" placeholder="{$smarty.const.TEXT_SEARCH_ASSIGNED_ATTR}" class="form-control">
                    <button onclick="return false"></button>
                </div>
            </div>
            <div class="widget-content">
                <table class="table assig-attr-sub-table linked-products" data-count="{count($pInfo->products_linked_children)}">
                    <thead>
                    <tr role="row">
                        <th></th>
                        <th>{$smarty.const.TEXT_IMG}</th>
                        <th>{$smarty.const.TEXT_LABEL_NAME}</th>
                        <th>{$smarty.const.TEXT_TITLE_NUMBER}</th>
                        <th style="display: none">{$smarty.const.TEXT_LINKED_PRODUCT_SHOW_ON_INVOICE}</th>
                        <th style="display: none">{$smarty.const.TEXT_LINKED_PRODUCT_SHOW_ON_PACKING_SLIP}</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    {foreach $pInfo->products_linked_children as $idx => $linked_child}
                        <tr role="row" prefix="linked-box-{$linked_child['bundles_id']}">
                            <td class="sort-pointer"></td>
                            <td class="img-ast img-ast-img">
                                <img src="{$linked_child['img-src']}" alt="" border="0">
                            </td>
                            <td class="name-ast">
                                <a target="_blank" href="{Yii::$app->urlManager->createUrl(['categories/productedit', 'pID' => $linked_child['linked_product_id']])}">{$linked_child['products_name']}</a>
                                <input type="hidden" name="products_linked_children[{$idx}][linked_product_id]" value="{$linked_child['linked_product_id']}" />
                            </td>
                            <td class="bu-num plus_td">
                                <span class="pr_plus"></span><input type="text" name="products_linked_children[{$idx}][linked_product_quantity]" value="{$linked_child['linked_product_quantity']}" class="form-control" /><span class='pr_minus'></span>
                            </td>
                            <td class="bu-disc" style="display: none">
                                <input type="checkbox" class="link_switch" name="products_linked_children[{$idx}][show_on_invoice]"  {if $linked_child.show_on_invoice}checked="checked"{/if}  value="1">
                            </td>
                            <td class="bu-disc" style="display: none">
                                <input type="checkbox" class="link_switch" name="products_linked_children[{$idx}][show_on_packing_slip]"  {if $linked_child.show_on_packing_slip}checked="checked"{/if}  value="1">
                            </td>
                            <td class="remove-ast" onclick="deleteSelectedLinked(this)"></td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
                <input type="hidden" value="" name="linked_sort_order" id="linked_sort_order"/>
            </div>

        </div>
    </div>
</div>

<script type="text/template" id="linkedProductSkel">
<tr role="row" prefix="linked-box-%%product_id%%">
    <td class="sort-pointer"></td>
    <td class="img-ast img-ast-img">
        <img src="%%img-src%%" alt="" border="0">
    </td>
    <td class="name-ast">
        <a target="_blank" href="{Yii::$app->urlManager->createUrl(['categories/productedit', 'pID' => ''])}%%product_id%%">%%name%%</a>
        <input type="hidden" name="products_linked_children[%%counter%%][linked_product_id]" value="%%product_id%%" />
    </td>
    <td class="bu-num plus_td">
        <span class="pr_plus"></span><input type="text" name="products_linked_children[%%counter%%][linked_product_quantity]" value="1" class="form-control" /><span class='pr_minus'></span>
    </td>
    <td class="bu-disc" style="display: none">
        <input type="checkbox" class="link_switch" name="products_linked_children[%%counter%%][show_on_invoice]"  {if $LinkedChildrenDefaults.show_on_invoice}checked="checked"{/if} value="1">
    </td>
    <td class="bu-disc" style="display: none">
        <input type="checkbox" class="link_switch" name="products_linked_children[%%counter%%][show_on_packing_slip]" {if $LinkedChildrenDefaults.show_on_packing_slip}checked="checked"{/if} value="1">
    </td>
    <td class="remove-ast" onclick="deleteSelectedLinked(this)"></td>
</tr>
</script>

<script type="text/javascript">


    function addSelectedLinked() {
        $( 'select#linked-search-products option:selected' ).each(function() {
            var product_id = $(this).val();
            var $option = $(this);
            if ( $( 'input[name$="[linked_product_id]"][value="'+product_id+'"]' ).length ) {
                //already exist
            } else {
                $('.linked-products').trigger('add_row', {
                    'product_id':product_id,
                    'name' : $option.text(),
                    'img-src' : $option.attr('data-image-src')
                });
            }
        });

        return false;
    }

    function deleteSelectedLinked(obj) {
        $(obj).parent().remove();
        return false;
    }

    var color = '#ff0000';
    var phighlight = function(obj, reg){
        if (reg.length == 0) return;
        $(obj).html($(obj).text().replace( new RegExp( "(" +  reg  + ")" , 'gi' ), '<font style="color:'+color+'">$1</font>'));
        return;
    }

    $(document).ready(function() {
        setTimeout(function(){
            $('.linked-products .pr_plus').off('click');
            $('.linked-products .pr_minus').off('click');
        },1500);
        $('.linked-products').on('click', '.plus_td', function(event){
            var $target = $(event.target);
            var $input = $(this).find('input');
            if ( $target.hasClass('pr_plus') ){
                $input.val(parseInt($input.val(),10)+1);
                event.stopPropagation();
            }else if ( $target.hasClass('pr_minus') && parseInt($input.val(),10)-1>0 ){
                $input.val(parseInt($input.val(),10)-1);
                event.stopPropagation();
            }
            return false;
        });

        $('.linked-products').on('init_row',function(event, idx) {
            var $row = $($(this).find('tbody tr').get(idx));
            if ( $row.data('inited') ) return;

            $('.link_switch',$row).bootstrapSwitch({
                onText: "{$smarty.const.SW_ON|escape:'javascript'}",
                offText: "{$smarty.const.SW_OFF|escape:'javascript'}",
                handleWidth: '20px',
                labelWidth: '24px'
            });

            $row.data('inited',1);
        });
        $('.linked-products').on('add_row',function(event, data) {

            var $table = $(this);
            var template = $('#linkedProductSkel').html();

            var counter = parseInt($table.attr('data-count')||0);
            data['counter'] = counter;
            $table.attr('data-count', counter+1);

            for( var _k in data ) {
                var re = new RegExp('%%'+_k+'%%','g');
                template = template.replace(re, data[_k]);
            }


            $('tbody',$table).append(template);
            $table.trigger('init_row', $('tbody',$table).find('tr').length-1 );
        });
        $('.linked-products tbody tr').each(function(idx, row){
            $('.linked-products').trigger('init_row',[idx]);
        });


        $('#linked-search-by-products').on('focus keyup', function(e) {
            var str = $(this).val();
            $.post( "{Yii::$app->urlManager->createUrl('categories/product-search')}?q="+encodeURIComponent(str)+"&not={$pInfo->products_id}&bundle_skip=1&linked_skip=1&with_images=1", function( data ) {
                $( "select#linked-search-products" ).html( data );
                psearch = new RegExp(str, 'i');
                $.each($('select#linked-search-products').find('option'), function(i, e){
                    if (psearch.test($(e).text())){
                        phighlight(e, str);
                    }
                });
            });
        }).keyup();

        $( ".linked-products tbody" ).sortable({
            handle: ".sort-pointer",
            axis: 'y',
            update: function( event, ui ) {
                var data = $(this).sortable('serialize', { attribute: "prefix" });
                $("#linked_sort_order").val(data);
            }
        });


    });

</script>