<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP;


class Transform
{
    protected $columnMap = [];
    protected $mapping = [];

    public function setProviderColumns($columns)
    {
        $this->columnMap = $columns;
        if ( count($this->mapping)==0 ) $this->mapping = array_flip($columns);
    }

    public function setTransformMap($external)
    {
        $this->mapping = $external;
    }

    public function transform($data)
    {
        if ( !is_array($data) ) return $data;

        $transformedData = [];
        foreach( $this->mapping as $file_key=>$db_key ) {
            if ( !array_key_exists($file_key, $data) ) continue;
            $transformedData[$db_key] = $data[$file_key];
        }

        return $transformedData;
    }
}