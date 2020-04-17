{use class="common\helpers\Html"}
    <div class="widget"><div class="widget-header" style="margin-bottom: 0;"><h4>{$smarty.const.BOX_USER_GROUPS_RESTRICTIONS}</h4></div>
    <table class="table tabl-res table-striped table-hover table-responsive table-bordered table-switch-on-off double-grid">
        <thead>
        <tr>
            <th>{$smarty.const.BOX_CUSTOMERS_GROUPS}</th>
            <th width="150">{$smarty.const.TEXT_ASSIGN}</th>
        </tr>
        </thead>
        <tbody>
        {foreach $customer_groups as $groupId=>$groupName}
            <tr>
                <td>{$groupName}</td>
                <td>
                    {Html::checkbox('customer_groups_assigned[]', isset($customer_groups_assigned[$groupId]), ['value' => $groupId,'class'=>'check_on_off'])}
                </td>
            </tr>
        {/foreach}
        </tbody>
    </table>
    </div>
    <input type="hidden" name="customer_groups_assigned_present" value="1">