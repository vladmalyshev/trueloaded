{use class="\yii\helpers\Html"}
<div>
<style>
 .groups-list label{ display:block; }
 .auto-wrapp{ width:auto; }
 .cuts-holder ul{ list-style: none;padding: 5px; }
</style>
<div class="popup-heading">{$smarty.const.TEXT_PERSONALIZATION}</div>
    {Html::beginForm('promotions/personalize', 'post', ['id' => 'promo-person'])}
    <div style="padding:10px;">        
            <div class="tabbable tabbable-custom widget-content">
                <ul class="nav nav-tabs">
                    <li class="active"><a href="#tab_groups" data-toggle="tab"><span>{$smarty.const.TABLE_HEADING_GROUPS}</span></a></li>
                    <li><a href="#tab_customers" data-toggle="tab"><span>{$smarty.const.TABLE_HEADING_CUSTOMERS}</span></a></li>                    
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="tab_groups">
                        <div class="groups-list" style="min-height:300px;overflow-y: scroll;">
                        {Html::checkboxList('promo_groups', $groups['selected'], $groups['full'], ['class' => ''])}
                        </div>
                    </div>
                    <div class="tab-pane" id="tab_customers">
                        <div class="up-box" style="min-height:300px;overflow-y: scroll;">
                            <div class="search-fields">
                                <label>{$smarty.const.TEXT_SEARCH_CUSTOMER}</label>
                            </div>
                            <div class="search-box auto-wrapp">
                                {Html::textInput('search_customer', '', ['placeholder' => 'Type to find customer', 'class' => 'form-control'])}                                
                            </div>
                            <div class="cuts-holder">
                                <ul>
                                    {if $customers}
                                        {foreach $customers as $customer}
                                        <li><input type="hidden" name='promo_customers[]' value='{$customer.id}'>{$customer.text}&nbsp;<span class='del-pt'></span></li>
                                        {/foreach}
                                    {/if}
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </div>
    <div class="note-block noti-btn">
      <div class="btn-left"><button class="btn btn-cancel">{$smarty.const.IMAGE_CANCEL}</button></div>
      <div class="btn-right"><button class="btn btn-primary">{$smarty.const.IMAGE_SAVE}</button></div>
    </div>
    {Html::endForm()}
    <script>
    (function($){
        $('#promo-person').submit(function(){
            $.post('{\yii\helpers\Url::to(["promotions/personalize", "promo_id" => $promo_id])}', $('#promo-person').serializeArray(), function(){
                $('.pop-up-close:last').trigger('click');
            }, 'json');
            return false;
        })
    
        $('input[name=search_customer]').autocomplete({
			create: function(){
				$(this).data('ui-autocomplete')._renderItem = function( ul, item ) {
					return $( "<li></li>" )
						.data( "item.autocomplete", item )	
                        .append( "<a><span>" + item.label + "</span></a>")                        
						.appendTo( ul );
					};
			},
			source: function(request, response){
				if (request.term.length > 2){
                    
                    $.post('promotions/search-customer', {
                        'search': request.term,
                    }, function(data){
                        response($.map(data, function(item, i) {
                            return {
                                    values: item.text,
                                    label: item.text,
                                    id: parseInt(item.id),                                    
                                };
                            }));
                    }, 'json');
				} else {
                    $('.btn-choose-customer').attr('disabled', 'disabled');
                }
			},
            minLength: 2,
            autoFocus: true,
            delay: 0,
            appendTo: '.auto-wrapp',
            select: function(event, ui) {
				if (ui.item.id > 0){
                    $('.cuts-holder ul').append("<li><input type=hidden name='promo_customers[]' value='"+ui.item.id+"'>"+ui.item.label+"&nbsp;<span class='del-pt'></span></li>");
				}                 
			},
        })
        
        $('.cuts-holder').on('click', '.del-pt', function(){
            $(this).closest('li').remove();
        })                
    })(jQuery)
    </script>    
</div>