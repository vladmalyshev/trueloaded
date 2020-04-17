<div class="search-products" style="overflow: overlay; " id="element-search-products">
    <ul name="tree" size="20" style="width: 100%;height: 500px;list-style: none; padding:0;">
    {foreach $promo->settings['tree'] as $key => $value}
        {if $value['category'] eq 1}
            {assign var="parent" value="cat_{$value['id']}"}
            <li id="{$value['id']}" value="cat_{$value['id']}" class="category_item {if $value['status'] eq 0}dis_prod{/if}" level="{$value['level']}" {if $promo->settings['disable_categories']}disabled="disabled"{/if}>{$value['text']}</li>
            {$first = false}
        {else}
            <li id="{$value['id']}" value="prod_{$value['id']}" parent="cat_{$value['parent_id']}" class="product_item  {if $value['status'] eq 0}dis_prod{/if}">{$value['text']}</li>
        {/if}
    {/foreach}
    </ul>
</div>


<script>
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
        
        /*function loadProduct(id){
            $.get("{$app->urlManager->createUrl('orders/addproduct')}", {
                            'products_id':id,
                            'orders_id':"{$params['oID']}"
                        }, function (data, status){
                            $('.product_holder').html(data).show();                            
            }, 'html');        
            
        } */   
        
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
                        /*$.get("{Yii::$app->urlManager->createUrl('orders/addproduct')}", {
                            'search':request.term,
                            'orders_id':"{$params['oID']}",
                        }, function(data){
                            response($.map(data, function(item, i) {
                                return {
                                        values: item.text,
                                        label: item.text,
                                        id: parseInt(item.id),
                                        image:item.image,
                                        price:item.price,
                                    };
                                }));
                        },'json');*/
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
		
})(jQuery);
</script>