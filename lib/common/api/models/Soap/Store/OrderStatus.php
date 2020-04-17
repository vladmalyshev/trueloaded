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

class OrderStatus extends SoapModel
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

    /**
     * @var integer {nillable = 1}
     * @soap
     */
    public $group_id;

    /**
     * @var \common\api\models\Soap\ArrayOfLanguageValueMap Array of ArrayOfLanguageValueMap {nillable = 1, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $group_names;


    /**
     * @var integer {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $external_status_id;

    /**
     * @var string {nillable = 1, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $color;

    /**
     * @var integer {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $createInGroup;

    public function __construct(array $config = [])
    {
        if ( isset($config['names']) && is_array($config['names']) ) {
            $this->names = new ArrayOfLanguageValueMap([]);
            foreach ($config['names'] as $lang=>$text) {
                $this->names->language_value[] = new LanguageValue([
                    'language'=>$lang,
                    'text'=>$text,
                ]);
            }
            unset($config['names']);
        }

        if ( isset($config['group_names']) && is_array($config['group_names']) ) {
            $this->group_names = new ArrayOfLanguageValueMap([]);
            foreach ($config['group_names'] as $lang=>$text) {
                $this->group_names->language_value[] = new LanguageValue([
                    'language'=>$lang,
                    'text'=>$text,
                ]);
            }
            unset($config['group_names']);
        }

        parent::__construct($config);
    }

}