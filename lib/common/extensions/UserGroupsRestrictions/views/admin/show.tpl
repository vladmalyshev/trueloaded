<div class="btn-toolbar btn-toolbar-order">
    <a href="{$app->urlManager->createUrl(['groups/restrictions','groupId'=>$groupId])}" class="btn btn-primary btn-process-order js-open-tree-popup" data-id="{$groupId}"><i class="icon-tag"></i> {$smarty.const.BUTTON_ASSIGN_CATEGORIES_PRODUCTS_TEXT}</a>
</div>
<script>
    $('.js-open-tree-popup').popUp();
</script>