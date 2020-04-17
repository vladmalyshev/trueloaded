<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\product;

use Yii;
use yii\base\Widget;
use frontend\design\Info;

class ButtonsSample extends Widget
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
        if ($ext = \common\helpers\Acl::checkExtensionAllowed('Samples', 'allowed')) {
            return $ext::productBlock();
        } elseif (Info::isAdmin()) {
            return 'Request for sample Button (Samples not installed)';
        } else {
            return '';
        }
    }
}