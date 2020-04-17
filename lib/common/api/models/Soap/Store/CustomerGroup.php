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

class CustomerGroup extends SoapModel
{

    /**
     * @var integer {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $groups_id;

    /**
     * @var string {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $groups_name;

    /**
     * @var double {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $discount_percent;

    /**
     * @var boolean {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $is_tax_applicable;

    /**
     * @var boolean {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $is_reseller;
    /**
     * @var boolean {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $checkout_disabled;

    /**
     * @var boolean {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $show_price;
    /**
     * @var boolean {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $new_customer_need_approve;

    /**
     * @var boolean {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $apply_groups_discount_to_specials;

    /**
     * @var boolean {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $disable_catalog_watermark;

    /**
     * @var datetime {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $date_added;

    /**
     * @var datetime {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $last_modified;

    protected $_castType = [
        'date_added' => ['datetime',true],
        'last_modified' => ['datetime',true],
    ];

    public static function makeFromAR($group)
    {
        $ar_data = $group->getAttributes();
        $data = [
            'groups_id' => $ar_data['groups_id'],
            'groups_name' => $ar_data['groups_name'],
            'discount_percent' => $ar_data['groups_discount'],
            'is_tax_applicable' => $ar_data['groups_is_tax_applicable'],
            'is_reseller' => $ar_data['groups_is_reseller'],
            'checkout_disabled' => $ar_data['groups_disable_checkout'],
            'show_price' => $ar_data['groups_is_show_price'],
            'new_customer_need_approve' => $ar_data['new_approve'],
            'apply_groups_discount_to_specials' => $ar_data['apply_groups_discount_to_specials'],
            'disable_catalog_watermark' => $ar_data['disable_watermark'],
            'date_added' => $ar_data['date_added'],
            'last_modified' => $ar_data['last_modified'],
        ];

        return new static($data);
    }
}