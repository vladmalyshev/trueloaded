{use class = "common\helpers\Date"}
{if $platform}
    <div class="or_box_head">{$platform->platform_name}</div>
    <div class="row_or"><div>{$smarty.const.TEXT_DATE_ADDED}</div><div>{Date::date_short($platform->date_added)}</div></div>
    {if tep_not_null($platform->last_modified)}
        <div class="row_or"><div>{$smarty.const.TEXT_LAST_MODIFIED}</div><div>{Date::date_short($platform->last_modified)}</div></div>
    {/if}
    
    {if $statement}
        <div class="btn-toolbar btn-toolbar-order">
            <a href="{Yii::$app->urlManager->createUrl(['platforms/edit', 'id' => $platform->platform_id])}" class="btn btn-edit btn-primary btn-process-order ">{$smarty.const.IMAGE_EDIT}</a>            
                {if !($platform->is_default || in_array($platform->platform_id, [4, 5, 6]))}
                    <button onclick="return deleteItemConfirm({$platform->platform_id})" class="btn btn-delete btn-no-margin btn-process-order ">{$smarty.const.IMAGE_DELETE}</button>                
                {/if}
                {if !$platform->is_virtual}
                    {$multiplatform}            
                    <a href="{Yii::$app->urlManager->createUrl(['platforms/configuration', 'platform_id' => $platform->platform_id])}" class="btn btn-edit btn-primary btn-process-order ">{$smarty.const.BOX_HEADING_CONFIGURATION}</a>
                {/if}
            <button onclick="return copyItemConfirm({$platform->platform_id})" class="btn btn-copy btn-primary btn-process-order ">{$smarty.const.IMAGE_COPY}</button>
        </div>
    {/if}
{/if}
