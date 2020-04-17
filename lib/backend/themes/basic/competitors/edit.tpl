{use class="\yii\helpers\Html"}
<style>
#supermenu{
    position:absolute;
    color:#000000;
    background:#fff;
    border:1px solid #ccc;
    top: -90px;
    left: 0px;
    display: block;
    margin: 0;
    padding: 10px;
    line-height: 20px;
    list-style: none;
    cursor:pointer;
}
</style>

<!--=== Page Content ===-->
<div id="rewiews_management_data">
 <div class="box-wrap widget box">
    {if Yii::$app->request->isAjax}
    <div class="modal-header">      
        {$app->controller->view->headingTitle}      
    </div> 
    {/if}
      <div class="widget-content">
        {if is_array($messages) > 0}
          {foreach $messages as $messageType => $message}
            <div class="alert fade in {$messageType}">
              <i data-dismiss="alert" class="icon-remove close"></i>
              <span id="message_plce">{$message[0]}</span>
            </div>               
          {/foreach}
        {/if}

{Html::beginForm('competitors/save', 'post', ['name' => 'competitor' ])}
    {Html::hiddenInput('competitors_id', $competitor->competitors_id)}
    
    {if Yii::$app->request->isAjax}
        {Html::hiddenInput('is_ajax', 1)}
    {/if}

    <div class="main_row">
        <div class="main_title">{$smarty.const.TEXT_COMPETITORS_NAME}</div>
        <div class="main_value">{Html::textInput('competitors_name', $competitor->competitors_name, ['class' => "form-control"])}</div>
    </div>
    <div class="main_row">
        <div class="main_title">{$smarty.const.TEXT_COMPETITORS_SITE}</div>
        <div class="main_value">{Html::textInput('competitors_site', $competitor->competitors_site, ['class' => "form-control", 'placeholder'=> 'http://'])}</div>
    </div>
    <div class="main_row">
        <div class="main_title">{$smarty.const.TEXT_COMPETITORS_CURRENCY}</div>
        <div class="main_value">{Html::dropDownList('competitors_currency', $competitor->competitors_currency, $curr, ['prompt' => PULL_DOWN_DEFAULT, 'class' => "form-control"])}</div>
    </div>
    <div class="main_row">
        <div class="main_title">{$smarty.const.TEXT_COMPETITORS_MASK}</div>
        <div class="main_value">{Html::textInput('competitors_mask', $competitor->competitors_mask, ['class' => "form-control", 'id' => 'competitors_mask'])}
            <div style="position:relative">
               <ul type="context" id="supermenu" style="display:none;">
                    <li label="trial" onclick="pasteCurrency(event)">{$smarty.const.PASTE_CURRENCY}</li>
                    <li label="trial" onclick="pastePrice(event)">{$smarty.const.PASTE_PRICE}</li>
                </ul>
            </div>
            {sprintf($smarty.const.COMPETITOR_TEMPLATES_NOTE, '##CURRENCY##', '##PRICE##')}
        </div>
    </div>
    
    <div class="btn-bar">
        <div class="btn-left"><a href="javascript:void(0)"  {if !Yii::$app->request->isAjax} onclick="return backStatement();"{/if} class="btn btn-cancel">{$smarty.const.IMAGE_CANCEL}</a></div>
        <div class="btn-right"><button class="btn btn-confirm">{$smarty.const.IMAGE_SAVE}</button></div>
    </div>    

{Html::endForm()}
      </div>
    </div>
  </div>
  
  <script>
  function backStatement() {
    window.history.back();
  }
  
  function wrapMessage(str){
    alertMessage('<div class="widget box"><div class="widget-content">'+str+'</div><div class="noti-btn"><div><span class="btn btn-cancel">{$smarty.const.TEXT_OK}</span></div></div></div>');
  }
  
  function closePopup() {    
    $('.popup-box-wrap:last').remove();
    return false;
 }
  
  {if Yii::$app->request->isAjax}
    $('.btn-cancel').click(function(){
        closePopup();
    })
  {/if}
  
      var selectedText;
    
    function pasteCurrency(e){
        if (selectedText){
            document.querySelector('#competitors_mask').value = document.querySelector('#competitors_mask').value.replace(selectedText, '##CURRENCY##');
            closeMenu();
        }
    }
    function pastePrice(e){
        if (selectedText){
            document.querySelector('#competitors_mask').value = document.querySelector('#competitors_mask').value.replace(selectedText, '##PRICE##');
            closeMenu();
        }
    }
    
    function caseMenu(e){        
        if (typeof window.getSelection == 'function'){
            selectedText = window.getSelection().toString();
        } else {
            selectedText = document.selection.createRange().text;
        }        
        if (selectedText){
            menu(e);
        }
    }
    
    document.getElementById('competitors_mask').addEventListener('select', function(e){ caseMenu(e); })
    
    function menu(evt){
		evt = evt || window.event;
		evt.cancelBubble = true;
		$('#supermenu').show();
		return false;		
	}
    
    function closeMenu(){
        $('#supermenu').hide();
    }
  
  {if Yii::$app->request->isAjax}
    $('form[name=competitor]').submit(function(){
        var form = this;
        $.post($(form).attr('action'), $(form).serialize(), function(data){
            if (data.hasOwnProperty('message')){
                wrapMessage(data.message);
                if (data.type != 'error'){
                    setTimeout(function (){ 
                        $('.pop-up-close:last').trigger('click');
                        $('.btn-cancel:last').trigger('click');
                        
                        if (typeof updateCompetitorsList == 'function'){
                            updateCompetitorsList();
                        }
                        
                    }, 1000);
                }
            }
        }, 'json');
        return false;
    })
  {/if}
  
  </script>
