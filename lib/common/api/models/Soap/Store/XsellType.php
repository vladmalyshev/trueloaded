<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap\Store;


use common\api\models\Soap\ArrayOfLanguageValueMap;
use common\api\models\Soap\LanguageValue;
use common\api\models\Soap\SoapModel;

class XsellType extends SoapModel
{

    /**
     * @var integer
     * @soap
     */
    public $id;

    /**
     * @var \common\api\models\Soap\ArrayOfLanguageValueMap Array of ArrayOfLanguageValueMap {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $names;

    public function __construct(array $config = [])
    {
        if ( isset($config['names']) && is_array($config['names']) ) {
            foreach ($config['names'] as $lang=>$text) {
                $this->names = new ArrayOfLanguageValueMap([]);
                $this->names->language_value[] = new LanguageValue([
                    'language'=>$lang,
                    'text'=>$text,
                ]);
            }
            unset($config['names']);
        }
        parent::__construct($config);
    }


}