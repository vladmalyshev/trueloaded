
<table border="0" width="100%" cellspacing="0" cellpadding="5" class="invoice-totals">

    {foreach $order_total_output as $price}
      <tr>
        <td{if $price.show_line} style="border-top: 1px solid #ccc; padding-top: 5px"{/if}>{$price.title}</td>
        <td{if $price.show_line} style="border-top: 1px solid #ccc; padding-top: 5px"{/if}>{$price.text}</td>
      </tr>
    {/foreach}
</table>