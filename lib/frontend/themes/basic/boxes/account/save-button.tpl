
{if $create}
    <input type="hidden" name="create" value="1"/>
    <button class="btn">{$smarty.const.IMAGE_BUTTON_CONTINUE}</button>
{else}
    <button class="btn btn-confirm">{$smarty.const.TEXT_SAVE}</button>
{/if}