<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\forms;


use common\models\Platforms;
use yii\base\Model;

/**
 * Class ProductsNotesForm
 * @package backend\models\forms
 */
final class ProductsNotesForm extends Model
{
    /** @var string $note */
	public $note;

    public function rules()
    {
        return [
            [['note'], 'required'],
            [['note'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'note' => 'Product Note',
        ];
    }

    /**
     * @return array
     */
    public function __toArray(): array
    {
        return json_decode(json_encode($this), true);
    }
}

