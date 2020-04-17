<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\pdf;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;

class CategoryImage extends Widget
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

        $categoryImage = '';
        if (is_file(DIR_FS_CATALOG . 'images/' . $this->params['categoryImage'])) {
            if (function_exists('tep_catalog_href_link')) {
                $categoryImage = '<img src="' . tep_catalog_href_link('images/' . $this->params['categoryImage']) . '" border="0">';
            } else {
                $categoryImage = '<img src="' . tep_href_link('images/' . $this->params['categoryImage']) . '" border="0">';
            }
        }
        return $categoryImage;
    }
}