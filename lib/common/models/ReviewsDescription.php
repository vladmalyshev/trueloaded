<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\models;


use yii\db\ActiveRecord;

/**
 * This is the model class for table "reviews_description".
 *
 * @property integer $reviews_id
 * @property integer $languages_id
 * @property string $reviews_text
 */
class ReviewsDescription extends ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{reviews_description}}';
    }

}