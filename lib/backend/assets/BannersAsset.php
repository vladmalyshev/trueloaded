<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\assets;

use yii\web\AssetBundle;

class BannersAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web/themes/basic';
    public $css = [
        'css/banners.css',
    ];
    /*public $js = [
      'js/image-map/edit.js',
    ];*/
}
