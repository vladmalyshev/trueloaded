<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\design\editor;


use Yii;
use yii\base\Widget;
use common\helpers\Acl;

class Attributes extends Widget {
    
    public $attributes;
    public $settings;
    public $complex = false;
    
    public function init(){
        parent::init();
        if (!$this->settings){
            $this->settings['onchange'] = 'getDetails(this)';
        }
    }    
    
    public function run(){
        return $this->render('attributes', [
            'attributes' => $this->attributes,
            'settings' => $this->settings,
            'complex' => $this->complex,
        ]);
    }
    
}
