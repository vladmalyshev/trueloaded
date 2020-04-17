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


use common\api\models\Soap\SoapModel;

class ArrayOfXsellType extends SoapModel
{

    /**
     * @var \common\api\models\Soap\Store\XsellType XsellType {nillable = 0, minOccurs=0, maxOccurs = unbounded}
     * @soap
     */
    public $xsell_type = [];

    public function __construct(array $config = [])
    {
        if ( defined('TABLE_PRODUCTS_XSELL_TYPE') ) {
            $types = [];
            $get_types_r = tep_db_query("SELECT * FROM " . TABLE_PRODUCTS_XSELL_TYPE . " WHERE 1 ORDER BY xsell_type_id, language_id");
            if (tep_db_num_rows($get_types_r) > 0) {
                while ($xsell_type = tep_db_fetch_array($get_types_r)) {
                    $xsell_type['language'] = \common\classes\language::get_code($xsell_type['language_id']);
                    if (!isset($types[$xsell_type['xsell_type_id']])) {
                        $types[$xsell_type['xsell_type_id']] = [
                            'id' => $xsell_type['xsell_type_id'],
                            'names' => []
                        ];
                    }
                    $types[$xsell_type['xsell_type_id']]['names'][$xsell_type['language']] = $xsell_type['xsell_type_name'];
                }
            }

            foreach ($types as $type) {
                $this->xsell_type[] = new XsellType($type);
            }
        }
        parent::__construct();
    }


    public function build()
    {
        parent::build();
    }

}