{use class="yii\helpers\Html"}
<link href="{$app->request->baseUrl}/plugins/fancytree/skin-bootstrap/ui.fancytree.min.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{$app->request->baseUrl}/plugins/fancytree/jquery.fancytree-all.min.js"></script>
<style>
    #manufacturers_tree li{
        height:30px;
    }
</style>
<div >
    <div class="our-pr-line after">
    <label>Minimal quantity</label>
    {Html::textInput('promo_quantity', $promo->settings['qty'], ['class' => 'form-control'])}
    </div>
    <div class="tabbable tabbable-custom tab-content">
          <ul class="nav nav-tabs">
            <li class="active"><a href="#categories_tree" data-toggle="tab"><span>Categories</span></a></li>
            <li><a href="#manufacturers_tree" data-toggle="tab"><span>Manufacturers</span></a></li>
            {if $promo->useProperties}
            <li><a href="#properties_tree" data-toggle="tab"><span>Properties</span></a></li>
            {/if}
          </ul>
          <div class="tab-content">
            <div class="tab-pane active" id="categories_tree">
                {*<ul name="tree" size="20" style="width: 100%;list-style: none; padding:0 15px 0 15px;">
                {foreach $promo->settings['categories_tree'] as $key => $value}
                    {if $value['desc'] eq 'cat'}
                        {assign var="parent" value="cat_{$value['id']}"}
                        <li id="{$value['id']}" value="cat_{$value['id']}" class="product_item {if $value['status'] eq 0}dis_prod{/if}" parent_id="{$value['parent_id']}" {if $promo->settings['disable_categories']}disabled="disabled"{/if}>
                        {assign var = checked value =false}
                        {if in_array($value['id'], $promo->settings['assigned_items']['cat'])}
                            {$checked = true}
                        {else}
                            {$checked = false}
                        {/if}
                        {Html::checkbox('cat_id[]', $checked, ['value' => $value['id']])}&nbsp;{$value['text']}
                        </li>
                    {/if}
                {/foreach}
                </ul>*}
               <div id="tree" style="height: 410px;overflow: auto;">
                <ul>
                  {foreach $promo->settings['categories_tree'] as $tree_item }
                  <li class="{if $tree_item.lazy}lazy {/if}{if $tree_item.folder}folder {/if}{if $tree_item.selected}selected {/if}" id="{$tree_item.categories_id}">{$tree_item.title}</li>
                  {/foreach}
                </ul>
              </div>
            </div>
            <div class="tab-pane" id="manufacturers_tree">
                <div style="margin:5px;">
                    {Html::input('text', 'search', '', ['class' => 'form-control', 'id'=>'man-search', 'placeholder' => $smarty.const.IMAGE_SEARCH])}
                </div>
              <ul name="tree" size="20" style="width: 100%;height: 500px;list-style: none; padding:0 0 0 15px; overflow-y: scroll;">
                {foreach $promo->settings['manufacturers_tree'] as $key => $value}
                    <li id="{$value['id']}" value="man_{$value['id']}">
                    {assign var = checked value =false}
                    {if in_array($value['id'], $promo->settings['selected_manufacturers'])}
                        {$checked = true}
                    {else}
                        {$checked = false}
                    {/if}
                    {Html::checkbox('man_id[]', $checked, ['value' => $value['id'], 'class' => 'manufacturers_item'])}&nbsp;{$value['text']}</li>
                {/foreach}
              </ul>
            </div>
            {if $promo->useProperties}
            <div class="tab-pane" id="properties_tree">
                <div id="ptree" style="height: 410px;overflow: auto;">
                </div>
                <div style="display:none" class="properties_box"></div>
            </div>
            {/if}
          </div>
    </div>    
</div>

<script type="text/javascript">
  var tree_data = {json_encode($promo->settings["categories_tree"])};
  var selected_data = {$promo->settings["selected_data"]};
  
  {if $promo->settings["properties_tree"]}
    $('#ptree').fancytree({
        extensions: ["glyph"],
        checkbox:true,
        source: {$promo->settings["properties_tree"]},
        click:function(event, data) {
            if (data.node.children && data.node.children.length>0){
                if (!data.node.selected){
                    data.options.nodeStatus(data.node.children, true);
                } else {
                    data.options.nodeStatus(data.node.children, false);
                }
            }
        },
        select:function(event, data) {
            setProps();
        },
        glyph: {
            map: getGlyphsMap()
        },
        nodeStatus: function(node, status){
            $.each(node, function(i, e){
                e.setSelected(status);
            })
        }
    });
      
    setProps();
      
    function setProps(){
        var pr = $('#ptree').fancytree('getTree');
        $('.properties_box').html('');
        var selected = pr.getSelectedNodes();
        $.each(selected, function (i, e){
            $('.properties_box').append("<input type='hidden' name='properties[]'  value='"+e.key+"'>")
        });
    }
  {/if}
  
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
  
  $('.manufacturers_item').bootstrapSwitch({
        onText: "{$smarty.const.SW_ON}",
        offText: "{$smarty.const.SW_OFF}",
        handleWidth: '20px',
        labelWidth: '24px'
    });
    $('#man-search').keyup(function(e){
        var searchString = $(this).val();
        if (searchString.length > 0 ){
            $("ul[name=tree] li").each(function(index, li) {
                var currentName = $(li).text();
                if( currentName.toUpperCase().indexOf(searchString.toUpperCase()) == -1) {
                   $(li).hide();
                } else {
                   $(li).show();
                }
                
            });
        } else {
            $("ul[name=tree] li").show();
        }
    })
</script>
</script>