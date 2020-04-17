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
<script>
/*
var selected_product;
var selected_product_name;
var tree;
(function($){

    {if !$promo->settings['searchsuggest']}
        tree = document.querySelector('ul[name=tree]');
        tree.options = [];
        tree.copy = [];
        $.each(tree.children, function(i, e){
            tree.options.push(e);
            tree.copy.push(e.innerHTML);
        });
    {/if}
        
        function seachText(text){
                $.each(tree.options, function(i, e){
                    if ($(e).hasClass('product_item')){ //e.className=
                        if (tree.copy[i].toLowerCase().indexOf(text.toLowerCase()) == -1 && text.length){
                            $(tree.options[i]).hide();
                        } else {
                            //tree.options[i].hidden = false;
                            $(tree.options[i]).show();
                            var string = tree.copy[i];
                            var pos = string.search(new RegExp(text, "i"));
                            if (text.length){
                                tree.options[i].innerHTML = string.substr(0, pos) + '<span style="background-color:#ebef16">' + string.substr(parseInt(pos),text.length) + '</span>' + string.substr(parseInt(pos)+parseInt(text.length));
                            } else {
                                tree.options[i].innerHTML = string;
                            }
                        }
                    }
                });        
        }        
        
        
        $('.search_product').click(function(e){
            if ((e.target.offsetWidth - e.offsetX) < e.target.offsetHeight){
                $('#search_text', this).val('');
                $('#search_text', this).trigger('keyup');
            }
        })
        
        $('#search_text').focus();
		$('#search_text').autocomplete({
			create: function(){
				$(this).data('ui-autocomplete')._renderItem = function( ul, item ) {
					return $( "<li></li>" )
						.data( "item.autocomplete", item )
						.append( "<a>"+(item.hasOwnProperty('image') && item.image.length>0?"<img src='" + item.image + "' align='left' width='25px' height='25px'>":'')+"<span>" + item.label + "</span>&nbsp;&nbsp;<span class='price'>"+item.price+"</span></a>")
						.appendTo( ul );
					};
			},
			source: function(request, response){
				if (request.term.length|| true){
                    {if $promo->settings['searchsuggest']}
                        
                    {else}
                        seachText(request.term);
                    {/if}
					
				} else {
                    {if !$promo->settings['searchsuggest']}
                    seachText('');
                    $.each(tree.options, function(i, e){
                            if (e.className == 'product_item'){
                                $(tree.options[i]).show();//.hidden = false;
                            }
                    });
                    {/if}
                }
			},
            minLength: 2,
            autoFocus: true,
            delay: 0,
            appendTo: '.auto-wrapp',
            select: function(event, ui) {
				//$("#search_text").val(ui.item.label);
				if (ui.item.id > 0){
					$('.product_name').html(ui.item.label)
                    //loadProduct(ui.item.id);					
				}                 
			},
        }).focus(function () {
			$('#search_text').autocomplete("search");  
        });
        
        {if !$promo->settings['searchsuggest']}
        $('input[name=search]').keyup(function(){
            if (!$(this).val().length){            
                seachText('');
                $.each(tree.options, function(i, e){
                    $(tree.options[i]).show();
                    if (e.className == 'product_item'){
                        //tree.options[i].hidden = false;
                        if (tree.options[i].innerHTML != tree.copy[i])
                            tree.options[i].innerHTML = tree.copy[i];
                    }
                });   
            }
        })
        {/if}
		
})(jQuery);*/
</script>