{use class="\yii\helpers\Html"}
<div class="wl-td">
<label>{$smarty.const.TEXT_FROM}:</label>
{Html::input('text', 'week', $week, ['class' =>'form-control', 'placeholder' => TEXT_SELECT])}
</div>
<script>
    var checkSelection = function(){
        //check custom    
        return true;
    }

    $(document).ready(function(){
        
        $('input[name=week]').datepicker({ 
            'minViewMode':0,
            'format':'dd/mm/yyyy',
            'weekStart':1,
            'multidate': true,
            'multidateSeparator':'-',
            'autoclose':true,
            'isChanged': false,
            'daysOfWeekDisabled' : [2,3,4,5,6,0],
            'calendarWeeks' : true,
            }).on('changeDate', function(e){
                this.isChanged = true;
            }).on('hide', function(e){
                if (!isNaN(Date.parse(e.date)) && this.isChanged){
                    var date = new Date(e.date);
                    var startDate = new Date(date.getFullYear(), date.getMonth(), date.getDate()); // - date.getDay() + 1 from monday to saunday
                    var endDate = new Date(date.getFullYear(), date.getMonth(), date.getDate() + 6);// - date.getDay() + 7 from monday to saunday
                    $('input[name=week]').datepicker('setDates', [ startDate, endDate ] );
                }
                this.isChanged = false;
            });            
    })
    
</script>