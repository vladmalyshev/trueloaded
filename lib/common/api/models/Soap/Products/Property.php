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
use common\api\models\Soap\ArrayOfLanguageValueMap;
use common\api\models\Soap\LanguageValue;

class Property extends SoapModel
{

    /**
     * @var integer {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $properties_id;

    /**
     * @var integer {nillable=1}
     * @soap
     */
    public $values_flag;

    /**
     * @var \common\api\models\Soap\ArrayOfLanguageValueMap Array of ArrayOfLanguageValueMap {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $names;

    /**
     * @var \common\api\models\Soap\ArrayOfLanguageValueMap Array of ArrayOfLanguageValueMap {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $name_path;

    /**
     * @var \common\api\models\Soap\ArrayOfLanguageValueMap Array of ArrayOfLanguageValueMap {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $values;

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
        if ( isset($config['name_path']) && is_array($config['name_path']) ) {
            $this->name_path = new ArrayOfLanguageValueMap([]);
            foreach ($config['name_path'] as $lang=>$text) {
                $this->name_path->language_value[] = new LanguageValue([
                    'language'=>$lang,
                    'text'=>$text,
                ]);
            }
            unset($config['name_path']);
        }
        if ( isset($config['values']) && is_array($config['values']) ) {
            $this->values = new ArrayOfLanguageValueMap([]);
            foreach ($config['values'] as $lang=>$text) {
                $this->values->language_value[] = new LanguageValue([
                    'language'=>$lang,
                    'text'=>$text,
                ]);
            }
            unset($config['values']);
        }

        parent::__construct($config);
    }


}