<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP\Formatter;

interface FormatterInterface {

  public function write_array($data_array);

  public function getHeaders();
  public function setReadRemapArray($data_array);
  public function read_array();

}