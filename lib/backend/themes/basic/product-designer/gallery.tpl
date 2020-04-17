{use class="common\extensions\ProductDesigner\widgets\GalleryArea"}
{use class="common\extensions\ProductDesigner\widgets\UploadArea"}
{use class="yii\helpers\Html"}
<div>
    <div class="col-md-6">
        <div class="widget box">
            <div class="widget-header">
                <h4>Common Gallery</h4>
            </div>
            <div class="widget-content">
                <div class="search-logo" style="width:100%;">
                    {Html::textInput('search_common', '', ['class' => 'form-control' ,'placeholder' => 'Find Logo'])}
                </div>
                <div class="common-gallery gallery" style="display:grid;" data-gid="0">
                    {GalleryArea::widget(['gallery' => $common])}
                </div>
                <div class="common-uploading">
                    {UploadArea::widget(['galleryId' => 0, 'jsCallback' => 'reloadGalleryArea(0);'])}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="widget box">
            <div class="widget-header">
                <h4>Private Gallery</h4>
            </div>
            <div class="widget-content">
                <div class="search-customer auto-wrapp" style="width:100%;">
                    {Html::textInput('gallery_customer', $gallery_customer, ['class' => 'form-control' ,'placeholder' => 'Find Customer'])}
                    <div class="toolbar no-padding" style="position:absolute;top: 0px;right: 0px;">
                        <div class="group">
                            <span class="btn unlink-customer" title="{$smarty.const.TEXT_REMOVE}"><i class="icon-trash"></i></span>
                        </div>
                    </div>
                </div>
                <div class="private-gallery gallery" style="display:grid;" data-gid="{$private->getPrivateGalleryId()}">
                    {GalleryArea::widget(['gallery' => $private])}
                </div>
                <div class="private-uploading">
                    {UploadArea::widget(['galleryId' => $private->getPrivateGalleryId(), 'jsCallback' => 'reloadGalleryArea(0);', 'canUpload' => $canModify])}
                </div>
            </div>
        </div>
    </div>
    <script>
    window.reloadGalleryArea = function(gid){
        $.get('{$app->urlManager->createUrl('product-designer/load-gallery')}', { 'gid' : gid } , function(html){
            if (!isNaN(gid)){
                $('.gallery[data-gid="'+gid+'"]').html(html);
                setDraggable($('.common-gallery .gallery-area'), $('.private-gallery .gallery-area .item'));
            } else {
                $('.private-gallery').html(html);
            }
        })
    }
    
    window.reloadPrivateUpload = function(){
        $.get('{$app->urlManager->createUrl('product-designer/private-upload')}', { } , function(html){
            $('.private-uploading').html(html);
        })
    }
    
    setDraggable = function(droppableSelector, draggableSelector){
        draggableSelector.draggable({
            helper: 'clone',
        });
        droppableSelector.droppable({
            accept: draggableSelector,
            drop: function( event, ui ){
                if ( ui.draggable && confirm('Do you really want to move this image')){
                    var logoid = $(ui.draggable).data('logoid');
                    if (logoid){
                        $.getJSON('{$app->urlManager->createUrl('product-designer/cross-logo')}', { 'to': 'common', 'logoid': logoid } , function(data){
                            if (data.hasOwnProperty('error') && !data.error){
                                reloadGalleryArea(data.gid);
                                reloadGalleryArea(0);
                            } else {
                                alert(data.message);
                            }
                        })
                    }
                }
            }
        });
    }
    
    $(document).ready(function(){
        $('.search-customer .unlink-customer').click(function(){
            $.getJSON('{$app->urlManager->createUrl('product-designer/unlink-customer')}', { } , function(data){
                reloadGalleryArea();
                reloadPrivateUpload();
                $('input[name="gallery_customer"]').val('');
            })
        })
        
        setDraggable($('.common-gallery .gallery-area'), $('.private-gallery .gallery-area .item'));
        
        $('input[name="gallery_customer"]').autocomplete({
            create: function(){
                $(this).data('ui-autocomplete')._renderItem = function( ul, item ) {
                    return $( "<li></li>" )
                        .data( "item.autocomplete", item )	
                        .append( "<a><span>" + item.label + "</span></a>")
                        .appendTo( ul );
                    };
            },
            source: function(request, response){
                $.post('{$app->urlManager->createUrl('product-designer/search-customer')}', {
                    'search': request.term,
                }, function(data){
                    response($.map(data, function(item, i) {
                        return {
                                values: item.text,
                                label: item.text,
                                id: parseInt(item.id),
                                gid: parseInt(item.gid),
                            };
                        }));
                }, 'json');
            },
            minLength: 2,
            autoFocus: true,
            delay: 0,
            appendTo: '.auto-wrapp',
            select: function(event, ui) {
                $.getJSON('{$app->urlManager->createUrl('product-designer/assign-customer')}', { 'cid' : ui.item.id } , function(data){
                    if (data.hasOwnProperty('error') && !data.error){
                        $('.private-gallery.gallery').attr('data-gid', ui.item.gid);
                        reloadGalleryArea(ui.item.gid);
                        reloadPrivateUpload();
                    }
                })
            },
        })
        
        $('input[name="search_common"]').keyup(function(e){
            var key = e.target.value;
            var r = new RegExp(key);
            $.each($('.common-gallery .gallery-area .item'), function(i, item){
                if ($('.name', item).text().search(r) != -1 || $('.filename', item).text().search(r) != -1){
                    $(item).show();
                } else {
                    $(item).hide();
                }
            })
        })
    })
    </script>
</div>