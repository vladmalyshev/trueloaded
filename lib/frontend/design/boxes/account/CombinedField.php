<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\account;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use frontend\design\Info;
use common\models\AdditionalFields;
use common\models\AdditionalFieldsDescription;
use common\models\CustomersAdditionalFields;

class CombinedField extends Widget
{

    public $file;
    public $params;
    public $settings;

    public function init()
    {
        parent::init();
    }

    public function run()
    {

        $content = preg_replace_callback('|[a-zA-Z\-\_]+|', function($matches){
            global $languages_id;

            $field = CustomersAdditionalFields::find()
                ->alias('cf')
                ->select('cf.value')
                ->leftJoin('additional_fields f', 'cf.additional_fields_id = f.additional_fields_id')
                ->where([
                    'f.additional_fields_code' => $matches[0],
                ])
                ->asArray()
                ->one();

            return  $field['value'];

        }, $this->settings[0]['fields']);

        return $content;
    }
}
