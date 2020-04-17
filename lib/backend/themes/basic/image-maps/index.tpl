<div class="order-wrap">
    <div class="row order-box-list">
        <div class="col-md-12">
            <div class="widget-content" id="groups_list_data">
                <table class="table table-striped table-bordered table-hover table-responsive table-checkable datatable double-grid"
                       checkable_list="0" data_ajax="image-maps/list">
                    <thead>
                    <tr>
                        <th>Name</th>
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
<script type="text/javascript">

    $(function(){
        $('.table').on('dblclick', 'tr', function(){
            var mapsId = $('.image-map', this).data('maps-id');

            window.location = 'image-maps/edit?maps_id=' + mapsId
        })
    });

    function onClickEvent(obj, table){
        var mapsId = $('.image-map', obj).data('maps-id');
        $.get("image-maps/bar", {
            'maps_id' : mapsId
        }, function(data){
            $('.right_column .scroll_col').html(data);
        });
    }
</script>