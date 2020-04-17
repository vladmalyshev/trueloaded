
<div class="popup-heading">{$details.name}</div>
<div class="popup-content">




  <table class="table">
    <tr>
      <td>Admin: </td>
      <td>{$details.admin}</td>
    </tr>
    <tr>
      <td>Date / time: </td>
      <td>{$details.date_added}</td>
    </tr>
    {if $details.widget_name}
    <tr>
      <td>Widget name: </td>
      <td>{$details.widget_name}</td>
    </tr>
    {/if}
  </table>



</div>
<div class="popup-buttons" style="overflow: hidden">
  <span class="btn btn-cancel">Close</span>
</div>