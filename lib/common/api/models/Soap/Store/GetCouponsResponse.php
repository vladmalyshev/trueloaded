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


use common\api\models\DataMapBehavior;
use common\api\models\Soap\ArrayOfSearchConditions;
use common\api\models\Soap\Paging;
use common\api\models\Soap\SoapModel;
use common\models\Coupons;
use yii\helpers\ArrayHelper;

class GetCouponsResponse extends SoapModel
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
     * @var \common\api\models\Soap\Paging {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $paging;

    /**
     * @var \common\api\models\Soap\Store\ArrayOfCoupons Array of Coupons {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $coupons;

    /**
     * @var ArrayOfSearchConditions
     */
    public $searchCondition = false;

    public function __construct(array $config = [])
    {
        $this->coupons = new ArrayOfCoupons();
        if ( !is_object($this->paging) ) {
            $this->paging = new Paging([
                'maxPerPage' => 200,
            ]);
        }
        parent::__construct($config);
    }

    public function setSearchCondition(ArrayOfSearchConditions $searchCondition)
    {
        $this->searchCondition = $searchCondition;
    }

    public function build()
    {
        global $languages_id;

        $this->searchCondition->setAllowedOperators([
            '*' => ['=', 'IN'],
            //'last_modified' => ['=', '>', '>=', '<', '<='],
        ]);
        $this->searchCondition->addDateTimeColumn('last_modified');
        $filter_conditions = $this->searchCondition->buildRequestCondition([
            'coupon_id' => 'coupon_id',
            'coupon_type' => 'coupon_type',
            'coupon_code' => 'coupon_code',
            'coupon_active' => 'coupon_active',
//            'access_levels_id' => 'access_levels_id',
        ]);

        if ($filter_conditions === false) {
            $this->error($this->searchCondition->getLastError());
            return;
        }

        $adminListQuery = Coupons::find();
        if ( $filter_conditions ) {
            $adminListQuery->where($filter_conditions);
        }

        $this->paging->applyActiveQuery($adminListQuery);

        $models = $adminListQuery->all();
        //$this->paging->rowsOnPage = count($models);

        foreach ( $models as $model ){
            $this->coupons->coupon[] = Coupon::makeFromAR($model);
        }

        parent::build();
    }

}