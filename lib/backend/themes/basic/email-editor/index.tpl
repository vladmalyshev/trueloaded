<div class="order-wrap">
    <div class="row order-box-list">
        <div class="col-md-12">
            <div class="widget-content" id="groups_list_data">
                <table class="table table-striped table-bordered table-hover table-responsive table-checkable datatable double-grid"
                       checkable_list="0,1,2" data_ajax="email-editor/list">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Last modified</th>
                        <th>Date added</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>


    <div class="row right_column" id="order_management" style="display: none;">
        <div class="widget box">
            <div class="widget-content fields_style" id="groups_management_data">
                <div class="scroll_col"></div>
            </div>
        </div>
    </div>
</div>
<style type="text/css">
    .table td:first-child {
        width: 50%;
    }
    .table td + td, .table th + th {
        text-align: right;
        padding-right: 20px !important;
    }
</style>
<script type="text/javascript">

    $(function(){
        $('.table').on('dblclick', 'tr', function(){
            var itemId = $('.email-editor', this).data('item-id');

            window.location = 'email-editor/edit?email_id=' + itemId
        })
    });

    function onClickEvent(obj, table){
        var itemId = $('.item', obj).data('item-id');
        $.get("email-editor/bar", {
            'email_id' : itemId
        }, function(data){
            $('.right_column .scroll_col').html(data);
        });
    }
</script>