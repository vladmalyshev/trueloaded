<div class="or_box_head">{$data.title}</div>

<div class="btn-toolbar btn-toolbar-order">
    <a class="btn btn-primary btn-edit btn-no-margin btn-process-order" href="{Yii::$app->urlManager->createUrl([$action, 'group_id' => $group_id, 'field_id' => $field_id, 'level_type' => $level_type, 'row' => $row])}">{IMAGE_EDIT}</a>
    <span class="btn btn-delete delete-item btn-process-order">{IMAGE_DELETE}</span>
</div>

<script type="text/javascript">
    $(function(){

        $('.delete-item').on('click', function(){
            $.get('{Yii::$app->urlManager->createUrl(['customer-additional-fields/delete-confirm'])}', {
                'group_id': '{$group_id}',
                'field_id': '{$field_id}',
                'level_type': '{$level_type}',
            }, function(data){
                $('.right_column .scroll_col').html(data);
            })
        })
    })
</script>