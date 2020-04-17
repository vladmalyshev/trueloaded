{use class="yii\helpers\Html"}
<div class="page-header">
  <div class="page-title">
    <h3>{$app->controller->view->headingTitle}</h3>
  </div>
</div>
<form action="{Yii::$app->urlManager->createUrl('pdf-catalogues/products-update')}" method="post" id="pdf_catalogues_products" name="pdf_catalogues_products">
{Html::hiddenInput('pdf_catalogues_id', $pcInfo->pdf_catalogues_id)}
<div class="xl-pr-box" id="box-xl-pr">
  <div class="after">
    <div class="attr-box attr-box-1">
      <div class="widget widget-attr-box box box-no-shadow" style="margin-bottom: 0;">
        <div class="widget-header">
          <h4>{$smarty.const.FIND_PRODUCTS}</h4>
          <div class="box-head-serch after">
            <input type="search" id="pdf-catalogue-search-by-products" placeholder="{$smarty.const.SEARCH_BY_ATTR}" class="form-control">
            <button onclick="return false"></button>
          </div>
        </div>
        <div class="widget-content">
          <select id="pdf-catalogue-search-products" size="25" style="width: 100%; height: 100%; border: none;" ondblclick="addSelectedPDFCatalogue()" multiple="">
          </select>
        </div>
      </div>
    </div>
    <div class="attr-box attr-box-2">
      <span class="btn btn-primary" onclick="addSelectedPDFCatalogue()"></span>
    </div>
    <div class="attr-box attr-box-3">
      <div class="widget-new widget-attr-box box box-no-shadow" style="margin-bottom: 0;">
        <div class="widget-header">
          <h4>{$smarty.const.FIELDSET_ASSIGNED_PRODUCTS}</h4>
          <div class="box-head-serch after">
            <input type="search" id="search-pdf-catalogues-assigned" placeholder="{$smarty.const.SEARCH_BY_ATTR}" class="form-control">
            <button onclick="return false"></button>
          </div>
        </div>
        <div class="widget-content">
          <table class="table assig-attr-sub-table pdf-catalogue-products">
            <thead>
            <tr role="row">
              <th>{$smarty.const.TEXT_IMG}</th>
              <th>{$smarty.const.TEXT_LABEL_NAME}</th>
              <th>{$smarty.const.TABLE_HEADING_PRODUCTS_MODEL}</th>
              <th></th>
            </tr>
            </thead>
            <tbody id="pdf-catalogues-assigned">
            {foreach $app->controller->view->pdf_catalogueProducts as $eKey => $pdf_catalogue}
              {include file="new-product.tpl" pdf_catalogue=$pdf_catalogue}
            {/foreach}
            </tbody>
          </table>
          <input type="hidden" value="" name="pdf_catalogue_sort_order" id="pdf_catalogue_sort_order"/>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="btn-bar">
  <div class="btn-left"><a href="{Yii::$app->urlManager->createUrl(['pdf-catalogues/index', 'pcID' => $pcInfo->pdf_catalogues_id])}" class="btn btn-cancel-foot">Cancel</a></div>
  <div class="btn-right"><button class="btn btn-primary">Save</button></div>
</div>
</form>
 
<script type="text/javascript">
  function addSelectedPDFCatalogue() {
    var ids = '';
    $( 'select#pdf-catalogue-search-products option:selected' ).each(function() {
      var products_id = $(this).val();
      if ( $('input[name="pdf_catalogue_products_id[]"][value="' + products_id + '"]').length ) {
        //already exist
      } else {
        ids = ids + products_id + ',';
      }
    });

    if (ids != '') {
      $.post("{Yii::$app->urlManager->createUrl('pdf-catalogues/new-product')}", { 'products_ids': ids }, function(data, status) {
        if (status == "success") {
          $( ".pdf-catalogue-products tbody" ).append(data);
        } else {
          alert("Request error.");
        }
      },"html");
    }

    return false;
  }

  function deleteSelectedPDFCatalogue(obj) {
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
    $('#search-pdf-catalogues-assigned').on('focus keyup', { rows_selector: '#pdf-catalogues-assigned tr', text_selector: '.ast-name-pdf-catalogue'}, searchHighlightExisting);

    $('#pdf-catalogue-search-by-products').on('focus keyup', function(e) {
      var str = $(this).val();
      $.post( "{Yii::$app->urlManager->createUrl('pdf-catalogues/product-search')}?q="+encodeURIComponent(str), function( data ) {
        $( "select#pdf-catalogue-search-products" ).html( data );
        psearch = new RegExp(str, 'i');
        $.each($('select#pdf-catalogue-search-products').find('option'), function(i, e){
          if (psearch.test($(e).text())){
            phighlight(e, str);
          }
        });
      });
    }).keyup();
  });
</script>