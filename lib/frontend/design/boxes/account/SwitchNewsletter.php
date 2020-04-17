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

class SwitchNewsletter extends Widget
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
        if (ENABLE_CUSTOMERS_NEWSLETTER != 'true' && !$this->settings[0]['hide_parents']) {
            return '';
        }
        return IncludeTpl::widget(['file' => 'boxes/account/switch-newsletter.tpl', 'params' => [
            'settings' => $this->settings,
            'params' => $this->params,
            'id' => $this->id,
        ]]);
    }
}