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


use common\api\models\Soap\Products\Manufacturer as SoapBrand;
use yii\helpers\ArrayHelper;

class UpdateManufacturerResponse extends SoapModel
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
     * @var \common\api\models\Soap\Products\Manufacturer
     * @soap
     */
    public $manufacturer;

    protected $dataIn;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    public function setManufacturer(SoapBrand $brandObj)
    {
        $brandData = json_decode(json_encode($brandObj),true);

        if ( isset($brandData['info_array']) && isset($brandData['info_array']['info']) ){
            $info_array = ArrayHelper::isIndexed($brandData['info_array']['info'])?$brandData['info_array']['info']:[$brandData['info_array']['info']];
            unset($brandData['info_array']);
            $brandData['infos'] = [];
            foreach ($info_array as $info){
                $brandData['infos'][$info['language']] = $info;
            }
        }

        if ( isset($brandData['date_added']) && $brandData['date_added']>1000 ) {
            $brandData['date_added'] = date('Y-m-d H:i:s', strtotime($brandData['date_added']));
        }
        if ( isset($brandData['last_modified']) && $brandData['last_modified']>1000 ) {
            $brandData['last_modified'] = date('Y-m-d H:i:s', strtotime($brandData['last_modified']));
        }

        $this->dataIn = $brandData;
    }

    public function build()
    {
        if ( !empty($this->dataIn) ) {
            $dbObj = \common\api\models\AR\Manufacturer::findOne(['manufacturers_id' => $this->dataIn['manufacturers_id']]);
            if (!is_object($dbObj)) {
                $this->error('Manufacturer not found');
            } else {
                $dbObj->importArray($this->dataIn);
                $dbObj->save(false);
                $dbObj->refresh();
                $newDataArray = $dbObj->exportArray([]);
                $this->manufacturer = new SoapBrand($newDataArray);
            }
        }

        parent::build();
    }

}