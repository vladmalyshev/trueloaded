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

use backend\models\EP;

class Dump implements FormatterInterface
{

  function __construct($config, $filename)
  {

  }

  public function getHeaders()
  {
    // TODO: Implement getHeaders() method.
  }

  public function write_array($data_array)
  {
    echo '<pre>'; var_export($data_array); echo '</pre>';
  }

  public function setReadRemapArray($data_array)
  {
    // TODO: Implement setReadRemapArray() method.
  }

  public function read_array()
  {
    // TODO: Implement read_array() method.
  }

}