<style>
    .icons_gallery{
        width: 500px;
        height: 110px;
        overflow-y: scroll;
        border: 1px solid #ccc;
        padding: 5px;
        margin-bottom: 10px;
    }
    .gallery-icon-item{ display:inline-block; }
    .gallery-icon-item.selected{
        border: 1px solid #0000ff;
    }
</style>
{use class="yii\helpers\Url"}
 <div class="popup-heading">{$smarty.const.TEXT_GALLERY_IMAGE}</div>
 <div class="popup-content pop-mess-cont">
    <div class="icons_gallery">
        {foreach $gallery as $_image}
            <img src="{$wspath|cat:$_image}" data-file="{$_image}" style="max-width:{$max_width}px;max-height:{$max_height}px;" class="gallery-icon-item">
        {/foreach}
    </div>

    <form action="" id="add-icon">
 
    <div class="setting-row setting-row-image">
      <label for="">{$smarty.const.TEXT_PAGE_NAME}</label>
        <div class="image-upload">
          <div class="upload" data-name="setting[promo_icon]"></div>
          <br/>
          <button class="btn btn-default confirm-upoload">Confirm</button>
          <script type="text/javascript">
            var selected_icon, path = "{$wspath}";
            $('.upload').uploads().on('upload', function(e){

              var img = $('.dz-image-preview img', this).attr('src');
              $('.demo-box').css('background-image', 'url("'+img+'")')
            });

            $(function(){
              $('.setting-row-image .image > img').each(function(){
                var img = $(this).attr('src');
                $('.demo-box').css('background-image', 'url("'+img+'")');

                $('input[name="setting[promo_icon]"]').val('{$setting.promo_icon}');
              });

              $('.setting-row-image .image .remove-img').on('click', function(){
                $('input[name="setting[promo_icon]"]').val('');
                $('.setting-row-image .image').remove()
              });
              
              $('.confirm-upoload').click(function(e){
                e.preventDefault();
                if ($('input[name="setting[promo_icon]"]').val().length > 0){
                    $.post("{Url::to(['promotions/icons'])}", {
                        'file': $('input[name="setting[promo_icon]"]').val()
                    }, function(data){
                        if (data.hasOwnProperty('file')){
                            $('.setting-row-image .upload-remove').trigger('click');
                            $('.icons_gallery').append('<img src="' + data.filepath + '" data-file="'+data.file+'" style="max-width:{$max_width}px;max-height:{$max_height}px;" class="gallery-icon-item">');
                        }
                    },"json");
                }
              });
              
            $('body').on('click', '.gallery-icon-item', function(){
                var that = this;
                $('.gallery-icon-item').removeClass('selected');
                $(that).addClass('selected');
                selected_icon = $(that).data('file');
            });
            
            var selectIcon = function(){
                if (typeof selected_icon == 'undefined' || selected_icon.length == 0){
                    bootbox.alert('{$smarty.const.ICON_UNSELECTED}');
                } else {
                    $('.current_icon').html('<img src="' + path + selected_icon + '" style="max-width:{$max_width}px;max-height:{$max_height}px;"><input type="hidden" name="promo_icon" value="'+selected_icon+'">');
                    $('.pop-up-close:last').trigger('click');
                }
            }
            
            $('.btn-select-icon').click(function(e){
                e.preventDefault();
                selectIcon();
            });
            
            $('body').on('dblclick', '.gallery-icon-item', function(e){
                e.preventDefault();
                selectIcon();
            })

            });

          </script>
        </div>

    </div>
    

  
    <div class="noti-btn">
        <div><span class="btn btn-cancel">{$smarty.const.IMAGE_CANCEL}</span></div>
        <div><button type="submit" class="btn btn-primary btn-save btn-select-icon">{$smarty.const.TEXT_BTN_OK}</button></div>
    </div>
    </form>
  
 </div>  
