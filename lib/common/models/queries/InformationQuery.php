<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */
namespace common\models\queries;

use common\models\Information;
use yii\db\ActiveQuery;

class InformationQuery extends ActiveQuery
{

    public function active()
    {
        return $this->andWhere(['visible' => Information::STATUS_ACTIVE]);
    }
    public function blog($typeId)
    {
        return $this->andWhere(['type' => $typeId]);
    }
    public function disable()
    {
        return $this->andWhere(['visible' => Information::STATUS_DISABLE]);
    }
    public function hide($show = Information::STATUS_HIDE)
    {
        if($show === Information::STATUS_HIDE ){
            return $this->andWhere(['hide' => $show]);
        }
        return $this;
    }
}