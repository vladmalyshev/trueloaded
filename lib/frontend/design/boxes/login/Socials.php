<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\login;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;

class Socials extends Widget
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
        if (!$this->params['show_socials']){
            return IncludeTpl::widget(['file' => 'boxes/hide-box.tpl','params' => [
                'settings' => $this->settings,
                'id' => $this->id
            ]]);
        }
        
        return IncludeTpl::widget(['file' => 'boxes/login/socials.tpl', 'params' => array_merge($this->params, [
            'settings' => $this->settings,
            'id' => $this->id,
        ])]);
    }
}