{use class="\yii\helpers\Html"}
{Html::beginForm($url, "post")}
{Html::dropDownList('taxable', $taxable, $tList, ['onchange' => "this.form.submit()"])}
{Html::endForm()}