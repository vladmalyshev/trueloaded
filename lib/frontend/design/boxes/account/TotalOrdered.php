<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\account;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;

class TotalOrdered extends Widget
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
      if (defined('GROUPS_IS_SHOW_PRICE') && GROUPS_IS_SHOW_PRICE == false) {
        return '';
      }
        return IncludeTpl::widget(['file' => 'boxes/account/total-ordered.tpl', 'params' => [
            'mainData' => $this->params['mainData'],

        ]]);
    }
}