<link href="{$app->request->baseUrl}/plugins/fancytree/skin-bootstrap/ui.fancytree.min.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{$app->request->baseUrl}/plugins/fancytree/jquery.fancytree-all.min.js"></script>
<div class="search-products" style="overflow: overlay; " id="element-search-products">
    <div id="tree" style="height: 410px;overflow: auto;">
        {*<ul>
          {foreach $promo->settings['categories_tree'] as $tree_item }
          <li class="{if $tree_item.lazy}lazy {/if}{if $tree_item.folder}folder {/if}{if $tree_item.selected}selected {/if}" id="{$tree_item.key}">{$tree_item.title}</li>
          {/foreach}
        </ul>*}
      </div>
</div>

<script type="text/javascript">
  var tree_data = {json_encode($promo->settings["categories_tree"])};  
  var item;
  var fTree = $('#tree').fancytree({
    extensions: ["glyph", "filter"],
    checkbox:false,
    filter: {  // override default settings
        counter: false, // No counter badges
        mode: "hide"  // "dimm": Grayout unmatched nodes, "hide": remove unmatched nodes
    },
    source: tree_data,
    _postProcess: function(event, data) {
      if (data.response.tree_data) {
        data.response = data.response.tree_data;
      }else{
        data.response = [];
      }
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
      map: {
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
    },    
    dblclick : function(event, data){
        addSelectedElement(data.node.key);
    }
  });
  
  $('#search_text').keyup(function(){
  
    var value = $(this).val();
    if (value.length){
        $(fTree).fancytree('getTree').filterNodes(value, { mode: 'hide', autoExpand: true, leavesOnly: true });
    }
  })
</script>