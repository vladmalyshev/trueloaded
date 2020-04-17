<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\design\boxes;

use Yii;
use yii\base\Widget;

class Sale extends Widget
{

    public $id;
    public $params;
    public $settings;
    public $visibility;

    public function init()
    {
        parent::init();
    }

    public function run()
    {

        global $languages_id;

        $product = tep_db_fetch_array(tep_db_query("
              select products_name
              from " . TABLE_PRODUCTS_DESCRIPTION . "
              where products_id = '" . (int)$this->settings[0]['products_id'] . "' and language_id = '" . (int)$languages_id . "'"));


        return $this->render('sale.tpl', [
            'id' => $this->id,
            'params' => $this->params,
            'settings' => $this->settings,
            'visibility' => $this->visibility,
            'productName' => $product['products_name']
        ]);
    }
}