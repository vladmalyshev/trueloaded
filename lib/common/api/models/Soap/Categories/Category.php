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
use common\api\models\Soap\Store\ArrayOfAssignedCustomerGroup;
use common\api\models\Soap\Store\ArrayOfAssignedPlatform;
use common\api\SoapServer\ServerSession;

class Category extends SoapModel
{

    /**
     * @var integer {minOccurs=0, maxOccurs=1}
     * @soap
     */
    public $categories_id;

    /**
     * @var integer
     * @soap
     */
    public $parent_id;

    /**
     * @var integer
     * @soap
     */
    public $sort_order;

    /**
     * @var integer
     * @soap
     */
    public $categories_status;

    /**
     * @var string
     * @soap
     */
    public $categories_image;
    /**
     * @var string
     * @soap
     */
    public $categories_image_source_url;

    /**
     * @var string
     * @soap
     */
    public $categories_image_2;
    /**
     * @var string
     * @soap
     */
    public $categories_image_2_source_url;

    /**
     * @var datetime
     * @soap
     */
    public $date_added;

    /**
     * @var datetime {nillable = 1, minOccurs=0, maxOccurs=1}
     * @soap
     */
    public $last_modified;

    /**
     * @var \common\api\models\Soap\Store\ArrayOfAssignedPlatform Array of AssignedPlatform {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $assigned_platforms;

    /**
     * @var \common\api\models\Soap\Store\ArrayOfAssignedCustomerGroup Array of ArrayOfAssignedCustomerGroup {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $assigned_customer_groups;

    /**
     * @var \common\api\models\Soap\Categories\ArrayOfCategoryDescription Array of CategoryDescription {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $descriptions;

    /**
     * @var string {minOccurs=0, maxOccurs=1}
     * @soap
     */
    public $order_deadline;

    /**
     * @var string {minOccurs=0, maxOccurs=1}
     * @soap
     */
    public $dispatch_date;

    /**
     * @var string {minOccurs=0, maxOccurs=1}
     * @soap
     */
    public $sap_project_code;


    /**
     * @var string {minOccurs=0, maxOccurs=1}
     * @soap
     */
    public $sap_export_mode;

    public function __construct(array $config = [])
    {
        if ( isset($config['descriptions']) ) {
            $this->descriptions = new ArrayOfCategoryDescription($config['descriptions']);
            unset($config['descriptions']);
        }

        if ( !ServerSession::get()->acl()->siteAccessPermission() ) {
            unset($config['assigned_platforms']);
            unset($config['assigned_customer_groups']);
        }else{
            $config['assigned_platforms'] = new ArrayOfAssignedPlatform($config['assigned_platforms']);
            $config['assigned_customer_groups'] = new ArrayOfAssignedCustomerGroup($config['assigned_customer_groups']);
        }

        parent::__construct($config);
        if ( !empty($this->date_added) ) {
            $this->date_added = \common\api\SoapServer\SoapHelper::soapDateTimeOut($this->date_added);
        }
        if ( !empty($this->last_modified) ) {
            $this->last_modified = \common\api\SoapServer\SoapHelper::soapDateTimeOut($this->last_modified);
        }

    }


}