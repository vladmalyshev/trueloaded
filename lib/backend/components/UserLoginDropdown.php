<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\components;

use yii\base\Widget;

class UserLoginDropdown extends Widget {

    public $adminFullname = '';
    public $adminAvatar = '';
    
    public function run() {
        $check_admin_query = tep_db_query("select * from " . TABLE_ADMIN . " where admin_id = " . (int)tep_session_var('login_id') . "");
        $check_admin = tep_db_fetch_array($check_admin_query);
        
        $this->adminFullname = $check_admin['admin_firstname'] . ' ' . $check_admin['admin_lastname'];				
				
        $this->adminAvatar = '<span class="avatar' . ((@GetImageSize(DIR_FS_CATALOG_IMAGES . $check_admin['avatar']) > 0) ? '':' avatar_noimg') . '">' . ((@GetImageSize(DIR_FS_CATALOG_IMAGES . $check_admin['avatar']) > 0) ? tep_image(DIR_WS_CATALOG_IMAGES . $check_admin['avatar'], $check_admin['admin_firstname'] . " " . $check_admin['admin_lastname']) : '<i class="icon-user"></i>') . '</span>';
        
        return $this->render('UserLoginDropdown', [
          'context' => $this,
        ]);
    }

}

