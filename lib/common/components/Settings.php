<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\components;

use Yii;
use yii\base\Component;

class Settings extends Component {
    
    public $sessionKey = 'settings';
    private $data = [];
    
    public function has($variable) {
        $this->load();
        if (isset($this->data[$variable])) {
            return true;
        }
        return false;
    }
    
    public function get($variable) {
        $this->load();
        if (isset($this->data[$variable])) {
            return $this->data[$variable];
        }
        return false;
    }
    
    public function set($variable, $value) {
        $this->load();
        $this->data[$variable] = $value;
        $this->save();
        return true;
    }
    
    public function getAll()
    {
        $this->load();
        return $this->data;
    }
    
    public function setAll(array $data) {
        $this->data = $data;
        $this->save();
        return true;
    }
    
    public function remove($variable) {
        $this->load();
        if (isset($this->data[$variable])) {
            unset($this->data[$variable]);
            $this->save();
            return true;
        }
        return false;
    }
    
    public function clear() {
        $this->data = [];
        $this->save();
        return true;
    }
                
    private function load()
    {
        if (Yii::$app instanceof \yii\console\Application) {
            $this->data = $_SESSION[$this->sessionKey];
        } else {
            if (Yii::$app->storage->pointerShifted()){
                $this->data = Yii::$app->storage->getAll();
            } else {
                $this->data = Yii::$app->session->get($this->sessionKey, []);
            }
        }
    }

    private function save()
    {
        if (Yii::$app instanceof \yii\console\Application) {
            $_SESSION[$this->sessionKey] = $this->data;
        } else {
            if (!Yii::$app->storage->pointerShifted()){
                Yii::$app->session->set($this->sessionKey, $this->data);
            }
        }
    }
}