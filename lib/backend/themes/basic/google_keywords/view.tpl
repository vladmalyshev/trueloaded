
    <div class="or_box_head">{$keyword->gapi_keyword}</div>
    <div class="row_or_wrapp">
        <div class="row_or"><div>{$smarty.const.TABLE_HEADING_VISIT}:</div><div>{$keyword->gapi_views}</div></div>
        <div class="row_or"><div>{$smarty.const.FIELDSET_ASSIGNED_PRODUCTS}:</div><div>{if is_array($keyword->products)}{count($keyword->products)}{/if}</div></div>
    </div>
	<div class="btn-toolbar btn-toolbar-order">
        <a href="{\yii\helpers\Url::to(['google_keywords/edit', 'gID' => $keyword->gapi_id, 'row_id' => $app->controller->view->row_id])}" class="btn btn-no-margin btn-primary btn-edit">{$smarty.const.IMAGE_EDIT}</a>
        <button onclick="return confirmDeleteCategory({$keyword->gapi_id})" class="btn btn-delete btn-no-margin">{$smarty.const.IMAGE_DELETE}</button>
    </div>
