<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

  function tep_trim($string)
  {
    if (is_string($string)) {
        return trim($string);
    } elseif (is_array($string)) {
      foreach ($string as $key => $value) {
        $string[$key] = tep_trim($value);
      }
      return $string;
    } else {
      return $string;
    }
  }

  class objectInfo {

// class constructor
    function __construct($object_array, $trim = true, $call_tep_db_prepare_input = false) {
      if (is_array($object_array)){
        foreach ($object_array as $key => $value) {
          if($call_tep_db_prepare_input != true)
          {
            if($trim)
                $this->$key = tep_trim($value);
            else
              $this->$key = $value;
          }
          else
          {
            $this->$key = tep_db_prepare_input($value, $trim);
          }
        }
      }
    }
  }
