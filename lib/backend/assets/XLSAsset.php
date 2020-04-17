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

class XLSAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        
    ];
    public $js = [
        '@web/../plugins/xls/jszip.js',
        '@web/../plugins/xls/xlsx.js',
        '@web/../plugins/filesaver/FileSaver.js',
        '@web/../plugins/fabricjs/fabric.all.min.js',
    ];
}
