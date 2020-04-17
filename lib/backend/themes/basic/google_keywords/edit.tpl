{use class="yii\helpers\Html"}
<div class="page-header">
  <div class="page-title">
    <h3>{$app->controller->view->headingTitle}</h3>
  </div>
</div>
<div>
    <h4>{$smarty.const.TABLE_HEADING_KEYWORDS}: <b>{$keyword->gapi_keyword}</b></h4>
</div>
<form action="{Yii::$app->urlManager->createUrl('google_keywords/products-update')}" method="post" id="elements_products" name="elements_products">
{Html::hiddenInput('gapi_id', $keyword->gapi_id)}
<input type="hidden" name="row_id" id="row_id" value="{$row_id}" />
<div class="xl-pr-box" id="box-xl-pr">
  <div class="after">
    <div class="attr-box attr-box-1">
      <div class="widget widget-attr-box box box-no-shadow" style="margin-bottom: 0;">
        <div class="widget-header">
          <h4>{$smarty.const.FIND_PRODUCTS}</h4>
          <div class="box-head-serch after">
            <input type="search" id="element-search-by-products" placeholder="{$smarty.const.SEARCH_BY_ATTR}" class="form-control">
            <button onclick="return false"></button>
          </div>
        </div>
        <div class="widget-content">
          <select id="element-search-products" size="25" style="width: 100%; height: 100%; border: none;" ondblclick="addSelectedElement()">
          </select>
        </div>
      </div>
    </div>
    <div class="attr-box attr-box-2">
      <span class="btn btn-primary" onclick="addSelectedElement()"></span>
    </div>
    <div class="attr-box attr-box-3">
      <div class="widget-new widget-attr-box box box-no-shadow" style="margin-bottom: 0;">
        <div class="widget-header">
          <h4>{$smarty.const.FIELDSET_ASSIGNED_PRODUCTS}</h4>
          <div class="box-head-serch after">
            <input type="search" id="search-elements-assigned" placeholder="{$smarty.const.SEARCH_BY_ATTR}" class="form-control">
            <button onclick="return false"></button>
          </div>
        </div>
        <div class="widget-content">
          <table class="table assig-attr-sub-table element-products">
            <thead>
            <tr role="row">
              <th></th>
              <th>{$smarty.const.TEXT_IMG}</th>
              <th>{$smarty.const.TEXT_LABEL_NAME}</th>
              <th></th>
            </tr>
            </thead>
            <tbody id="elements-assigned">
            {foreach $keyword->products as $pKey => $product}
              {include file="new-product.tpl" product=$product}
            {/foreach}
            </tbody>
          </table>
          <input type="hidden" value="" name="element_sort_order" id="element_sort_order"/>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="btn-bar">
  <div class="btn-left"><a href="{Yii::$app->urlManager->createUrl(['google_keywords/index', 'row_id' => $row_id])}" class="btn btn-cancel-foot">{$smarty.const.IMAGE_CANCEL}</a></div>
  <div class="btn-right"><button class="btn btn-primary">{$smarty.const.IMAGE_SAVE}</button></div>
</div>
</form>
 
<script type="text/javascript">
  function addSelectedElement() {
    $( 'select#element-search-products option:selected' ).each(function() {
      var products_id = $(this).val();
      if ( $('input[name="element_products_id[]"][value="' + products_id + '"]').length ) {
        //already exist
      } else {
        $.post("{Yii::$app->urlManager->createUrl('google_keywords/new-product')}", { 'products_id': products_id }, function(data, status) {
          if (status == "success") {
            $( ".element-products tbody" ).append(data);
          } else {
            alert("Request error.");
          }
        },"html");
      }
    });

    return false;
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
    $('#search-elements-assigned').on('focus keyup', { rows_selector: '#elements-assigned tr', text_selector: '.ast-name-element'}, searchHighlightExisting);

    $('#element-search-by-products').on('focus keyup', function(e) {
      var str = $(this).val();
      $.post( "{Yii::$app->urlManager->createUrl('google_keywords/product-search')}?q="+encodeURIComponent(str), function( data ) {
        $( "select#element-search-products" ).html( data );
        psearch = new RegExp(str, 'i');
        $.each($('select#element-search-products').find('option'), function(i, e){
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

  });
</script>