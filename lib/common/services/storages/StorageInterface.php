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

interface StorageInterface {
    
    public function getPointer();

    public function setPointer(string $pointer);
    
    public function pointerShifted();

    public function get($name);
    
    public function getAll();

    public function set($name, $value);
    
    public function has($name);
    
    public function remove($name);
    
    public function removeAll();
    
}