<style>
 .tCompetitor .icon-pencil{ display:inline-block; }
 .tCompetitor .del-pt{ padding-left:10px;display:inline-block; }
 .competitor-popup { width:650px; }
 .green{ background-color:#aff5af; }
 .red{ background-color:#ffbaba; }
 .yellow{ background-color:#feffba; }
 .competitor-box{ padding: 0 10px 35px 0;overflow: overlay;}
</style>
<div class="competitor-box">
    <table class="dataTable table tCompetitor" width="100%">
        <thead>
            <tr>
            {foreach $competitors_data['competitors_table_header'] as $header}
                <th >{$header}</th>
            {/foreach}
                <th ></th>
            </tr>
        </thead>
        <tbody>            
        </tbody>
    </table>

<div class="btn-bar edit-btn-bar">
    <div class="btn-left"><a href="{Yii::$app->urlManager->createUrl(['categories/edit-competitor-product', 'pID' => $pID])}" class="btn new-product-popup">{$smarty.const.IMAGE_NEW}</a></div>
    <div class="btn-right"><a href="{Yii::$app->urlManager->createUrl(['competitors/edit'])}" class="btn new-competitor-popup" >{$smarty.const.TEXT_CREATE_NEW_COMPETITOR}</a></div>
</div>
<div>
<script>
    var competitors = [];
    var cTable;
    
    colorize = function(){
        var nodes = cTable.fnGetNodes();
        if (nodes.length >0){
        var $_color;
            $.each(nodes, function(i, e){
                $_color = $(e).find('.color_price').data('class');
                if ($_color){
                    $(e).addClass($_color);
                }
            })
        }
    }
    
    function wrapMessage(str){
        alertMessage('<div class="widget box"><div class="widget-content">'+str+'</div><div class="noti-btn"><div><span class="btn btn-cancel">{$smarty.const.TEXT_OK}</span></div></div></div>');
    }
    
    updateCompetitorsList = function(){
        $.get('competitors/get-list', '', function(data){
            if (data.hasOwnProperty('competitors')){
                competitors = [];
                $.each(data.competitors, function(i, e){
                    competitors.push({ 'id': e.competitors_id, 'name':e.competitors_name, 'site':e.competitors_site, 'mask':e.competitors_mask, 'currency':e.competitors_currency });
                });
                if (competitors.length > 0){
                    $('.new-product-popup').off().popUp({ one_popup:false, box_class: 'popupEditCat'});
                } else {
                    $('.new-product-popup').off().click(function(e){
                        e.preventDefault();
                        wrapMessage('{$smarty.const.TEXT_ADD_NEW_COMPETITOR}');
                    })
                    
                }
                setTimeout(colorize(), 2000);
            }
        },'json');
    }

    $(document).ready(function(){
        cTable = $('.tCompetitor').dataTable({
            "paging":   false,
            "ordering": false,
            "processing": true,
            "serverSide": true,
            "ajax": {
                "type" : "GET",
                "url" : "{Yii::$app->urlManager->createUrl(['categories/competitor-products-list', 'pID' => $pID])}",
                "dataSrc": function ( json ) {
                    updateCompetitorsList();
                    return json.data;
                }
            }
        });       
    
        $('.new-competitor-popup').popUp({ one_popup:false, box_class: 'popupEditCat'});
        
        
        $('body').on('click', '.tCompetitor .del-pt', function(){
            var input = $(this).parents('tr').find('td:first input');
            if (input){
                var selected_id = $(input).val();
                if(selected_id){
                    bootbox.dialog({
                        message: "{$smarty.const.TEXT_DELETE_SELECTED}?",
                        title: "{$smarty.const.TEXT_DELETE_SELECTED}",
                        buttons: {
                            success: {
                                label: "{$smarty.const.TEXT_YES}",
                                className: "btn btn-primary",
                                callback: function() {
                                    $.post("{Yii::$app->urlManager->createUrl('categories/delete-competitor-product')}", { 'pcID' : selected_id }, function(data, status){
                                        if (status == "success") {
                                            cTable.fnDraw(false);
                                            updateCompetitorsList();                                            
                                        } else {
                                            alert("Request error.");
                                        }
                                    },"json");
                                }
                            },
                            cancel: {
                                label: "Cancel",
                                className: "btn-cancel",
                                callback: function() {
                                        //console.log("Primary button");
                                }
                            }                                
                        }
                    });
                }
            }            
        })
    })

</script>