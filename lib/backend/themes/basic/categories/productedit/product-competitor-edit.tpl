{use class="yii\helpers\Html"}
<style>
#buttons-products_price_mask { padding-bottom: 5px; }
</style>
<div class="prcEditPage popupEditCat">
    <div class="modal-header">      
        {if $cProduct->isNewRecord}
            {$smarty.const.IMAGE_NEW}
        {else}
            {$smarty.const.IMAGE_EDIT}
        {/if}
    </div> 
<form id="save_prc_form" name="cproduct_edit" action="{Yii::$app->urlManager->createUrl(['categories/save-competitor-product'])}">
    {Html::hiddenInput('pID', $pID)}
    {Html::hiddenInput('pcID', $cProduct->competitors_products_id)}
    <div class="popupCategory">
        <table cellspacing="0" cellpadding="0" width="100%">
            <tr>
                <td class="label_name">{$fields['competitor_name']}</td>
                <td class="label_value">{Html::dropDownList('competitors_id', $cProduct->competitors_id, \yii\helpers\ArrayHelper::map($competitors, 'competitors_id', 'competitors_name'), ['class' => 'form-control'])}</td>
            </tr>
            <tr>
                <td class="label_name">{$fields['products_model']}</td>
                <td class="label_value">{Html::textInput('products_model', $cProduct->products_model, ['class' => 'form-control'])}</td>
            </tr>
            <tr>
                <td class="label_name">{$fields['products_name']}</td>
                <td class="label_value">{Html::textInput('products_name', $cProduct->products_name, ['class' => 'form-control'])}</td>
            </tr>
            <tr>
                <td class="label_name">{$fields['products_url']}</td>
                <td class="label_value">{Html::textInput('products_url', $cProduct->products_url, ['class' => 'form-control'])}</td>
            </tr>
            <tr>
                <td class="label_name">{$fields['products_url_short']}</td>
                <td class="label_value">{Html::textInput('products_url_short', $cProduct->products_url_short, ['class' => 'form-control'])}</td>
            </tr>
            <tr>
                <td class="label_name">{$fields['products_price']}</td>
                <td class="label_value">{Html::textInput('products_price', $cProduct->products_price, ['class' => 'form-control'])}</td>
            </tr>
            <tr>
                <td class="label_name">{$smarty.const.TEXT_PRICE_MASK}</td>
                <td class="label_value">
                    <div class="" id="buttons-products_price_mask">
                        <a href="javascript:void(0);" class="btn" onclick="pasteCurrency(event)">{$smarty.const.PASTE_CURRENCY}</a>
                        <a href="javascript:void(0);" class="btn" onclick="pastePrice(event)">{$smarty.const.PASTE_PRICE}</a>
                    </div>
                    {Html::textArea('products_price_mask', $cProduct->products_price_mask, ['class' => 'form-control', 'id' => 'products_price_mask', 'rows' => 5])}
                    {sprintf($smarty.const.COMPETITOR_TEMPLATES_NOTE, '##CURRENCY##', '##PRICE##')}
                </td>
            </tr>
        </table>    

    <div class="btn-bar edit-btn-bar">
        <div class="btn-left"><a href="javascript:void(0)" class="btn btn-cancel cp-cancel">{$smarty.const.IMAGE_CANCEL}</a></div>
        <div class="btn-right"><button class="btn btn-primary">{$smarty.const.IMAGE_SAVE}</button></div>
    </div>
</div>
    <input type="hidden" name="popup" value="1" />
</form>
</div>

<script type="text/javascript">
    defineCompetitor = function(id){        
        if (id){
            $.each(competitors, function(i,e){ 
                if (id == e.id){
                    {if !$cProduct->isNewRecord}
                        if (!$('#products_price_mask').val().length){
                    {/if}
                        $('#products_price_mask').val(e.mask);
                    {if !$cProduct->isNewRecord}
                        }
                    {/if}
                    {if !$cProduct->isNewRecord}
                        if (!$('input[name=products_url]').val().length){
                    {/if}
                        $('input[name=products_url]').val(e.site);
                    {if !$cProduct->isNewRecord}
                        }
                    {/if}
                }
            });
        }
    }
    
    var selectedText;
    
    function pasteCurrency(e){
        var container = document.querySelector('#products_price_mask');
        if (selectedText){
            container.value = container.value.replace(selectedText, '##CURRENCY##');
        } else {
            container.value = container.value.substr(0, container.selectionStart) + '##CURRENCY##' + container.value.substr(container.selectionStart) ;
            console.log(container.value);
        }
    }
    function pastePrice(e){
        var container = document.querySelector('#products_price_mask');
        if (selectedText){
            container.value = container.value.replace(selectedText, '##PRICE##');
        } else {
            container.value = container.value.substr(0, container.selectionStart) + '##PRICE##' + container.value.substr(container.selectionStart) ;
        }
    }
    
    function caseMenu(e){        
        if (typeof window.getSelection == 'function'){
            selectedText = window.getSelection().toString();
        } else {
            selectedText = document.selection.createRange().text;
        }
    }
    
    document.getElementById('products_price_mask').addEventListener('select', function(e){ caseMenu(e); })
    
    $(document).ready(function(){        
        defineCompetitor($('select[name=competitors_id]').val());
        selectedText = null;
        $('select[name=competitors_id]').change(function(){
            defineCompetitor($(this).val());
        });
        
        $('#products_price_mask').on('select', function(e){
            caseMenu(e);
        })
        
        $('#save_prc_form').submit(function(){
            var form = this;
            $.post($(form).attr('action'), $(form).serialize(), function(data){
                if (data.hasOwnProperty('error')){
                    wrapMessage(data.message);
                    if (data.error == false){
                        setTimeout(function (){ 
                            $('.pop-up-close:last').trigger('click');
                            $('.popup-box-wrap:last').remove();
                            
                            if (typeof cTable == 'object'){
                                cTable.fnDraw(false);
                                updateCompetitorsList();
                            }
                            
                        }, 1000);
                    }
                }
            }, 'json');
            return false;
        });
        
        $(".check_on_off").bootstrapSwitch(
        {
            onSwitchChange: function (element, arguments) {
                //switchStatement(element.target.value, arguments);
                return true;  
            },
            onText: "{$smarty.const.SW_ON}",
            offText: "{$smarty.const.SW_OFF}",
            handleWidth: '20px',
            labelWidth: '24px'
        });
    })
</script>