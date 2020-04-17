{use class="\yii\helpers\Html"}
<div class="popup-heading">{$smarty.const.HEADING_TITLE}</div>
<div class="creditHistoryPopup">
    <table class="order-journal-datatable table table-striped table-bordered table-hover table-responsive table-checkable datatable double-grid table-no-search">
        <thead>
            <tr>
                {foreach $app->controller->view->reportTable as $tableItem}
                    <th{if $tableItem['not_important'] == 1} class="hidden-xs"{/if}>{$tableItem['title']}</th>
                {/foreach}
            </tr>
        </thead>
        <tbody>
        {foreach $app->controller->view->reportData as $tableItemArray}
            <tr>
            {foreach $tableItemArray as $tableItem}
                <td>{$tableItem}</td>
            {/foreach}
            </tr>
        {/foreach}
        </tbody>
    </table>
</div>
<div class="mail-sending noti-btn">
    <div><span class="btn btn-cancel">{$smarty.const.IMAGE_CANCEL}</span></div>
</div>