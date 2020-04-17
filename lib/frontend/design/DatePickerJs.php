<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design;

use yii\base\Widget;

class DatePickerJs extends Widget
{

    public $selector;
    public $params;

    public function init()
    {
        parent::init();
    }

    public function run()
    {
        \common\helpers\Translation::init('js');
        return IncludeTpl::widget([
            'file' => 'boxes/date-picker-js.tpl',
            'params' => [
                        'selector' => $this->selector,
                        'params' => $this->params
        ]]);
    }

}
