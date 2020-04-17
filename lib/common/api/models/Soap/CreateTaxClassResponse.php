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


use common\api\models\DataMapBehavior;
use common\api\models\Soap\Store\TaxClass;
use yii\db\Expression;

class CreateTaxClassResponse extends SoapModel
{

    /**
     * @var string
     * @soap
     */
    public $status = 'OK';

    /**
     * @var \common\api\models\Soap\ArrayOfMessages Array of Messages {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $messages = [];

    /**
     * @var TaxClass
     */
    protected $taxClassIn;

    /**
     * @var \common\api\models\Soap\Store\TaxClass {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $taxClass;

    public function setTaxClass( $taxClass )
    {
        $this->taxClassIn = $taxClass;
    }

    public function build()
    {
        if ( is_object($this->taxClassIn) ) {

            $obj = new \common\models\TaxClass();

            $obj->attachBehavior('DataMap', [
                'class' => DataMapBehavior::className(),
                //'prop1' => 'value1',
                //'prop2' => 'value2',
            ]);
            $obj->populateAR((array)$this->taxClassIn);
            if ( empty($obj->date_added) || $obj->date_added<1000 ) {
                $obj->date_added = new Expression('NOW()');
            }
            $obj->save();
            $obj->refresh();

            $this->taxClass = new \common\api\models\Soap\Store\TaxClass();
            $obj->populateObject($this->taxClass);
        }
        parent::build();
    }


}