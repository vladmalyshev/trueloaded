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

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "cloud_printers_documents".
 *
 * @property integer $id
 * @property integer $printer_id 
 * @property string $document_name
 */
class CloudPrintersDocuments extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cloud_printers_documents';
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['printer_id'], 'required'],
            [['document_name'], 'string'],
        ];
    }
    
    public static function create($printer_id, $document_name){
        $check = static::findOne(['printer_id' => (int)$printer_id, 'document_name' => $document_name]);
        if (!$check){
            $check = new static();
            $check->printer_id = (int)$printer_id;
            $check->document_name = $document_name;
        }
        return $check;
    }
}
