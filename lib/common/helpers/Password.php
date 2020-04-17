<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\helpers;

class Password {

    public static function validate_password($plain, $encrypted) {
        if (tep_not_null($plain) && tep_not_null($encrypted)) {
            $stack = explode(':', $encrypted);
            if (sizeof($stack) != 2)
                return false;
            if (md5($stack[1] . $plain) == $stack[0]) {
                return true;
            }
        }

        return false;
    }

    public static function rand($min = null, $max = null) {
        static $seeded;

        if (!isset($seeded)) {
            mt_srand((double) microtime() * 1000000);
            $seeded = true;
        }

        if (isset($min) && isset($max)) {
            if ($min >= $max) {
                return $min;
            } else {
                return mt_rand($min, $max);
            }
        } else {
            return mt_rand();
        }
    }

    public static function encrypt_password($plain) {
        $password = '';
        for ($i = 0; $i < 10; $i++) {
            $password .= self::rand();
        }
        $salt = substr(md5($password), 0, 2);
        $password = md5($salt . $plain) . ':' . $salt;
        return $password;
    }

    public static function create_random_value($length, $type = 'mixed') {
        if (($type != 'mixed') && ($type != 'chars') && ($type != 'digits'))
            return false;

        $rand_value = '';
        while (strlen($rand_value) < $length) {
            if ($type == 'digits') {
                if (empty($rand_value)){
                    $char = self::checkFirst(self::rand(0, 9));
                } else {
                    $char = self::rand(0, 9);
                }
            } else {
                $char = chr(self::rand(0, 255));
            }
            if ($type == 'mixed') {
                if (preg_match('/^[a-z0-9]$/i', $char))
                    $rand_value .= $char;
            } elseif ($type == 'chars') {
                if (preg_match('/^[a-z]$/i', $char))
                    $rand_value .= $char;
            } elseif ($type == 'digits') {
                if (preg_match('/^[0-9]$/', $char))
                    $rand_value .= $char;
            }
        }

        return $rand_value;
    }
    
    public static function checkFirst($value){
        if (!$value){
            do {
                $value = self::rand(0, 9);
            } while(!$value);
        }
        return $value;
    }

}
