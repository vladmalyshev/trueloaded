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

class Breadcrumb extends Widget
{

    public $params;
    public $settings;

    public function init()
    {
        parent::init();
    }

    public function run()
    {
        global $breadcrumb;
        $breadcrumb_trail = $breadcrumb->trail();

        $count = 1;
        foreach ($breadcrumb_trail as $item) {
            \frontend\design\JsonLd::addData(['BreadcrumbList' => [
                'itemListElement' => [[
                    '@type' => 'ListItem',
                    'position' => $count,
                    'item' => ['@id' => $item['link'], 'name' => $item['name']]
                ]]
            ]]);
            $count++;
        }

        return IncludeTpl::widget(['file' => 'boxes/breadcrumb.tpl', 'params' => [
            'breadcrumb' => $breadcrumb_trail,
            'settings' => $this->settings
        ]]);
    }
}