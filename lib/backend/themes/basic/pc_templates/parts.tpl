{use class="yii\helpers\Html"}
<div class="page-header">
  <div class="page-title">
    <h3>{$app->controller->view->headingTitle}</h3>
  </div>
</div>
<form action="{Yii::$app->urlManager->createUrl('pc_templates/parts-update')}" method="post" id="pctemplates_parts" name="pctemplates_parts">
{Html::hiddenInput('pctemplates_id', $tInfo->pctemplates_id)}
{foreach $app->controller->view->elementsArray as $elements_id => $element}
<div class="widget box box-no-shadow js-element" data-element-id="{$elements_id}">
  <div class="widget-header">
    <h4>{$element['elements_name']} ({if $element['is_mandatory']} {$smarty.const.TEXT_MANDATORY_ELEMENT} {else} {$smarty.const.TEXT_OPTIONAL_ELEMENT} {/if})</h4>
    <div class="toolbar no-padding">
      <div class="btn-group">
        <span class="btn btn-xs widget-collapse"><i class="icon-angle-down"></i></span>
      </div>
    </div>
  </div>
  <div class="widget-content">

    <div class="xl-pr-box" id="box-xl-pr">
      <div class="after">
        <div class="attr-box attr-box-1">
          <div class="widget widget-attr-box box box-no-shadow" style="margin-bottom: 0;">
            <div class="widget-header">
              <h4>{$smarty.const.FIND_PRODUCTS}</h4>
              <div class="box-head-serch after">
                <input type="search" id="pctemplate-{$elements_id}-search-by-products" placeholder="{$smarty.const.SEARCH_BY_ATTR}" class="form-control">
                <button onclick="return false"></button>
              </div>
            </div>
            <div class="widget-content">
              <select id="pctemplate-{$elements_id}-search-products" size="25" style="width: 100%; height: 100%; border: none;" ondblclick="addSelectedPoduct{$elements_id}()" multiple="">
              </select>
            </div>
          </div>
        </div>
        <div class="attr-box attr-box-2">
          <span class="btn btn-primary" onclick="addSelectedPoduct{$elements_id}()"></span>
        </div>
        <div class="attr-box attr-box-3">
          <div class="widget-new widget-attr-box box box-no-shadow" style="margin-bottom: 0;">
            <div class="widget-header">
              <h4>{$smarty.const.FIELDSET_ASSIGNED_PRODUCTS}</h4>
              <div class="box-head-serch after">
                <input type="search" id="search-{$elements_id}-assigned" placeholder="{$smarty.const.SEARCH_BY_ATTR}" class="form-control">
                <button onclick="return false"></button>
              </div>
            </div>
            <div class="widget-content">
              <table class="table assig-attr-sub-table pctemplate-{$elements_id}-products">
                <thead>
                <tr role="row">
                  <th></th>
                  <th>{$smarty.const.TEXT_IMG}</th>
                  <th>{$smarty.const.TEXT_LABEL_NAME}</th>
                  <th>{$smarty.const.TEXT_QTY_MIN}</th>
                  <th>{$smarty.const.TEXT_QTY_MAX}</th>
                  <th>
                      {$smarty.const.TEXT_DEFAULT}<br>
                      <input type="radio" name="pctemplate_{$elements_id}_def" value="0" {if $element['def'] == 0}checked{/if} class="pctemplate_def" title="{$smarty.const.TEXT_DEFAULT}">
                  </th>
                  <th></th>
                </tr>
                </thead>
                <tbody id="pctemplate-{$elements_id}-assigned">
                {foreach $element['products'] as $eKey => $product}
                  {include file="new-product.tpl" elements_id={$elements_id} product=$product}
                {/foreach}
                </tbody>
              </table>
              <input type="hidden" value="" name="pctemplate_{$elements_id}_sort_order" id="pctemplate_{$elements_id}_sort_order"/>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
<script type="text/javascript">
  function addSelectedPoduct{$elements_id}() {
    var ids = '';
    $( 'select#pctemplate-{$elements_id}-search-products option:selected' ).each(function() {
      var products_id = $(this).val();
      if ( $('input[name="pctemplate_{$elements_id}_products_id[]"][value="' + products_id + '"]').length ) {
        //already exist
      } else {
        ids = ids + products_id + ',';
      }
    });
    if (ids != '') {
      $.post("{Yii::$app->urlManager->createUrl('pc_templates/new-product')}", { 'elements_id': {$elements_id}, 'products_ids': ids }, function(data, status) {
        if (status == "success") {
          $( ".pctemplate-{$elements_id}-products tbody" ).append(data);

          $('.pctemplate_def').bootstrapSwitch({
            onText: "{$smarty.const.SW_ON}",
            offText: "{$smarty.const.SW_OFF}",
            handleWidth: '20px',
            labelWidth: '24px',
          });
        } else {
          alert("Request error.");
        }
      },"html");
    }
    return false;
  }

  function deleteSelectedProduct(obj) {
    $(obj).parent().remove();
    return false;
  }

  var color = '#ff0000';
  var phighlight = function(obj, reg){
    if (reg.length == 0) return;
    $(obj).html($(obj).text().replace( new RegExp( "(" +  reg  + ")" , 'gi' ), '<font style="color:'+color+'">$1</font>'));
    return;
  }

  var searchHighlightExisting{$elements_id} = function(e){
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
    $('#search-{$elements_id}-assigned').on('focus keyup', { rows_selector: '#pctemplate-{$elements_id}-assigned tr', text_selector: '.ast-name-element'}, searchHighlightExisting{$elements_id});

    $('#pctemplate-{$elements_id}-search-by-products').on('focus keyup', function(e) {
      var str = $(this).val();
      $.post( "{Yii::$app->urlManager->createUrl('pc_templates/product-search')}?elements_id={$elements_id}&q="+encodeURIComponent(str), function( data ) {
        $( "select#pctemplate-{$elements_id}-search-products" ).html( data );
        psearch = new RegExp(str, 'i');
        $.each($('select#pctemplate-{$elements_id}-search-products').find('option'), function(i, e){
          if (psearch.test($(e).text())){
            phighlight(e, str);
          }
        });
      });
    }).keyup();

    $( ".pctemplate-{$elements_id}-products tbody" ).sortable({
      handle: ".sort-pointer",
      axis: 'y',
      update: function( event, ui ) {
        var data = $(this).sortable('serialize', { attribute: "prefix" });
        $("#pctemplate_{$elements_id}_sort_order").val(data);
      },
    }).disableSelection();

  
    $('.pctemplate_def').bootstrapSwitch({
      onText: "{$smarty.const.SW_ON}",
      offText: "{$smarty.const.SW_OFF}",
      handleWidth: '20px',
      labelWidth: '24px',
    });
  });
</script>
{/foreach}
<div class="btn-bar">
  <div class="btn-left"><a href="{Yii::$app->urlManager->createUrl(['pc_templates/index', 'tID' => $tInfo->pctemplates_id])}" class="btn btn-cancel-foot">Cancel</a></div>
  <div class="btn-right"><button class="btn btn-primary">Save</button></div>
</div>
</form>
