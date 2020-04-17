<?php

/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\classes\modules;

class ModuleBuilder {
    
    public $manager;
    
    public function __construct(\common\services\OrderManager $manager = null) {
        $this->manager = $manager;
    }

    public function __invoke($args) {
        if (!isset($args['class'])) throw new \Exception ('class name is not defined');
        
        $class = new \ReflectionClass($args['class']);
        $object = $class->newInstanceWithoutConstructor();
        $object->manager = $this->manager;
        if (is_object($this->manager)){
            if ($_delivery = $this->manager->getDeliveryAddress()){
                $object->setDelivery($_delivery);
            }

            if ($_billing = $this->manager->getBillingAddress()){
                $object->setBilling($_billing);
            }
        }
        
        $object->__construct();
        return $object;
    }
    
}
