<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\AR\Categories;

use backend\models\EP\Tools;
use common\api\models\AR\EPMap;

class AssignedDepartments extends EPMap
{

    protected $hideFields = [
        'categories_id',
    ];

    public static function tableName()
    {
        return TABLE_DEPARTMENTS_CATEGORIES;
    }

    public static function primaryKey()
    {
        return ['categories_id', 'departments_id'];
    }

    public function parentEPMap(EPMap $parentObject)
    {
        $this->categories_id = $parentObject->categories_id;
        parent::parentEPMap($parentObject);
    }

    public function matchIndexedValue(EPMap $importedObject)
    {
        if ( !is_null($importedObject->departments_id) && !is_null($this->departments_id) && $importedObject->departments_id==$this->departments_id ){
            $this->pendingRemoval = false;
            return true;
        }
        return false;
    }

    public function exportArray(array $fields = [])
    {
        $tools = new Tools();
        $data = parent::exportArray($fields);
        $data['departments_name'] = $tools->getDepartmentsName($this->departments_id);
        return $data;
    }

    public function importArray($data)
    {
        if (isset($data['departments_name'])) {
            $tools = new Tools();
            $data['departments_id'] = $tools->getDepartmentsId($data['departments_name']);
        }
        return parent::importArray($data);
    }

}