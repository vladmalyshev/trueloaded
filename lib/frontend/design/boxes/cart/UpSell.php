<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\cart;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use frontend\design\Info;

class UpSell extends Widget
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
        if ($ext = \common\helpers\Acl::checkExtension('UpSell', 'shoppingCart')) {
            $html = $ext::shoppingCart($this->settings);
            if ($html) {
                return $ext::shoppingCart($this->settings);
            }
        }

        return IncludeTpl::widget(['file' => 'boxes/hide-box.tpl','params' => [
            'settings' => $this->settings,
            'id' => $this->id
        ]]);
    }
}