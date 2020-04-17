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


use common\api\models\AR\CatalogProperty;
use common\api\models\Soap\Products\CatalogProductProperty;
use yii\helpers\ArrayHelper;

class UpdateCatalogPropertiesResponse extends SoapModel
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
     * @var \common\api\models\Soap\Products\CatalogProductProperty
     * @soap
     */
    public $property;

    /**
     * @var \common\api\models\Soap\Products\CatalogProductProperty
     */
    public $propertyIn;


    public function setCatalogProperty($property)
    {
        $this->propertyIn = json_decode(json_encode($property),true);
        unset($this->propertyIn['parent_id']);

        if ( isset($this->propertyIn['descriptions']) ) {
            $replaceDescription = [];
            if ( is_array($this->propertyIn['descriptions']) && isset($this->propertyIn['descriptions']['description']) ){
                $_replaceDescription = ArrayHelper::isIndexed($this->propertyIn['descriptions']['description'])?$this->propertyIn['descriptions']['description']:[$this->propertyIn['descriptions']['description']];
                foreach ($_replaceDescription as $__replaceDescription){
                    $replaceDescription[$__replaceDescription['language']] = $__replaceDescription;
                }
            }
            $this->propertyIn['descriptions'] = $replaceDescription;
        }
        if ( !$this->propertyIn['properties_id'] ) {
            $this->error('properties_id - required');
        }
    }

    public function build()
    {
        if ( $this->status!='ERROR' ){
            $propertyObj = CatalogProperty::findOne(['properties_id' => $this->propertyIn['properties_id']]);
            if ($propertyObj && $propertyObj->properties_id){
                $propertyObj->importArray($this->propertyIn);
                $propertyObj->save(false);
                $propertyObj->refresh();
                $this->property = new CatalogProductProperty($propertyObj->exportArray([]));
            }else{
                $this->error('Property not found');
            }
        }
        parent::build();
    }

}