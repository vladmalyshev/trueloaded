{use class="\yii\helpers\Html"}
{use class="\common\models\promotions\PromotionsBonusGroups"}
<style>
 .item-box{ padding:10px;width:800px; }
 .btn-edit, .btn-edit:active{ width: 30px;padding: 1%; text-decoration:none; }
 .btn-edit:hover { text-decoration:none; }
 .editing-mode1{ width: 30px;padding: 5%; }
 .dataTable input{ width:100px; }
 th.ted{ text-align: center; }
</style>

<div class="info-pleasure-points-table col-md-12">
{if $groups}
    <div>{Html::checkbox('programm_status', "{$smarty.const.BONUS_ACTION_PROGRAM_STATUS == 'true'}", ['class' => 'status_on_off'])}&nbsp;<label>{$smarty.const.TEXT_BONUS_PROGRAMME_STATUS}</label></div>
    {if $messages}
      <div class="alert fade in alert-success">
          <i data-dismiss="alert" class="icon-remove close"></i>
          <span id="message_plce">{$messages}</span>
      </div>  
    {/if}
    {Html::beginForm('promotions/actions', 'post')}
    {foreach $groups as $group_code => $group}
        <h4 class="{$group_code}" data-code="{$group_code}"><a href="javascript:void(0);" class="group-title-edit btn-edit">&nbsp;{$group['name']}</a>
        <div class="item-title-holder" style="display:none" data-code="{$group_code}">
            <h4>{$smarty.const.IMAGE_EDIT}</h4>
            <div class="topTabPane tabbable-custom">
            {if is_array($languages)}
                {if count($languages) > 1}
                <ul class="nav nav-tabs">
                    {foreach $languages as $language}
                    <li{if $language['id'] == $default_language} class="active"{/if}><a href="#tab_{$group_code}_{$language['code']}" data-toggle="tab">{$language['image']}<span>{$language['name']}</span></a></li>
                    {/foreach}
                </ul>
                {/if}
                <div class="tab-content {if count($languages) < 2}tab-content-no-lang{/if}">
                {foreach $languages as $language}
                    <div class="tab-pane{if $language['id'] == $default_language} active{/if}" id="tab_{$group_code}_{$language['code']}">
                    {Html::input('text', "group_title[{$language['id']}][{$group_code}]", PromotionsBonusGroups::getGroupTitle($group_code, $language['id']), ['class' => 'form-control'])}
                    </div>
                {/foreach}
                </div>
            {/if}
           </div>
           <div class="btn-bar edit-btn-bar"><br/>
            <button class="btn btn-save">{$smarty.const.IMAGE_SAVE}</button>
           </div>
        </div>
        </h4>
       <table class="col-md-12 table dataTable">
            <thead>
                <tr>
                    <th width="35%" align="left">{$smarty.const.TEXT_PROMO_AREA}</th>
                    <th width="15%" align="center" class="ted">{$smarty.const.TEXT_PROMO_AWARD}</th>
                    <th width="15%" align="center" class="ted">{$smarty.const.TEXT_PROMO_OCCASION_LIMIT}</th>
                    <th align="center">{$smarty.const.TEXT_PROMO_DAILY_LIMIT}</th>
                    <th align="center">{$smarty.const.TABLE_HEADING_STATUS}</th>
                    <th width="100px">&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                {if $group['items']}
                    {foreach $group['items'] as $code => $item}
                        {if is_object($item)}
                            <tr>
                                <td class="{$code}">
                                    <span class="item-value-title"><a href="javascript:void(0);" class="item-title-edit btn-edit">&nbsp;{$item->getPointsTitle()}</a></span>
                                     <div class="item-title-holder" data-code="{$code}" style="display:none">
                                        <h4>{$smarty.const.IMAGE_EDIT}</h4>
                                        <div class="topTabPane tabbable-custom">
                                        {if is_array($languages)}
                                            {if count($languages) > 1}
                                           <ul class="nav nav-tabs">
                                                {foreach $languages as $language}
                                                <li{if $language['id'] == $default_language} class="active"{/if}><a href="#tab_{$code}_{$language['code']}" data-toggle="tab">{$language['image']}<span>{$language['name']}</span></a></li>
                                                {/foreach}
                                            </ul>
                                            {/if}
                                            <div class="tab-content {if count($languages) < 2}tab-content-no-lang{/if}">
                                            {foreach $languages as $language}
                                                <div class="tab-pane{if $language['id'] == $default_language} active{/if}" id="tab_{$code}_{$language['code']}">
                                                {Html::input('text', "points_title[{$language['id']}][{$code}]", $item->getPointsTitle($language['id']), ['class' => 'form-control'])}
                                                </div>
                                            {/foreach}
                                            </div>
                                        {/if}
                                       </div>
                                       <div class="btn-bar edit-btn-bar"><br/>
                                        <button class="btn btn-save">{$smarty.const.IMAGE_SAVE}</button>
                                       </div>
                                    </div>
                                </td>
                                <td align="center">{Html::activeHiddenInput($item, "bonus_points_award", ['class' => 'form-control item-input', 'value' => $item->getBonusPointsAward()])}<span class="item-value">{$item->getBonusPointsAward()}</span></td>
                                <td align="center">{Html::activeHiddenInput($item, "bonus_points_limit", ['class' => 'form-control item-input', 'value' => $item->getBonusDailyLimit()])}<span class="item-value">{$item->getBonusDailyLimit()}</span></td>
                                <td align="center" class="dayli-limit">{$item->getBonusPointsLimit()}</span></td>
                                <td>{Html::activeCheckbox($item, "bonus_points_status", ['class' => 'check_on_off'])}</td>
                                <td align="center"><span class="row-edit btn-edit">&nbsp;</span></td>
                            </tr>
                        {/if}
                    {/foreach}
                {/if}
            </tbody>
       </table>
    {/foreach}
    <div class="btn-bar">
      <div class="btn-right">{Html::submitButton(IMAGE_SAVE, ['class' => 'btn btn-primary'])}</div>
    </div>    
    {Html::endForm()}
{/if}
</div>    

<script>
    $(document).ready(function(){
        $('.btn-edit.row-edit').click(function(){
           var row = $(this).parents('tr');
           if ($(this).hasClass('editing-mode')){
                $(this).removeClass('btn');
                $('input.item-input:visible', row).attr('type', 'hidden');
                var result = 0;
                $.each($('input.item-input:hidden', row), function(i, e){
                     $(e).next().html($(e).val());
                     if (!result){ result =  $(e).val(); } else { result *= $(e).val(); }                     
                });
                $('.dayli-limit', row).html(result);
                $('.item-value', row).show();
                $(this).removeClass('editing-mode');
           } else {               
               $('input.item-input:hidden', row).attr('type', 'text');
               $('.item-value', row).hide();
               $(this).addClass('editing-mode');
               $(this).addClass('btn');
           }
        });
        
        var current_box;
        var saved = false;
        var default_language = '{$default_language}';
        
        function saveData(){
            var items = [];
            $.each($('.pop-up-content .item-title-holder input'), function(i, e){
                items[$(e).attr('name')] = $(e).val();
            });
            if ($("td."+current_box).is('td')){
                $("td."+current_box).append($('.pop-up-content .item-title-holder'));
                $("td."+current_box).find('.item-title-edit').html( '&nbsp;' + $("td."+current_box).find('input[name="points_title['+default_language+']['+current_box+']"]').val() );                
            } else {
                $("h4."+current_box).append($('.pop-up-content .item-title-holder'));
                $("h4."+current_box).find('.group-title-edit').html( '&nbsp;' + $("h4."+current_box).find('input[name="group_title['+default_language+']['+current_box+']"]').val() );
            }
            
            saved = true;
        }
        
        var lastClone;
        
        function prepareHolder(holder){
            lastClone = $(holder).clone();
            saved = false;
            var popup = $(holder).popUp({
                event: 'show',
                box_class: 'item-box',
                only_show: true,
                data: holder,
                position: function(o){ if ($(o).height() > 0){ $(o).css('top', (($(window).height() - $(o).height()) / 3) + $(window).scrollTop() )} }
            }).show();
            current_box = $(holder).data('code');
            $('.pop-up-close').click(function(e){
                if (!saved){
                    if ($("td."+current_box).is('td')){
                        $("td."+current_box).append(lastClone);
                    } else {
                        $("h4."+current_box).append(lastClone);
                    }
                    
                }
            });
        }
        
        $('a.item-title-edit').click(function(){
           var holder = $(this).parent().parent().find('.item-title-holder');
           prepareHolder(holder);
        });
        
        $('.group-title-edit').click(function(){
           var holder = $(this).parent().find('.item-title-holder');
           prepareHolder(holder);
        });
        
        $('body').on('click', '.pop-up-content:last .btn-save', function(e){
            e.preventDefault();
            saveData();
            $('.item-title-holder').hide();            
            $('.pop-up-close').trigger('click');
        })
        
        $('.check_on_off').bootstrapSwitch({
            onText: "{$smarty.const.SW_ON}",
            offText: "{$smarty.const.SW_OFF}",
            handleWidth: '20px',
            labelWidth: '24px'
        });
        
        function switchStatement(status) {
            $.post("{Yii::$app->urlManager->createUrl('promotions/program-status')}", { 'status' : status }, function(data, status){
                if (status == "success") {                 
                } else {
                    alert("Request error.");
                }
            },"html");
        }
        
        $('.status_on_off').bootstrapSwitch({
            onSwitchChange: function (element, arguments) {
                switchStatement(arguments);
                return true;  
            },
            onText: "{$smarty.const.SW_ON}",
            offText: "{$smarty.const.SW_OFF}",
            handleWidth: '20px',
            labelWidth: '24px'
        });
    })
</script>