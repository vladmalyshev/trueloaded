
{use class="backend\assets\BannersAsset"}
{BannersAsset::register($this)|void}

<form action="" class="banner-group-form">
<div class="row">
    <div class="col-md-1"><label for="">{$smarty.const.TABLE_TEXT_NAME}</label></div>
    <div class="col-md-2">
        <input type="hidden" name="banners_group" value="{$groupName}"/>
        <input type="text" name="name" value="{$groupName}" class="form-control"/>
    </div>
</div>

<div class="banner-groups-table">

<table class="table table-bordered">
    <thead>
    <tr>
        <th>{$smarty.const.WINDOW_SIZE_FROM}</th>
        <th>{$smarty.const.WINDOW_SIZE_TO}</th>
        <th>{$smarty.const.IMAGE_WIDTH}</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    {foreach $groupSizes as $size}
    <tr>
        <td>
            <input type="hidden" name="id[]" value="{$size.id}"/>
            <input type="text" name="width_from[]" value="{if $size.width_from}{$size.width_from}{/if}" class="form-control"/>
        </td>
        <td>
            <input type="text" name="width_to[]" value="{if $size.width_to}{$size.width_to}{/if}" class="form-control"/>
        </td>
        <td>
            <input type="text" name="image_width[]" value="{if $size.image_width}{$size.image_width}{/if}" class="form-control"/>
        </td>
        <td>
            <span class="btn-delete"></span>
        </td>
    </tr>
    {/foreach}
    </tbody>
</table>

    <div class="btn-bar">
        <div class="btn-right">
            <span class="btn btn-primary btn-add-size">{$smarty.const.IMAGE_ADD}</span>
        </div>
    </div>

</div>

<div class="btn-bar">
    <div class="btn-left">
        <a href="{Yii::$app->urlManager->createUrl('banner_manager/banner-groups')}" class="btn">{$smarty.const.IMAGE_BACK}</a>
    </div>
    <div class="btn-right">
        <span class="btn btn-confirm">{$smarty.const.IMAGE_SAVE}</span>
    </div>
</div>
</form>



<script type="text/javascript">

    $(function () {
        let form = $('.banner-group-form');

        $('.btn-confirm, .save-group').on('click', function(){
            $.post('{$app->urlManager->createUrl('banner_manager/banner-groups-save')}', form.serializeArray(), function(d){
                alertMessage(`<div class="alert-message">${ d}</div>`);
                if (!$('input[name="banners_group"]').val()){
                    window.location = '{$app->urlManager->createUrl('banner_manager/banner-groups-edit')}?banners_group=' + $('input[name="name"]').val()
                }
                setTimeout(function () {
                    $('.popup-box-wrap').remove()
                }, 1000)
            })
        });

        $('.table-bordered tbody', form).on('click', '.btn-delete', function () {
            $(this).parents('tr').remove();
        });

        $('.btn-add-size', form).on('click', function () {
            $('.table-bordered tbody').append(`
    <tr>
        <td>
            <input type="hidden" name="id[]" value="0"/>
            <input type="text" name="width_from[]" value="" class="form-control"/>
        </td>
        <td>
            <input type="text" name="width_to[]" value="" class="form-control"/>
        </td>
        <td>
            <input type="text" name="image_width[]" value="" class="form-control"/>
        </td>
        <td>
            <span class="btn-delete"></span>
        </td>
    </tr>
            `)
        })

    })

</script>