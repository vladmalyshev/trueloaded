{use class="\yii\helpers\Html"}
{\backend\assets\BDPAsset::register($this)|void}
{if !empty($holder)}
{assign var=holder value=" "|cat:$holder}
{else}
{assign var=holder value=""}
{/if}
<div class="wl-td">
<label>{$smarty.const.TEXT_FROM}</label>
{Html::input('text', 'month', $month, ['class' =>'form-control', 'placeholder' => TEXT_SELECT|cat:$holder])}
</div>

<script>
    var checkSelection = function(){
        //check custom    
        return true;
    }
    
    $(document).ready(function(){
        
        $('input[name=month]').datepicker({ 
            'minViewMode':1,
            'format':'mm/yyyy',
            autoclose:true,
            });
            
    })
    
</script>