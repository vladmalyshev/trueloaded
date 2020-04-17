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


use common\api\models\AR\Supplier;

class GetSupplierResponse extends SoapModel
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
     * @var \common\api\models\Soap\Supplier\Supplier {nillable = 1, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $supplier;

    public function setSupplierId($supplierId){
        $supplierObj = Supplier::findOne(['suppliers_id'=>$supplierId]);
        if ( $supplierObj && $supplierObj->suppliers_id ) {
            $this->supplier = new \common\api\models\Soap\Supplier\Supplier( $supplierObj->exportArray([]) );
        }else{
            $this->error('Supplier not found');
        }
    }

    public function build()
    {
        parent::build();
    }


}