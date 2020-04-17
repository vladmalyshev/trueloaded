<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\design\boxes\account;

use Yii;
use yii\base\Widget;
use common\models\AdditionalFields;
use common\models\AdditionalFieldsDescription;
use common\models\AdditionalFieldsGroup;
use common\models\AdditionalFieldsGroupDescription;

class AddressesList extends Widget
{

    public $id;
    public $params;
    public $settings;
    public $visibility;

    public function init()
    {
        parent::init();
    }

    public function run()
    {
        global $languages_id;

        $groups = AdditionalFieldsGroupDescription::find()
            ->select(['id' => 'additional_fields_group_id', 'title'])
            ->where(['language_id' => $languages_id])
            ->asArray()->all();

        return $this->render('../../views/account/addresses-list.tpl', [
            'id' => $this->id,
            'params' => $this->params,
            'settings' => $this->settings,
            'visibility' => $this->visibility,
            'groups' => $groups,
        ]);
    }
}