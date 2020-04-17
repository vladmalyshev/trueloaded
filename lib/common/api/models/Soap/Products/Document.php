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

class Document extends SoapModel
{

    /**
     * @var integer
     * @soap
     */
    public $document_types_id;

    /**
     * @var string
     * @soap
     */
    public $document_types_name;

    /**
     * @var integer
     * @soap
     */
    public $sort_order;

    /**
     * @var string
     * @soap
     */
    public $filename;

    /**
     * @var integer  {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $is_link;

    /**
     * @var \common\api\models\Soap\Products\ArrayOfDocumentDescriptions Array of DocumentDescription {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $descriptions;

    /**
     * @var string {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $document_url;

    /**
     * @var int {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $document_modify_time;

    public function __construct(array $config = [])
    {
        if ( isset($config['titles']) && is_array($config['titles'])){
            $this->descriptions = new ArrayOfDocumentDescriptions($config['titles']);
        }
        parent::__construct($config);
    }


}