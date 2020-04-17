<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models;

use Yii;
use yii\base\Model;

class Admin {

    protected $info;

    public function __construct($id = 0) {

        if ($id) {
            $this->info = tep_db_fetch_array(tep_db_query("select * from " . TABLE_ADMIN . " where admin_id = '" . (int) $id . "'"));
        } else {
            $this->info = tep_db_fetch_array(tep_db_query("select * from " . TABLE_ADMIN . " where admin_id = '" . (int) Yii::$app->session->get('login_id') . "'"));
        }
    }

    public function getInfo($field) {
        if (is_array($this->info)) {
            return $this->info[$field];
        }
        return;
    }

    public function saveAdditionalInfo($data) {
        $this->_save('additional_info', serialize($data));
    }

    public function getAdditionalInfo() {

        $_info = unserialize($this->info['additional_info']);

        if (!$_info) {
            $_info = [];
        }

        return $_info;
    }

    private function _save($field, $data) {

        if ($this->info['admin_id']) {
            tep_db_query("update " . TABLE_ADMIN . " set {$field} = '" . tep_db_input($data) . "' where admin_id = '" . (int) $this->info['admin_id'] . "'");
        }

        return;
    }

}
