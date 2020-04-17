{use class="yii\helpers\Html"}
{\backend\assets\MultiSelectAsset::register($this)|void}
<style type="text/css">
    .preloader {
        display: none;
        position: absolute;
        width: auto;
        height: auto;
        margin: 0;
        right: 90px;
        bottom: 15px;
    }
</style>
<div class="page-header">
  <div class="page-title">
    <h3>{$app->controller->view->headingTitle}</h3>
  </div>
</div>

<div class="tabbable tabbable-custom">
    <div class="tab-content">
        <form action="{Yii::$app->urlManager->createUrl('promotions/save')}" method="post" id="promotions" name="promotions">
        {if $promo->isNewRecord}
            Select Promo Variation
            {Html::dropDownList('promo_class', null, $services, ['class' => 'form-control', 'prompt' => PULL_DOWN_DEFAULT, 'onchange' => 'showSave(this.value)'])}
            </br>
        {else}
            <h4>{$services}{Html::hiddenInput('promo_class', $promo->promo_class)}</h4>
        {/if}
        <div class="tab-pane active" id="tab_1">
            <div class="widget box-no-shadow tab-content">
                <div class="widget-content">
                    
                        {Html::hiddenInput('promo_type', '0')}{*price*}
                        {Html::hiddenInput('platform_id', $platform_id)}
                        {Html::hiddenInput('promo_id', $promo->promo_id)}
                        <input type="hidden" name="row_id" id="row_id" value="{$row_id}" />
                        

                        {include file='master.tpl'}

                        <div class="btn-bar">
                          <div class="btn-left"><a href="{Yii::$app->urlManager->createUrl(['promotions/index', 'row_id' => $row_id])}" class="btn btn-cancel-foot">{$smarty.const.IMAGE_CANCEL}</a></div>
                          <div class="btn-right"><div class="preloader"></div><button class="btn btn-primary btn-save" {if $promo->isNewRecord}style="display:none;"{/if}>{$smarty.const.IMAGE_SAVE}</button></div>
                        </div>

                </div>
            </div>
        </div>  
        </form>        
    </div>
</div>
            


<script type="text/javascript">
  function observe(vars, callbalck, request){
      if (request != 'html' && request != 'json') request = 'json';
      vars['request'] = request;
      $.post("{Yii::$app->urlManager->createUrl('promotions/observe')}", vars, function(data, status) {
          if (status == "success") {
            if (typeof callbalck == "function"){
                callbalck(data, vars);
            } else if (typeof callbalck == "object") {                
               callbalck = data;
            }
          } else {
            alert("Request error.");
          }
        },request);        
  }
  
  function closePopup(){
    $(".pop-up-close").trigger('click');
  }
  
  function showSave(state){
    if (state.length > 0){
        $('.btn-save').show();
    } else {
        $('.btn-save').hide();
    }
  }
  
  function loadSettings(_class){
    $.post('promotions/settings',{
            'promo_class': _class,
            'promo_id' : '{$promo->promo_id}',
            'platform_id' : '{$platform_id}',            
        }, function(data, status){
            if (status == 'success'){                
                $('.settings_details').html(data);
            }
        },"html");
    return;
}

  function deleteSelectedElement(obj) {
    $(obj).parent().remove();
    return false;
  }

  var color = '#ff0000';
  var phighlight = function(obj, reg){
    if (reg.length == 0) return;
    $(obj).html($(obj).text().replace( new RegExp( "(" +  reg  + ")" , 'gi' ), '<font style="color:'+color+'">$1</font>'));
    return;
  }

  var searchHighlightExisting = function(e){
    var $rows = $(e.data.rows_selector);
    var search_term = $(this).val();
    $rows.each(function(){
      var $row = $(this);
      var $value_text = $row.find(e.data.text_selector);
      var search_match = true;

      if ( !$row.data('raw-value') ) $row.data('raw-value', $value_text.html());
      var prop_value = $row.data('raw-value');
      if ( search_term.length>0 ) {
        var searchRe = new RegExp(".*" + (search_term + "").replace(/([.?*+\^\$\[\]\\(){}|-])/g, "\\$1") + ".*", 'i');
        if (searchRe.test(prop_value)) {
          phighlight($value_text, search_term);
        } else {
          $value_text.html(prop_value);
          search_match = false;
        }
      }else{
        $value_text.html(prop_value);
      }

      if ( search_match ) {
        $row.show();
      }else{
        $row.hide();
      }
    });
  }

  $(document).ready(function() {
  
    $('select[name=promo_class]').change(function(){
        var _class = $(this).val();
        if(_class){
            loadSettings(_class);
            $('.show_promo_settings').show();
        } else {
            $('.settings_holder').html('');
            $('.show_promo_settings').hide();
        }
    });
  
    $('#search-elements-assigned').on('focus keyup', { rows_selector: '#elements-assigned tr', text_selector: '.ast-name-element'}, searchHighlightExisting);

    $('#element-search-by-products').on('focus keyup', function(e) {
      var str = $(this).val();
      $.post( "{Yii::$app->urlManager->createUrl('promotions/product-search')}?q="+encodeURIComponent(str), { 'platform_id': '{$app->controller->view->platform_id}' }, function( data ) {
        
        if (!$('#element-search-products li.selected').size()){
            $('.btn-select-item').attr('disabled', 'disabled');
        }
        psearch = new RegExp(str, 'i');
        $.each($('element-search-products').find('option'), function(i, e){
          if (psearch.test($(e).text())){
            phighlight(e, str);
          }
        });
      });
    }).keyup();
    
    

    $( ".element-products tbody" ).sortable({
      handle: ".sort-pointer",
      axis: 'y',
      update: function( event, ui ) {
        var data = $(this).sortable('serialize', { attribute: "prefix" });
        $("#element_sort_order").val(data);
      },
    }).disableSelection();
    
    
    $('form[name=promotions]').submit(function(){
        var form = this;
        var submit = true;
        if (typeof beforeSave == 'function') {
            submit = beforeSave(form);
        }
        if (submit){
            $('.preloader').show();
            $.post('promotions/save', $(form).serialize(), function(data, status){
                if (status == 'success'){
                    $('.preloader').hide();
                    if (data.hasOwnProperty('messages')){
                        var str = '<br/>';
                        $.each(Object.keys(data.messages), function(i,e){
                            str += '<center>' + e+': '+data.messages[e]+'</center>';
                        });
                        alertMessage(str+'<br/><br/>');
                        if (typeof afterSave == 'function') {
                            afterSave(form);
                        }
                        setTimeout(function(){ closePopup(); }, 1000);
                    }
                    if (data.hasOwnProperty('promo_id')){
                        setTimeout(function(){
                            window.location.href =  window.location.href + '&promo_id='+data.promo_id;
                        }, 1000);
                    }
                }
            },'json');
        }
        return false;
    });
      
  });
  
  
</script>