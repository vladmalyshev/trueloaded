<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap;


use yii\helpers\ArrayHelper;

class ArrayOfSearchConditions extends SoapModel
{

    /**
     * @var \common\api\models\Soap\SearchCondition SearchCondition {nillable = 1, minOccurs=0, maxOccurs = unbounded}
     * @soap
     */
    public $searchCondition = [];

    /**
     * @var string
     */
    public $error = '';

    protected $allowedOperators = [
        '*' => ['=','IN','NOT IN','>','<','!='],
    ];

    protected $dateTimeColumns = [];

    /**
     * @param string $column
     * @return array
     */
    public function getAllowedOperators($column = '*')
    {
        return isset($this->allowedOperators[$column])?$this->allowedOperators[$column]:[];
    }

    /**
     * @param array $allowedOperators
     */
    public function setAllowedOperators($allowedOperators)
    {
        if ( ArrayHelper::isIndexed($allowedOperators) ) {
            $this->allowedOperators = ['*' => $allowedOperators];
        }else{
            $this->allowedOperators = $allowedOperators;
        }
    }

    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    /**
     * @return array
     */
    public function getSearchConditions()
    {
        if ( is_object($this->searchCondition) ) {
            $this->searchCondition = [$this->searchCondition];
        }
        return $this->searchCondition;
    }

    public function getLastError()
    {
        return $this->error;
    }

    public function buildRequestCondition(array $knownColumns)
    {
        $rawConditions = [];
        foreach($this->getSearchConditions() as $searchCondition)
        {
            $column_allowedOperators = isset($this->allowedOperators[(string)$searchCondition->column])?$this->allowedOperators[(string)$searchCondition->column]:$this->allowedOperators['*'];
            $values = $searchCondition->values;
            if ( is_object($searchCondition->values) ) {
                if (isset($searchCondition->values->value) && is_array($searchCondition->values->value)) {
                    $values = $searchCondition->values->value;
                }elseif( isset($searchCondition->values->value) ){
                    $values = [$searchCondition->values->value];
                }
            }
            if ( count($this->dateTimeColumns)>0 && in_array((string)$searchCondition->column, $this->dateTimeColumns) ) {
                foreach( $values as $idx=>$value ) {
                    $values[$idx] = date('Y-m-d H:i:s', strtotime($value));
                }
            }
            if (empty($searchCondition->column)){
                // empty column name
                $this->error = 'empty column name';
                return false;
            }elseif(!isset($knownColumns[$searchCondition->column])) {
                // column not allowed
                $this->error = 'column "'.$searchCondition->column.'" not allowed. expect one of: '.implode(', ',array_keys($knownColumns));
                return false;
            }elseif ( !in_array(strtoupper($searchCondition->operator), $column_allowedOperators) ) {
                // unknown operator
                $this->error = 'unknown operator - "'.$searchCondition->operator.'" not allowed. expect one of: '.implode(', ',$column_allowedOperators);
                return false;
            }else{

            }
            if ( strtoupper($searchCondition->operator)=='IN' || strtoupper($searchCondition->operator)=='NOT IN' ) {
                $_condition = ' '.strtoupper($searchCondition->operator) . ' (\''.implode("', '",array_map('tep_db_input',$values)).'\')';
            }else{
                $_condition = $searchCondition->operator."'".tep_db_input($values[0])."'";
            }
            if ( strpos($knownColumns[$searchCondition->column],'?')!==false ) {
                $rawConditions[] = str_replace('?',$_condition,$knownColumns[$searchCondition->column]);
            }else{
                $rawConditions[] = $knownColumns[$searchCondition->column].$_condition;
            }
        }
        return implode(' AND ',$rawConditions);
    }

    public function isColumnPresent($columnName)
    {
        $columnInCondition = false;
        foreach ($this->getSearchConditions() as $searchCondition) {
            if ( $searchCondition->column == $columnName ) {
                $columnInCondition = true;
                break;
            }
        }
        return $columnInCondition;
    }

    /**
     * @return array
     */
    public function getDateTimeColumns()
    {
        return $this->dateTimeColumns;
    }

    /**
     * @param array $dateTimeColumns
     */
    public function setDateTimeColumns($dateTimeColumns)
    {
        $this->dateTimeColumns = $dateTimeColumns;
    }

    public function addDateTimeColumn($column)
    {
        $this->dateTimeColumns[] = $column;
    }
}