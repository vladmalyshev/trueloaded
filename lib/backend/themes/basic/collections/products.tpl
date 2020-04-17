{use class="yii\helpers\Html"}
<div class="page-header">
  <div class="page-title">
    <h3>{$app->controller->view->headingTitle}</h3>
  </div>
</div>
<form action="{Yii::$app->urlManager->createUrl('collections/products-update')}" method="post" id="collections_products" name="collections_products">
{Html::hiddenInput('collections_id', $cInfo->collections_id)}
<div class="xl-pr-box" id="box-xl-pr">
  <div class="attr-box-wrap after">
    <div class="attr-box attr-box-1">
      <div class="widget widget-attr-box box box-no-shadow" style="margin-bottom: 0;">
        <div class="widget-header">
          <h4>{$smarty.const.FIND_PRODUCTS}</h4>
          <div class="box-head-serch after">
            <input type="search" id="collection-search-by-products" placeholder="{$smarty.const.SEARCH_BY_ATTR}" class="form-control">
            <button onclick="return false"></button>
          </div>
        </div>
        <div class="widget-content">
          <select id="collection-search-products" size="25" style="width: 100%; height: 100%; border: none;" ondblclick="addSelectedCollection()">
          </select>
        </div>
      </div>
    </div>
    <div class="attr-box attr-box-2">
      <span class="btn btn-primary" onclick="addSelectedCollection()"></span>
    </div>
    <div class="attr-box attr-box-3">
      <div class="widget-new widget-attr-box box box-no-shadow" style="margin-bottom: 0;">
        <div class="widget-header">
          <h4>{$smarty.const.FIELDSET_ASSIGNED_PRODUCTS}</h4>
          <div class="box-head-serch after">
            <input type="search" id="search-collections-assigned" placeholder="{$smarty.const.SEARCH_BY_ATTR}" class="form-control">
            <button onclick="return false"></button>
          </div>
        </div>
        <div class="widget-content">
          <table class="table assig-attr-sub-table collection-products">
            <thead>
            <tr role="row">
              <th></th>
              <th>{$smarty.const.TEXT_IMG}</th>
              <th>{$smarty.const.TEXT_LABEL_NAME}</th>
              <!-- {*
              <th>{$smarty.const.TEXT_DISCOUNT}</th>
              <th>{$smarty.const.TEXT_PRICE}</th>
              *} -->
              <th></th>
            </tr>
            </thead>
            <tbody id="collections-assigned">
            {foreach $app->controller->view->collectionProducts as $eKey => $collection}
              {include file="new-product.tpl" collection=$collection}
            {/foreach}
            </tbody>
          </table>
          <input type="hidden" value="" name="collection_sort_order" id="collection_sort_order"/>
        </div>
      </div>
    </div>
  </div>
  <div class="widget box box-no-shadow collection-discount-box" style="margin-bottom: 0;">
    <div class="widget-header"><h4>{$smarty.const.BOX_COLLECTION_DISCOUNT}</h4></div>
    <div class="widget-content widget-inv" id="collection-discount-box">
    </div>
  </div>
</div>
<div class="btn-bar">
  <div class="btn-left"><a href="{Yii::$app->urlManager->createUrl(['collections/index', 'cID' => $cInfo->collections_id])}" class="btn btn-cancel-foot">Cancel</a></div>
  <div class="btn-right"><button class="btn btn-primary">Save</button></div>
</div>
</form>
 
<script type="text/javascript">
  function addSelectedCollection() {
    $( 'select#collection-search-products option:selected' ).each(function() {
      var products_id = $(this).val();
      if ( $('input[name="collection_products_id[]"][value="' + products_id + '"]').length ) {
        //already exist
      } else {
        $.post("{Yii::$app->urlManager->createUrl('collections/new-product')}", { 'products_id': products_id }, function(data, status) {
          if (status == "success") {
            $( ".collection-products tbody" ).append(data);
            updateCollectionDiscountBox();
          } else {
            alert("Request error.");
          }
        },"html");
      }
    });

    return false;
  }

  function deleteSelectedCollection(obj) {
    $(obj).parent().remove();
    updateCollectionDiscountBox();
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

  function updateCollectionDiscountBox() {
    $.post("{Yii::$app->urlManager->createUrl('collections/discount-box')}", $('#collections_products').serialize(), function(data, status){
      if (status == "success") {
        $( "#collection-discount-box" ).html(data);
      } else {
        alert("Request error.");
      }
    },"html");
  }

  $(document).ready(function() {
    $('#search-collections-assigned').on('focus keyup', { rows_selector: '#collections-assigned tr', text_selector: '.ast-name-collection'}, searchHighlightExisting);

    $('#collection-search-by-products').on('focus keyup', function(e) {
      var str = $(this).val();
      $.post( "{Yii::$app->urlManager->createUrl('collections/product-search')}?q="+encodeURIComponent(str), function( data ) {
        $( "select#collection-search-products" ).html( data );
        psearch = new RegExp(str, 'i');
        $.each($('select#collection-search-products').find('option'), function(i, e){
          if (psearch.test($(e).text())){
            phighlight(e, str);
          }
        });
      });
    }).keyup();

    $( ".collection-products tbody" ).sortable({
      handle: ".sort-pointer",
      axis: 'y',
      update: function( event, ui ) {
        var data = $(this).sortable('serialize', { attribute: "prefix" });
        $("#collection_sort_order").val(data);
      },
    }).disableSelection();

    updateCollectionDiscountBox();
  });
</script>