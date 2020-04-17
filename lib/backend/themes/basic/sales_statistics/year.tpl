{use class="\yii\helpers\Html"}
<div class="wl-td">
<label>{$smarty.const.TITLE_YEAR}:</label>
{Html::dropDownList('year', $year, $years, ['class' =>'form-control range-block'])}
</div>
