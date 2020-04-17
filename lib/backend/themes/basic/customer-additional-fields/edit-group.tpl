
{use class="backend\assets\BannersAsset"}
{BannersAsset::register($this)|void}

<form action="" class="group-form">
    <input type="hidden" name="group_id" value="{$group_id}"/>

<div class="">


    <div class="tabbable tabbable-custom">
        <ul class="nav nav-tabs">

            {foreach $languages as $language}
                <li{if $language.id == $languages_id} class="active"{/if}><a href="#{$item.id}_{$language.id}" data-toggle="tab">{$language.logo} {$language.name}</a></li>
            {/foreach}

        </ul>
        <div class="tab-content">

            {foreach $languages as $language}
                <div class="tab-pane{if $language.id == $languages_id} active{/if}" id="{$item.id}_{$language.id}" data-language="{$language.id}">
                    <div class="row">
                        <div class="col-md-2">
                            <label for="">{$smarty.const.TABLE_HEADING_TITLE}</label>
                        </div>
                        <div class="col-md-4"><input type="text" name="title[{$language.id}]" value="{$groupsByLanguages[$language.id]['title']}" class="form-control"/></div>
                    </div>
                </div>
            {/foreach}

        </div>
    </div>


</div>

<div class="btn-bar">
    <div class="btn-left">
        <a href="{Yii::$app->urlManager->createUrl(['customer-additional-fields', 'group_id' => $group_id, 'field_id' => $field_id, 'level_type' => $level_type, 'row' => $row])}" class="btn">{$smarty.const.IMAGE_BACK}</a>
    </div>
    <div class="btn-right">
        <span class="btn btn-confirm">{$smarty.const.IMAGE_SAVE}</span>
    </div>
</div>
</form>



<script type="text/javascript">

    $(function () {

        let form = $('.group-form');

        $('.btn-confirm').on('click', function(){
            $.post('{$app->urlManager->createUrl('customer-additional-fields/save-group')}', form.serializeArray(), function(d){
                alertMessage(`<div class="alert-message">${ d}</div>`);
                if (!$('input[name="group_id"]').val()){
                    window.location = '{$app->urlManager->createUrl('customer-additional-fields')}'
                }
                setTimeout(function () {
                    $('.popup-box-wrap').remove()
                }, 1000)
            })
        });
    })

</script>