<div class="copy-page-form">
    <div class="popup-heading">Copy page content</div>
    <div class="popup-content pop-mess-cont">


        <p>Copy page content from </p>
        <p>
            <select name="page_type" class="form-control">
                <option value=""></option>
                {foreach $pages as $page}
                    <option value="{$page.name}">{$page.title}</option>
                {/foreach}
            </select>
        </p>
        <p>to <strong>"{$page_title}"</strong></p>


    </div>
    <div class="noti-btn">
        <div><span class="btn btn-cancel">{$smarty.const.IMAGE_CANCEL}</span></div>
        <div><button type="submit" class="btn btn-primary btn-save">{$smarty.const.TEXT_BTN_OK}</button></div>
    </div>
</div>

<script type="text/javascript">
    $(function(){
        var $form = $('.copy-page-form');
        var $select = $('select[name="page_type"]', $form);
        $('.btn-save', $form).on('click', function(){

            $.post('design/copy-page', {
                'theme_name': '{$theme_name}',
                'page_from': $select.val(),
                'page_to': '{$page_name}',
            }, function () {
                document.location.reload()
            })
        })
    })
</script>