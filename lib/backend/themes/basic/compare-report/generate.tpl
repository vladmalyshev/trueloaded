<table class="table datatable"><thead><tr><th>{$platformTitle}</th></tr></thead></table><br>
{foreach $tables as $table}
<table class="table datatable">
{foreach $table as $key => $row}
    {if $key == 0}
        <thead>
    {/if}
    {if $key == 1}
        <tbody>
    {/if}
        <tr>
    {foreach $row as $column}
        {if $key == 0}<th>{else}<td>{/if}{$column}{if $key == 0}</th>{else}</td>{/if}
    {/foreach}
        </tr>
    {if $key == 0}
        </thead>
    {/if}
{/foreach}
    <tbody>
</table>
<br>
{/foreach}