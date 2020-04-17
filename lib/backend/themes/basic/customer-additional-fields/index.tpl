<div class="order-wrap">
    <div class="row order-box-list">
        <div class="col-md-12">
            <div class="widget-content">
                <form id="filterForm" name="filterForm">
                    <input name="row" id="row_id" value="{$row}" type="hidden">
                    <input name="group_id" id="group_id" value="{$group_id}" type="hidden">
                    <input name="field_id" id="field_id" value="{$field_id}" type="hidden">
                    <input name="level_type" id="level_type" value="{$level_type}" type="hidden">
                </form>
                <table class="table table-striped table-bordered table-hover table-responsive table-checkable datatable double-grid"
                       checkable_list="0" data_ajax="customer-additional-fields/list">
                    <thead>
                    <tr>
                        <th>{$smarty.const.TABLE_TEXT_NAME}</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>


    <div class="row right_column" id="order_management" style="display: none;">
        <div class="widget box">
            <div class="widget-content fields_style">
                <div class="scroll_col"></div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    function resetStatement(reset, resetSearch ) {
        if ($("#level_type").val() === 'fields' && !$("#row_id").val()) {
            $("#row_id").val(1)
        }
        if ( reset !== true ) reset = false;
        var table = $('.table').DataTable();
        if (resetSearch) {
            table.search('');
        }
        table.draw(reset);
        return false;
    }

    $(function(){
        let $table = $('.table');
        let dtable = $table.DataTable();
        if ($("#level_type").val() === 'fields'){
            let group_id = $("#group_id").val();
            let href = $('.btn-add-field').attr('href');
            href = setGetParam(href, 'group_id', group_id);
            $('.btn-add-field').attr('href', href);
        }

        $table.on('dblclick', 'tr', function(){

            let { group_id, field_id, row_id} = setFilter(this);
            let level_type = $("#level_type").val();

            if ($('.item', this).hasClass('item-back')) {

                $("#level_type").val('groups');
                $('.top_bead h1').text('{$smarty.const.ADDITIONAL_CUSTOMER_FIELDS_GROUPS}');
                $('.btn-add-group').show();
                $('.btn-add-field').hide();
                resetStatement();

            } else if (level_type === 'groups') {

                $("#level_type").val('fields')
                $('.top_bead h1').text('{$smarty.const.ADDITIONAL_CUSTOMER_FIELDS}');
                $('.btn-add-group').hide();
                $('.btn-add-field').show();
                let href = $('.btn-add-field').attr('href');
                href = setGetParam(href, 'group_id', group_id);
                $('.btn-add-field').attr('href', href);
                resetStatement();

            } else if (level_type === 'fields') {

                let url = 'customer-additional-fields/edit';
                url += field_id ? '?field_id=' + field_id : '';
                url += group_id ? '&group_id=' + group_id : '';
                url += row_id ? '&row=' + row_id : '';
                url += level_type ? '&level_type=' + level_type : '';
                window.location = url;
            }
        });
        $table.on('click', 'tr', function(){
            let sendData = setFilter(this);

            if ($('.item', this).hasClass('item-back')) {

                if ($('.item', $table) > 1) {
                    $("#level_type").val('groups');
                    $('.top_bead h1').text('{$smarty.const.ADDITIONAL_CUSTOMER_FIELDS_GROUPS}');
                    $('.btn-add-group').show();
                    $('.btn-add-field').hide();
                    resetStatement();
                }

            } else {

                sendData.row = sendData.row_id;
                sendData.level_type = $("#level_type").val();
                $.get("customer-additional-fields/bar", sendData, function (data) {
                    $('.right_column .scroll_col').html(data);
                });
            }
        });

        function setFilter($row){
            let row_id = dtable.row('.selected').index();
            let group_id = $('.item', $row).data('group-id');
            let field_id = $('.item', $row).data('field-id');

            $('#group_id').val(group_id);
            $('#field_id').val(field_id);
            $("#row_id").val(row_id);

            let orig = $('#filterForm').serialize();
            let url = window.location.origin + window.location.pathname + '?' + orig.replace(/[^&]+=\.?(?:&|$)/g, '')
            window.history.replaceState({ }, '', url);

            return {
                group_id, field_id, row_id
            }
        }
    });

    function findGetParameter(parameterName) {
        var result = null,
            tmp = [];
        var items = location.search.substr(1).split("&");
        for (var index = 0; index < items.length; index++) {
            tmp = items[index].split("=");
            if (tmp[0] === parameterName) result = decodeURIComponent(tmp[1]);
        }
        return result;
    }


    function setGetParam(href, paramName, paramValue){
        var res = '';
        var d = href.split("#")[0].split("?");
        var base = d[0];
        var query = d[1];
        if(query) {
            var params = query.split("&");
            for(var i = 0; i < params.length; i++) {
                var keyval = params[i].split("=");
                if(keyval[0] != paramName) {
                    res += params[i] + '&';
                }
            }
        }
        res += paramName + '=' + paramValue;
        return base + '?' + res;
    }

</script>