<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap\Categories;


use common\api\models\Soap\SoapModel;

class ArrayOfCategoryDescription extends SoapModel
{
    /**
     * @var \common\api\models\Soap\Categories\CategoryDescription CategoryDescription {nillable = 0, minOccurs=0, maxOccurs = unbounded}
     * @soap
     */
    public $description = [];

    public function __construct(array $config = [])
    {
        foreach( $config as $key=>$descriptionData ) {
            list($language,$affiliate_id) = explode('_',$key);
            if ( (int)$affiliate_id!=0 ) continue;
            $descriptionData['language'] = $language;
            $this->description[] = new CategoryDescription($descriptionData);
        }
        parent::__construct([]);
    }
}