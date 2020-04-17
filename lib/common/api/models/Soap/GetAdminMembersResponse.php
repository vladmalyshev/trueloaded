<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap;


use common\api\models\DataMapBehavior;
use common\api\models\Soap\Store\AdminMember;
use common\api\models\Soap\Store\ArrayOfAdminMembers;
use common\models\Admin;
use yii\data\Pagination;

class GetAdminMembersResponse extends SoapModel
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
     * @var \common\api\models\Soap\Store\ArrayOfAdminMembers Array of ArrayOfAdminMembers {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $adminMembers;

    /**
     * @var ArrayOfSearchConditions
     */
    public $searchCondition = false;

    public function __construct(array $config = [])
    {
        $this->adminMembers = new ArrayOfAdminMembers();
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
            'admin_email_address' => 'admin_email_address',
            'admin_firstname' => 'admin_firstname',
            'admin_lastname' => 'admin_lastname',
            'access_levels_id' => 'access_levels_id',
        ]);

        if ($filter_conditions === false) {
            $this->error($this->searchCondition->getLastError());
            return;
        }

        $adminListQuery = Admin::find()->select(['admin_id','admin_firstname','admin_lastname','admin_email_address','access_levels_id']);
        if ( $filter_conditions ) {
            $adminListQuery->where($filter_conditions);
        }

        $this->paging->applyActiveQuery($adminListQuery);

        $models = $adminListQuery->all();
        //$this->paging->rowsOnPage = count($models);

        foreach ( $models as $model ){
            $model->attachBehavior('DataMap', [
                'class' => DataMapBehavior::className(),
            ]);
            $member = new AdminMember();
            $model->populateObject($member);
            $this->adminMembers->admin_member[] = $member;
        }


        parent::build();
    }

}