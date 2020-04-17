{\backend\assets\BDPAsset::register($this)|void}
<div>{$smarty.const.TEXT_DATE_RANGE}</div>
<div id="slider-range"></div>
<div class="min-range" style="float:left;"><label>{$smarty.const.TEXT_FROM}:</label>&nbsp;<input value="" class="date-input form-control"></div>
<div class="max-range" style="float:right;margin-bottom:12px;"><label>{$smarty.const.TEXT_TO}</label>&nbsp;<input value="" class="date-input form-control"></div>
<script>
    (function($){
        var last_min, last_max;
        $('#slider-range').slider({
            range: true,
            min: {$min_date},
            max: {$max},
            step: 1, 
            values: [{$min}, {$max}],
            change: function( event, ui ) {
                if (last_min == ui.values[0] && last_max == ui.values[1]) return;
                var _min = new Date(ui.values[0]*1000);
                var _max = new Date(ui.values[1]*1000);
                $('.min-range input').val(getLongDate(_min));
                $('.max-range input').val(getLongDate(_max));
                clearMarkers();
                loadMapData(ui.values[0], ui.values[1]);
                last_min = ui.values[0];
                last_max = ui.values[1];
                $('.min-range input.date-input').datepicker( 'update', _min );
                $('.max-range input.date-input').datepicker( 'update', _max );
            }
        });
        $(document).ready(function(){
            $('.min-range input.date-input').datepicker({
                format: 'dd MM yyyy',
                autoclose: true,
                startDate: new Date({$min_date}*1000),
                beforeShowDay: function(e){
                    var values = $('#slider-range').slider('values');
                    return (new Date(e)).getTime() <= (new Date(values[1]*1000)).getTime();
                }
            }).on('changeDate', function(e){
               var values = $('#slider-range').slider('values');
               var _d = new Date(e.date);
               values[0] = _d.getTime()/1000;
               $('#slider-range').slider('values', values);
            });
            
            $('.max-range input.date-input').datepicker({
                format: 'dd MM yyyy',
                autoclose: true,
                endDate: new Date( (new Date()).getFullYear(), (new Date()).getMonth(), (new Date()).getDate()+1, 0,0,0,0 ),
                beforeShowDay: function(e){
                    var values = $('#slider-range').slider('values');
                    return (new Date(e)).getTime() >= (new Date(values[0]*1000)).getTime();
                }
            }).on('changeDate', function(e){               
               var values = $('#slider-range').slider('values');
               var _d = new Date(e.date);
               values[1] = _d.getTime()/1000;
               $('#slider-range').slider('values', values);               
            });
            
            var _min = new Date({$min}*1000);
            var _max = new Date({$max}*1000); 
            $('.min-range input.date-input').datepicker( 'update', _min );
            $('.max-range input.date-input').datepicker( 'update', _max );
        })
    })(jQuery)
    
</script>