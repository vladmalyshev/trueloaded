{use class="yii\helpers\Html"}
<link href="{$app->request->baseUrl}/plugins/fancytree/skin-bootstrap/ui.fancytree.min.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{$app->request->baseUrl}/plugins/fancytree/jquery.fancytree-all.min.js"></script>
<style>
    #manufacturers_tree li{
        height:30px;
    }
</style>
<div>
    <div class="tabbable tabbable-custom tab-content">
          <ul class="nav nav-tabs">
            <li class="active"><a href="#categories_tree" data-toggle="tab"><span>{$smarty.const.TABLE_HEADING_CATEGORIES_PRODUCTS}</span></a></li>
            <li><a href="#manufacturers_tree" data-toggle="tab"><span>{$smarty.const.TABLE_HEADING_MANUFACTURERS}</span></a></li>
			<li><a href="#countries_tree" data-toggle="tab"><span>{$smarty.const.BOX_TAXES_COUNTRIES}</span></a></li>
			<li><a href="#zones_tree" data-toggle="tab"><span>{$smarty.const.BOX_GEO_ZONES}</span></a></li>
          </ul>
          <div class="tab-content">
            <div class="tab-pane active" id="categories_tree">
               <div id="tree" style="height: 410px;overflow: auto;">
                <ul>
                  {foreach $promo->settings['categories_tree'] as $tree_item }
                  <li class="{if $tree_item.lazy}lazy {/if}{if $tree_item.folder}folder {/if}{if $tree_item.selected}selected {/if}" id="{$tree_item.categories_id}">{$tree_item.title}</li>
                  {/foreach}
                </ul>
              </div>
            </div>
            <div class="tab-pane" id="manufacturers_tree">
			  {Html::dropDownList('man_id[]', $promo->settings['selected_manufacturers'], $promo->settings['manufacturers_tree'], ['encode'=> false, 'multiple' => 'multiple', 'data-role' => 'multiselect-radio', 'class' => 'form-control', 'id' => 'manu-list'])}
            </div>
            
            <div class="tab-pane" id="countries_tree">
                <div style="margin:5px;">
					{Html::dropDownList('countries[]', $promo->settings['selected_countries'], $promo->settings['countries_tree'], ['encode'=> false, 'multiple' => 'multiple', 'class' => 'form-control', 'id' => 'countries-list'])}
                </div>
            </div>
			<div class="tab-pane" id="zones_tree">
                <div style="margin:5px;">
					{Html::dropDownList('zones[]', $promo->settings['selected_zones'], $promo->settings['zones_tree'], ['encode'=> false, 'multiple' => 'multiple', 'class' => 'form-control', 'id' => 'zones-list'])}
                </div>
            </div>
          </div>
    </div>    
</div>

<script type="text/javascript">
  var tree_data = {json_encode($promo->settings["categories_tree"])};
  var selected_data = {$promo->settings["selected_data"]};
    
	$('#countries-list, #manu-list, #zones-list').multipleSelect({
		multiple: true,
		filter: true,
	});
  $('#tree').fancytree({
    extensions: ["glyph"],
    checkbox:true,
    lazyLoad: function(event, data){
    
      data.result = {
        url: 'promotions/observe',
        type: 'POST',
        data:{ 
            'action' : 'loadTree', 
            'params' : {
                  'do':'missing_lazy',
                  'id':data.node.key,
                  'selected':data.node.selected?1:0,
                  'selected_data': Object.values(selected_data),
                  'platform_id': '{$promo->settings["platform_id"]}',
                } , 
            'promo_class': '{$promo_class}' 
            },
        dataType: "json"
      };
    },
    _postProcess: function(event, data) {
      if (data.response.tree_data) {
        data.response = data.response.tree_data;
      }else{
        data.response = [];
      }
	  console.log(data);
    },
    select:function(event, data) {
      var tree = $('#tree').fancytree('getTree');
      if ( tree.lock ) return false;
      tree.lock = true;
      var node_key = data.node.key,
          node_selected = data.node.selected;
      if ( node_selected ) {
        if ( selected_data.indexOf(node_key) == -1 ) selected_data.push(node_key);
      }else{
        if ( selected_data.indexOf(node_key) != -1 ) { selected_data.splice(selected_data.indexOf(node_key),1); }
      }

      $.ajax({
        url: 'promotions/observe',
        async: false,
        type: 'POST',
        dataType: 'json',
        data: { 
            'action' : 'loadTree', 
            'params' : {
                  'do':'update_selected',
                  'id':node_key,
                  'selected': node_selected ? 1 : 0,
                  'select_children' : true,
                  'selected_data': Object.values(selected_data),
                  'platform_id': '{$promo->settings["platform_id"]}',
                } , 
            'promo_class': '{$promo_class}' 
            },
        success: function (data) {
        
          if ( data.selected_data ) {
            selected_data = data.selected_data;
          }
          
          if ( data.update_selection ) {
            for( var key in data.update_selection ) {            
              if ( !data.update_selection.hasOwnProperty(key) ) continue;              
              var updateNode = tree.getNodeByKey(key);
			  console.log(key, updateNode);
              if ( updateNode ) {
                updateNode.setSelected(!!data.update_selection[key]);
              }
            }
          }
          tree.lock = false;
        }
      });
    },
    glyph: {
        map: getGlyphsMap()
    }
  });
  
  function getGlyphsMap(){
    return {
        doc: "icon-cubes",//"fa fa-file-o",
        docOpen: "icon-cubes", //"fa fa-file-o",
        checkbox: "icon-check-empty",// "fa fa-square-o",
        checkboxSelected: "icon-check",// "fa fa-check-square-o",
        checkboxUnknown: "icon-check-empty", //"fa fa-square",
        dragHelper: "fa fa-arrow-right",
        dropMarker: "fa fa-long-arrow-right",
        error: "fa fa-warning",
        expanderClosed: "icon-expand", //"fa fa-caret-right",
        expanderLazy: "icon-plus-sign-alt", //"icon-expand-alt", //"fa fa-angle-right",
        expanderOpen: "icon-minus-sign-alt",//"fa fa-caret-down",
        folder: "icon-folder-close-alt",//"fa fa-folder-o",
        folderOpen: "icon-folder-open-alt",//"fa fa-folder-open-o",
        loading: "icon-spinner" //"fa fa-spinner fa-pulse"
      }
  }
  
</script>