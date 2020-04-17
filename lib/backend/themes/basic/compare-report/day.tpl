{use class="\yii\helpers\Html"}
{\backend\assets\BDPAsset::register($this)|void}
<div class="wl-td">
<label>{$smarty.const.TEXT_FROM}:</label>
{Html::input('text', 'day', $day, ['class' =>'form-control', 'placeholder' => TEXT_SELECT])}
</div>
<script>
    var checkSelection = function(){
        //check custom    
        return true;
    }
    
    $(document).ready(function(){
        
        $('input[name=day]').datepicker({ 
            'minViewMode':0,
            'format':'dd/mm/yyyy',
            autoclose:true,
            });
    })
    
</script>