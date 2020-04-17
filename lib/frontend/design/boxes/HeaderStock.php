<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;

class HeaderStock extends Widget
{

    public $file;
    public $params;
    public $settings;

    public function init()
    {
        parent::init();
    }

    public function run()
    {
        return IncludeTpl::widget(['file' => 'boxes/header-stock.tpl', 'params' => [
            'checked' => SHOW_OUT_OF_STOCK,
            'url' => tep_href_link(Yii::$app->controller->id . '/' . Yii::$app->controller->action->id, \common\helpers\Output::get_all_get_params()),
            'text' => $this->params['text'] ?? TEXT_OUT_STOCK
        ]]);
    }
}