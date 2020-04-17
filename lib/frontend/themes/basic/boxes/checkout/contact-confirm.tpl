{if $manager->get('is_multi') == 1}
<div class="">{$manager->get('customer_email_address')}</div>
{else}
<div class="">{$data.email_address}</div>
{/if}
<div class="">{$data.telephone}</div>
<div class="">{$data.landline}</div>
