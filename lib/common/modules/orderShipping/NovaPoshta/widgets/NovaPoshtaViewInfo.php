<?php

namespace common\modules\orderShipping\NovaPoshta\widgets;

use common\modules\orderShipping\NovaPoshta\VO\ViewShippingInfo;

class NovaPoshtaViewInfo extends \yii\base\Widget
{
    /** @var ViewShippingInfo */
    public $info;
    public $view = 'params';

    public function run()
    {
        return $this->render('params.tpl', ['info' => $this->info]);
    }
}
