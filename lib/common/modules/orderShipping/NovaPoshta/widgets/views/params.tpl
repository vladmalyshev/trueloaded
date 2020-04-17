{if $info->isNotEmpty()}
    <table>
        {if $info->getArea()}
            <tr>
                <td>{$smarty.const.ENTRY_STATE_TEXT}:</td>
                <td>{$info->getArea()}</td>
            </tr>
        {/if}
        {if $info->getCity()}
            <tr>
                <td>{$smarty.const.ENTRY_TOWN_TEXT}:</td>
                <td>{$info->getCity()}</td>
            </tr>
        {/if}
        {if $info->getWarehouse()}
            <tr>
                <td>{$smarty.const.ENTRY_DEPARTMENT_TEXT}:</td>
                <td>{$info->getWarehouse()}</td>
            </tr>
        {/if}
        {if $info->getFirstname()}
            <tr>
                <td>{$smarty.const.ENTRY_FIRST_NAME}:</td>
                <td>{$info->getFirstname()}</td>
            </tr>
        {/if}
        {if $info->getLastname()}
            <tr>
                <td>{$smarty.const.ENTRY_LAST_NAME}:</td>
                <td>{$info->getLastname()}</td>
            </tr>
        {/if}
        {if $info->getTelephone()}
            <tr>
                <td>{$smarty.const.ENTRY_TELEPHONE_NUMBER}:</td>
                <td>{$info->getTelephone()}</td>
            </tr>
        {/if}
    </table>
{/if}
