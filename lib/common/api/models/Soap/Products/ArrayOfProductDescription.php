<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap\Products;


use common\api\models\Soap\SoapModel;
use common\api\SoapServer\ServerSession;

class ArrayOfProductDescription extends SoapModel
{

    /**
     * @var \common\api\models\Soap\Products\ProductDescription ProductDescription {nillable = 0, minOccurs=0, maxOccurs = unbounded}
     * @soap
     */
    public $description = [];

    public function __construct(array $config = [])
    {
        foreach( $config as $key=>$descriptionData ) {
            list($language, $platform_id) = explode('_',$key);
            if (false && ServerSession::get()->getDepartmentId() ){

            }elseif (ServerSession::get()->getPlatformId() ){
                if ( (int)$platform_id!=ServerSession::get()->getPlatformId() ) continue;
            }
            $descriptionData['language'] = $language;
            $this->description[] = new ProductDescription($descriptionData);
        }
        parent::__construct([]);
    }


}