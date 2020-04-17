<div>
<!--=== Page Header ===-->
<div class="page-header">
    <div class="page-title">
        <h3>{$app->controller->view->headingTitle}</h3>
    </div>
</div>
<!-- /Page Header -->

<!--=== Page Content ===-->
<link href="{{$smarty.const.DIR_WS_ADMIN}}/css/fancybox.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{$smarty.const.DIR_WS_ADMIN}/js/jquery.fancybox.pack.js"></script>

<!--===Process Order ===-->

<form action="{$link}" method="post" id="subscribers-form">
<input type="hidden" name="subscribers_md5hash" value="{$subscribers_md5hash}">
<input type="hidden" name="subscribers_id" value="{$subscribers_id}">
<div class="tabbable tabbable-custom">
<div class="tab-content">
<table cellspacing="0" cellpadding="0">
<tbody>
<tr>
<td class="label_name">{ENTRY_FIRST_NAME}</td>
<td class="label_value"><input name="subscribers_firstname" value="{$subscribers_firstname}" class="form-control" type="text"></td>
</tr>
<tr>
<td class="label_name" valign="top">{ENTRY_LAST_NAME}</td>
<td class="label_value"><input name="subscribers_lastname" value="{$subscribers_lastname}" class="form-control" type="text"></td>
</tr>
<tr>
<td class="label_name" valign="top">{ENTRY_EMAIL_ADDRESS}</td>
<td class="label_value"><input name="subscribers_email_address" value="{$subscribers_email_address}" class="form-control type="text"></td>
</tr>
</tbody></table>
</div></div>
<div class="btn-bar edit-btn-bar">
<div class="btn-left"><a href="javascript:void(0)" class="btn btn-cancel-foot" onclick="return backStatement()">Cancel</a></div>
<div class="btn-right"><button class="btn btn-primary">Save</button></div>
</div>
</form>
<!-- Process Order -->

<!-- /Page Content -->
</div>

<script type="text/javascript">
function backStatement() {     
  window.history.back();    
  return false;
}
</script>