<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\invoice;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use frontend\design\Info;

class Totals extends Widget {

    public $id;
    public $file;
    public $params;
    public $settings;

    public function init() {
        parent::init();
    }

    public function run() {
        
        /*to get correct settings */
        if ($this->params['order']->manager->getPlatformId() != $this->params['order']->info['platform_id']){
            $this->params['order']->manager->set('platform_id', $this->params['order']->info['platform_id']);
        }
        
        $order_total_output = $this->params['order']->manager->wrapTotals($this->params['order']->totals, 'TEXT_INVOICE');

        return $this->renderFile(Yii::$aliases['@frontend']. '/themes/basic/boxes/invoice/totals.tpl', [
                'order' => $this->params['order'],
                'order_total_output' => $order_total_output,
                'currencies' => $this->params['currencies'],
                'to_pdf' => ($_GET['to_pdf'] ? 1 : 0),
                'width' => Info::blockWidth($this->id)
        ]);
    }
}
