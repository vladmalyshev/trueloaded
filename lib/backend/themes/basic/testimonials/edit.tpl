{use class="yii\helpers\Html"}
<!--=== Page Content ===-->
<div id="rewiews_management_data">
<!--===Customers List ===-->
<form name="save_item_form" id="save_item_form" action="{\yii\helpers\Url::to(['testimonials/save'])}" method="post">
{Html::input('hidden', 'testimonials_id', $testimonial['testimonials_id'])}
<input type="hidden" name="row" id="row_id" value="{$row}" />
<div class="box-wrap">
              {if {$messages|@count} > 0}
			   {foreach $messages as $type => $message}
              <div class="alert alert-{$type} fade in">
                  <i data-dismiss="alert" class="icon-remove close"></i>
                  <span id="message_plce">{$message}</span>
              </div>			   
			   {/foreach}
			  {/if} 
    <div class="cedit-top redit-top after">
        <div class="cedit-block cedit-block-1">
            <div class="status-left" style="float: none;">
                <span>{$smarty.const.TABLE_HEADING_STATUS}</span>
                <input type="checkbox" name="status" value="on" class="check_bot_switch_on_off" {if $testimonial.status == 1}checked{/if} />
            </div>
        </div>
        {if $testimonial['testimonials_title']}
        <div class="cedit-block cedit-block-2">
            <div class="cr-ord-cust">
                <span>{$smarty.const.ENTRY_REVIEW}</span>
                <div>{$testimonial['testimonials_title']}</div>
            </div>
        </div>
        {/if}
        <div class="cedit-block cedit-block-3">
            <div class="cr-ord-cust">
                <span>{$smarty.const.ENTRY_FROM}</span>
                <div>{if $testimonial['testimonials_anonymus']} {$smarty.const.TEXT_ANONIMUS}{else}{$testimonial['testimonials_name']}{/if}</div>
            </div>
        </div>
        <div class="cedit-block cedit-block-4">
            <div class="cr-ord-cust">
                <span>{$smarty.const.ENTRY_DATE}</span>
                <div>{\common\helpers\Date::date_short($testimonial['date_added'])}</div>
            </div>
        </div>
    </div>    
    <div class="create-or-wrap after create-cus-wrap">
        <div class="widget box box-no-shadow" style="margin-bottom: 0;">
            <div class="widget-header widget-header-review">
                <h4>{$smarty.const.TEXT_EDIT_TESTIMONIAL}</h4>
            </div>
            <div class="widget-content">
                <div class="wedit-rev after">
                    <label>{$smarty.const.TEXT_TITLE}</label>
                    {Html::input('text', 'testimonials_title', $testimonial['testimonials_title'], ['class' => 'form-control'])}
                    <br/>
                    <label>{$smarty.const.TABLE_HEADING_AUTHOR}:</label>
                    {Html::input('text', 'testimonials_name', $testimonial['testimonials_name'], ['class' => 'form-control'])}
                    <br/>
                    <label>{$smarty.const.TEXT_URL}:</label>
                    {Html::input('text', 'testimonials_url', $testimonial['testimonials_url'], ['class' => 'form-control'])}
                    <br/>
                    <label>{$smarty.const.TEXT_URL_TITLE}:</label>
                    {Html::input('text', 'testimonials_url_title', $testimonial['testimonials_url_title'], ['class' => 'form-control'])}
                    <br/>
                    <label>{$smarty.const.TABLE_HEADING_DESCRIPTION}:</label>
                    {Html::textarea('testimonials_html_text', $testimonial['testimonials_html_text'], ['class' => 'form-control', 'rows' => '10'])}
                    <br/>
                    <label>{$smarty.const.TEXT_ANSWER}:</label>
                    {Html::textarea('testimonials_answer', $testimonial['testimonials_answer'], ['class' => 'form-control', 'rows' => '10'])}
                    
                    <label>{$smarty.const.TEXT_INFO_REVIEW_RATING}</label>
                    <div class="rating-holder">
                        {Html::radio('testimonials_rating', (int)$testimonial['testimonials_rating'] == '1', ['class' => 'star', 'value' => '1', 'title' => "Rate this 1 star out of 5"])}
                        {Html::radio('testimonials_rating', (int)$testimonial['testimonials_rating'] == '2', ['class' => 'star', 'value' => '2', 'title' => "Rate this 2 star out of 5"])}
                        {Html::radio('testimonials_rating', (int)$testimonial['testimonials_rating'] == '3', ['class' => 'star', 'value' => '3', 'title' => "Rate this 3 star out of 5"])}
                        {Html::radio('testimonials_rating', (int)$testimonial['testimonials_rating'] == '4', ['class' => 'star', 'value' => '4', 'title' => "Rate this 4 star out of 5"])}
                        {Html::radio('testimonials_rating', (int)$testimonial['testimonials_rating'] == '5', ['class' => 'star', 'value' => '5', 'title' => "Rate this 5 star out of 5"])}
                    </div>
                </div>
            </div>
        </div>        
    </div>
</div>
<div class="btn-bar">
    <div class="btn-left"><a href="{\yii\helpers\Url::to(['testimonials/', 'row' => $row, 'platform_id' => $testimonial['platform_id']])}" class="btn btn-cancel-foot">{$smarty.const.IMAGE_CANCEL}</a></div>
    <div class="btn-right"><button class="btn btn-confirm">{$smarty.const.IMAGE_SAVE}</button></div>
</div>
</form>
<script>

function backStatement() {
    window.history.back();
    return false;
}
$(document).ready(function(){ 
    $(".check_bot_switch_on_off").bootstrapSwitch(
        {
			onText: "{$smarty.const.SW_ON}",
			offText: "{$smarty.const.SW_OFF}",
            handleWidth: '20px',
            labelWidth: '24px'
        }
    );
    $('input.star').rating(); 
});
</script>

</div>
<!-- /Page Content -->
