<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\services\storages;
use Yii;

class ObjectStorage implements StorageInterface {
    
    protected $_pointer = null;
    
    private $_storageName = '_tlStorageId';
    private $_storageID;
    
    private $data = [];
    
    public function __construct() {
        $this->_storageID = Yii::$app->security->generateRandomString();
    }
    
    public function setPointer(string $pointer){
        $this->_storageID = $pointer;
    }
    
    public function getPointer(){
        return $this->_storageID;
    }
    
    public function pointerShifted(){
        return false;
    }

    public function get($name){
        $var = $this->_get();
        return isset($var[$name]) ? $var[$name] : null;
    }
    
    public function getAll(){
        return $this->_get();
    }

    public function set($name, $value){
        $this->data[$name] = $value;
    }
    
    private function _get(){
        return $this->data;
    }
    
    public function has($name){
        return isset($this->data[$name]);
    }
    
    public function remove($name){
        if ($this->has($name)){
            $var = $this->_get();
            unset($var[$name]);
            $this->data = $var;
        }
    }
    
    public function removeAll(){
        $this->data = [];
    }
}