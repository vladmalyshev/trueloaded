<div id="error_personal_catalog_popup" style="padding: 20px 30px;">
    {if $message}
        {foreach $message as $mes}
            {$mes}<br>
        {/foreach}
    {/if}
    {if $url_text != ''}
        <p style="text-align: center;">
            <a class="btn" href="{$url_link}"><strong>{$url_text}</strong></a>
        </p>
    {/if}
</div>
