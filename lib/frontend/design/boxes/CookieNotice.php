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
use frontend\design\Info;

class CookieNotice extends Widget
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
        Info::addBoxToCss('cookie-notice');

        if (Info::isAdmin()) {
            return '';
        }

        return IncludeTpl::widget(['file' => 'boxes/cookie-notice.tpl', 'params' => [
            'settings' => $this->settings
        ]]);
    }
}