<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\checkout;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;

class Shipping extends Widget
{

    public $file;
    public $params;
    public $settings;
    public $manager;

    public function init()
    {
        parent::init();
    }

    public function run()
    {
        if (is_object($this->manager)){
            $this->params['manager'] = $this->manager;
        }
        if (is_object($this->params['manager'])){
            if (!$this->params['manager']->isChargedOrder()) return '';
        }        

        return IncludeTpl::widget([
              'file' => 'boxes/checkout/shipping.tpl',
              'params' => array_merge($this->params, [
                'settings' => $this->settings,
                'id' => $this->id,
            ])]);
    }
}