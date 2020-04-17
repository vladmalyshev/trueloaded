<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */
namespace common\forms;

use Yii;
use yii\base\Model;
use common\helpers\Address;
use common\helpers\Country;

class ShippingChoice extends Model {
    
    public $choice = 1;
    
    protected $_manager;
    
    public function __construct(\common\services\OrderManager $manager, $config = array()) {
        $this->_manager = $manager;
        if (!$this->_manager->getPickupShippingQuotes()){
            $this->choice = 1;
        }
        parent::__construct($config);
    }
    
    public function rules() {
        return [
            'choice', 
        ];
    }
    
    public function showCustomerChoices(){
        return [
            1 => SHIP_TO_ADDRESS,
            0 => COLLECT_FROM_POINT,
        ];
    }
    
    public function setChoice($choice){
        $this->choice = (int)$choice;
    }
    
    public function getChoice(){
        return $this->choice;
    }
}