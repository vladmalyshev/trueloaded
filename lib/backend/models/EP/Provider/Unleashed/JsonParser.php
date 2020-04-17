<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP\Provider\Unleashed;


use yii\httpclient\Response;

class JsonParser extends \yii\httpclient\JsonParser
{
    protected $localTimeZone;

    public function parse(Response $response)
    {
        $result = parent::parse($response);
        $this->localTimeZone = new \DateTimeZone(date_default_timezone_get());
        array_walk_recursive($result,[$this,'json_new_date']);
        return $result;
    }

    protected function json_new_date(&$val, $key)
    {
        if ( \is_string($val) && \strpos($val,'/Date(')===0 ) {
            $time = substr($val,6,-5);
            $val = new \DateTime(\date('Y-m-d H:i:s',$time)); //, new \DateTimeZone('Zulu')
            $val->setTimezone($this->localTimeZone);
        }
    }
}