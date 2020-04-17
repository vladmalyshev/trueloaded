<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\forms\catalogpages;

use yii\base\Model;

class AssignInformationForm extends Model {

	public $information_id;
	public $page_title;
	public $hide;

    public function rules()
    {
        return [
            [['information_id'], 'required'],
            ['information_id', 'integer', 'min' => 1],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'information_id' => TEXT_INFORMATION,
        ];
    }

}

