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

use common\models\CatalogPages;
use yii\db\ActiveQuery;

class CatalogPagesQuery extends ActiveQuery
{
    public function active()
    {
        return $this->andWhere(['status' => CatalogPages::STATUS_ACTIVE]);
    }

}