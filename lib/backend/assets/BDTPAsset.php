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

class BDTPAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        '@web/../plugins/bootstrap-datepicker/bootstrap-datetimepicker.min.css',
    ];
    public $js = [
        '@web/../plugins/moment/moment.js',
        '@web/../plugins/bootstrap-datepicker/bootstrap-datetimepicker.min.js',
        '@web/../plugins/bootstrap-datepicker/moment-with-locales.js',
    ];
}
