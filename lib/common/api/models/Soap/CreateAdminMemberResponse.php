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

class CreateAdminMemberResponse extends SoapModel
{

    /**
     * @var \common\api\models\Soap\Store\AdminMember
     * @soap
     */
    public $adminMember;

    protected $adminIn;

    public function setAdminMember(AdminMember $admin)
    {
        $this->adminIn = $admin;
    }

    public function build()
    {
        $model = new \common\models\Admin();
        $model->attachBehavior('DataMap', [
            'class' => DataMapBehavior::className(),
        ]);
        $model->populateAR($this->adminIn);

        if ($model->save(false)) {
            $model->refresh();

            $this->adminMember = new AdminMember();
            $model->populateObject($this->adminMember);
        }else{
            $s = $model->getErrors();
            echo '<pre>'; var_dump($s); echo '</pre>'; die;
        }

        parent::build();
    }


}