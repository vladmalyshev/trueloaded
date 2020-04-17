{if $page_name == 'index_2'}
    <span class="btn-2 btn-next">{$smarty.const.CONTINUE}</span>
{else}
    <button type="submit" class="btn-2 btn-next">
    {if $smarty.const.SKIP_CHECKOUT == 'True'}
        {$smarty.const.TEXT_CONFIRM_AND_PAY}
    {else}
        {$smarty.const.CONTINUE}
    {/if}
    </button>
{/if}