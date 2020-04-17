<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\assets;

use yii\web\AssetBundle;

class AppAsset extends AssetBundle
{
    public $sourcePath = '@app/themes/basic/js/boxes';
    public $baseUrl = '@web/themes/basic/js';
    public $js = [
        'edit-blocks.js',
    ];

    public function init()
    {
        parent::init();

        foreach (\frontend\design\Block::$widgetsList as $name) {
            $this->js[] = $name . '.js';
        }
    }
}
