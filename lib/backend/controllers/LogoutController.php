<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\controllers;

use Yii;
use yii\web\Controller;

/**
 * default controller to handle user requests.
 */
class LogoutController extends Controller
{
	/**
	 * Index action is the default action in a controller.
	 */
	public function actionIndex()
	{
            global $login_id, $device_hash;
            if (!tep_session_is_registered('admin_multi_session_error')) {
                $adminLoginLogRecord = new \common\models\AdminLoginLog();
                $adminLoginLogRecord->all_event = 20;
                $adminLoginLogRecord->all_device_id = $device_hash;
                $adminLoginLogRecord->all_ip = '';
                $adminLoginLogRecord->all_agent = '';
                $adminLoginLogRecord->all_user_id = $login_id;
                $adminLoginLogRecord->all_user = \common\models\AdminLoginLog::getAdminEmail($login_id);
                $adminLoginLogRecord->all_date = date('Y-m-d H:i:s');
                try {
                    $adminLoginLogRecord->save();
                } catch (\Exception $exc) {}
            }

            \common\models\AdminLoginSession::deleteAll(['als_admin_id' => (int)$login_id, 'als_device_id' => trim($device_hash)]);

            //tep_session_destroy();
            tep_session_unregister('login_id');
            tep_session_unregister('login_firstname');
            tep_session_unregister('login_groups_id');
            tep_session_unregister('login_affiliate');
            tep_session_unregister('login_vendor');
            tep_session_unregister('device_hash');
            tep_redirect(tep_href_link(FILENAME_LOGIN));
	}
}
