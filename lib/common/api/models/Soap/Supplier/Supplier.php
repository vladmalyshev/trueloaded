<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap\Supplier;


use common\api\models\Soap\SoapModel;

class Supplier extends SoapModel
{

    /**
     * @var integer {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $suppliers_id;

    /**
     * @var string
     * @soap
     */
    public $suppliers_name;

    /**
     * @var float
     * @-soap
     */
    public $suppliers_surcharge_amount;

    /**
     * @var float
     * @-soap
     */
    public $suppliers_margin_percentage;

    /**
     * @var string
     * @-soap
     */
    public $script;

    /**
     * @var datetime {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $date_added;

    /**
     * @var datetime {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $last_modified;

    /**
     * @var boolean {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $is_default;

    /**
     * @var string {minOccurs=0, maxOccurs = 1}
     * @-soap
     */
    public $price_formula;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        if ( !empty($this->date_added) ) {
            $this->date_added = \common\api\SoapServer\SoapHelper::soapDateTimeOut($this->date_added);
        }
        if ( !empty($this->last_modified) ) {
            $this->last_modified = \common\api\SoapServer\SoapHelper::soapDateTimeOut($this->last_modified);
        }

    }

    public function makeARArray()
    {
        $data = [];
        foreach (\Yii::getObjectVars($this) as $key=>$val ) {
            if ($key=='date_added' || $key=='last_modified') {
                if ( $val>2000 ) $val = date('Y-m-d H:i:s', strtotime($val));
            }
            $data[$key] = $val;
        }
        return $data;
    }

}