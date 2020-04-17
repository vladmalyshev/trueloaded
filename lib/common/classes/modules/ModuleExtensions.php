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

class ModuleExtensions extends Module{
    
    public function __construct(){
        $ref = new \ReflectionClass(get_called_class());
        $this->code = $ref->getShortName();
        $this->namespace = $ref->getNamespaceName();
        $this->title = \yii\helpers\Inflector::camel2words($this->code);
        $this->isExtension = true;
    }
    
    public function install($platform_id){
        return parent::install($platform_id);
    }
    
    public function remove($platform_id) {
        return parent::remove($platform_id);
    }
    
    public function describe_status_key(){
        return new ModuleStatus($this->code . '_EXTENSION_STATUS', 'True', 'False');
    }
    
    public function describe_sort_key(){}
    
    public function configure_keys(){ 
        return [
            $this->code . '_EXTENSION_STATUS' => [
                'title' => $this->title . ' status',
                'value' => 'False',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
            ]
        ];
    }
    
    public static function enabled(){
        $class = (new \ReflectionClass(get_called_class()))->getShortName();
        return defined($class . '_EXTENSION_STATUS') && constant($class . '_EXTENSION_STATUS') == 'True';
    }

    public static function getMetaTagKeys($meta_tags)
    {
        return $meta_tags;
    }

}
