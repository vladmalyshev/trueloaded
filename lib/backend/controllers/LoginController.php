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
 * login controller to handle user requests.
 */
class LoginController extends Controller {

    /**
     * Disable layout for the controller view
     */
    public $layout = false;
    public $errorMessage = '';
    public $enableCsrfValidation = false;

    /**
     * Index action is the default action in a controller.
     */
    public function actionIndex() {
        global $language, $navigation;
        $languages_id = \Yii::$app->settings->get('languages_id');
        \common\helpers\Translation::init('admin/main');

        $stamp = date('Y-m-d H:i:s', strtotime("-1 hour"));
        tep_db_query("update " . TABLE_ADMIN . " set login_failture = 0, login_failture_ip = '', login_failture_date = NULL where login_failture > 2 and login_failture_date IS NOT NULL and login_failture_date < '" . $stamp . "'");

// {{ From superadmin
        /*if ($_GET['uid'] > 0 && tep_not_null($_GET['tr'])) {
            $check_admin = tep_db_fetch_array(tep_db_query("select admin_id, admin_groups_id, admin_firstname from admin where admin_id = '" . (int) $_GET['uid'] . "' and admin_password = '" . tep_db_input(tep_db_prepare_input($_GET['tr'])) . "'"));
            if ($check_admin['admin_id'] > 0 && $_GET['uid'] == $check_admin['admin_id']) {
                $login_id = $check_admin['admin_id'];
                $login_groups_id = $check_admin['admin_groups_id'];
                $login_firstname = $check_admin['admin_firstname'];

                \common\helpers\Acl::saveManagerDeviceHash($login_id);

                tep_session_register('login_id', $login_id);
                tep_session_register('login_groups_id', $login_groups_id);
                tep_session_register('login_first_name', $login_firstname);

                tep_redirect(tep_href_link(FILENAME_DEFAULT));
            }
        }*/
// }}
        if (isset($_GET['action']) && ($_GET['action'] == 'process')) {
            $errorMessage = TEXT_LOGIN_ERROR;
            $email_address = tep_db_prepare_input($_POST['email_address']);
            $password = tep_db_prepare_input($_POST['password']);

            $adminLoginLogRecord = new \common\models\AdminLoginLog();
            $adminLoginLogRecord->all_event = 1;
            $adminLoginLogRecord->all_device_id = \common\helpers\Acl::saveManagerDeviceHash(0);
            $adminLoginLogRecord->all_ip = \common\helpers\System::get_ip_address();
            $adminLoginLogRecord->all_agent = implode('|', \common\helpers\System::getHttpUserInfoArray());
            $adminLoginLogRecord->all_user_id = 0;
            $adminLoginLogRecord->all_user = $email_address;
            $adminLoginLogRecord->all_date = date('Y-m-d H:i:s');

            $adminSecurityKey = '';
            if (\Yii::$app->request->post('action', '') == 'authorize') {
                $al_admin_login_id = trim(tep_session_is_registered('al_admin_login_id') ? tep_session_var('al_admin_login_id') : '');
                tep_session_unregister('al_admin_login_id');
                if ($al_admin_login_id == '') {
                    tep_redirect(tep_href_link(FILENAME_DEFAULT));
                }
                $email_address = $al_admin_login_id;
                $adminSecurityKey = preg_replace('/[^0-9a-z]*/si', '', \Yii::$app->request->post('security_key', ''));

                $adminLoginLogRecord->all_event = 3;
                $adminLoginLogRecord->all_user = $email_address;

            }

            // Check if email exists
            $check_admin_query = tep_db_query("select admin_id as login_id, admin_groups_id as login_groups_id, access_levels_id, admin_firstname as login_firstname, admin_lastname as login_lastname, admin_email_address as login_email_address, admin_password as login_password, admin_modified as login_modified, admin_logdate as login_logdate, admin_lognum as login_lognum, languages, login_failture, admin_phone_number as login_phone_number, admin_two_step_auth as login_two_step_auth from " . TABLE_ADMIN . " where (admin_email_address = '" . tep_db_input($email_address) . "' or admin_username='" . tep_db_input($email_address) . "')");
            if (!tep_db_num_rows($check_admin_query)) {
                $_GET['login'] = 'fail';
            } else {
                $check_admin = tep_db_fetch_array($check_admin_query);

                $adminLoginLogRecord->all_user_id = $check_admin['login_id'];
                $adminLoginLogRecord->all_user = $check_admin['login_email_address'];

                $isAdminNoPassword = false;
                $isGuest = ((int)\Yii::$app->request->post('ad_is_guest', 0) > 0 ? true : false);
                $al_computer_id = md5($check_admin['login_id'] . $check_admin['login_email_address'] . $check_admin['login_password'] . $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
                if ($adminSecurityKey != '') {
                    $adminLoginRecord = \common\models\AdminLogin::getByIdComputer($check_admin['login_id'], $al_computer_id);
                    if (is_object($adminLoginRecord)) {
                        if ($adminLoginRecord->al_security_key == md5($adminSecurityKey)
                            AND $adminLoginRecord->al_expire == '0000-00-00 00:00:00'
                        ) {
                            $alExpire = date('Y-m-d H:i:s', strtotime('+3 second'));
                            $securityKeyExpire = (int)\Yii::$app->request->post('security_key_expire', 0);
                            $securityKeyExpireArray = \common\models\AdminLogin::getSecurityKeyExpireArray();
                            if (($isGuest != true) AND isset($securityKeyExpireArray[$securityKeyExpire]) AND ((int)$securityKeyExpireArray[$securityKeyExpire]['ale_expire_minutes'] > 0)) {
                                $alExpire = date('Y-m-d H:i:s', strtotime('+' . (int)$securityKeyExpireArray[$securityKeyExpire]['ale_expire_minutes'] . ' minutes'));
                            }
                            $adminLoginRecord->al_expire = $alExpire;
                            $adminLoginRecord->save();
                            $isAdminNoPassword = true;
                        } else {
                            $adminLoginRecord->delete();
                        }
                    }
                }

                // Check that password is good
                if ($check_admin['login_failture'] >= 3) {
                    $_GET['login'] = 'fail';
                    $errorMessage = TEXT_LOGIN_BLOCK;
                } elseif (($isAdminNoPassword !== true) AND !\common\helpers\Password::validate_password($password, $check_admin['login_password'])) {

                    if ($adminLoginLogRecord->all_event == 1) {
                        $adminLoginLogRecord->all_event = 2;
                    }

                    $_GET['login'] = 'fail';
                    tep_db_query("update " . TABLE_ADMIN . " set login_failture = login_failture + 1, login_failture_ip='" . tep_db_input($_SERVER['REMOTE_ADDR']) . "', login_failture_date = now() where admin_id = '" . (int) $check_admin['login_id'] . "' and admin_email_address!='vlad@holbi.co.uk'");
                    $login_failture = 3 - ($check_admin['login_failture']+1);
                    if ($login_failture < 0) {
                        $login_failture = 0;
                    }
                    $errorMessage = sprintf(TEXT_LOGIN_WARNING, $login_failture);
                } else {
                    if ((ADMIN_TWO_STEP_AUTH_ENABLED == 'true') OR ($check_admin['login_two_step_auth'] != '')) {
                        $isAdminLogged = false;
                        $adminLoginRecord = \common\models\AdminLogin::getByIdComputer($check_admin['login_id'], $al_computer_id);
                        if (is_object($adminLoginRecord)) {
                            if (strtotime($adminLoginRecord->al_expire) > time()) {
                                $isAdminLogged = true;
                            } else {
                                $adminLoginRecord->delete();
                            }
                        }
                        if ($isAdminLogged != true) {
                            $al_security_key = \common\models\AdminLogin::securityKeyGenerate();
                            $adminLoginRecord = new \common\models\AdminLogin();
                            $adminLoginRecord->al_admin_id = (int)$check_admin['login_id'];
                            $adminLoginRecord->al_computer_id = trim($al_computer_id);
                            $adminLoginRecord->al_security_key = md5(preg_replace('/[^0-9a-z]*/si', '', $al_security_key));
                            $adminLoginRecord->al_expire = '0000-00-00 00:00:00';
                            $adminLoginRecord->al_create = date('Y-m-d H:i:s');
                            $adminLoginRecord->save();
                            tep_session_register('al_admin_login_id', $check_admin['login_email_address']);
                            $securityKeyExpireArray = array();
                            foreach (\common\models\AdminLogin::getSecurityKeyExpireArray() as $loginExpireArray) {
                                $securityKeyExpireArray[] = ['id' => $loginExpireArray['ale_id'], 'text' => $loginExpireArray['ale_title']];
                            }
                            $parameterArray = [
                                'securityKeyExpireArray' => $securityKeyExpireArray,
                                'isMobile' => (\Yii::$app->mobileDetect->isMobile() OR \Yii::$app->mobileDetect->isTablet() OR \Yii::$app->mobileDetect->isIphone())
                            ];
                            $two_step_auth_service = (($check_admin['login_two_step_auth'] != '') ? $check_admin['login_two_step_auth'] : ADMIN_TWO_STEP_AUTH_SERVICE);
                            if (($two_step_auth_service == 'email') OR (trim($check_admin['login_phone_number']) == '')) {
                                $parameterArray['type'] = 'email';
                                \common\models\AdminLogin::securityKeyEmail($check_admin, $al_security_key);
                            } else {
                                $parameterArray['type'] = 'sms';
                                if (\common\models\AdminLogin::securityKeySms($check_admin, $al_security_key) != true) {
                                    $parameterArray['type'] = 'email';
                                    \common\models\AdminLogin::securityKeyEmail($check_admin, $al_security_key);
                                }
                            }
                            return $this->render('authorize', $parameterArray);
                        }
                    }

                    if (tep_session_is_registered('password_forgotten')) {
                        tep_session_unregister('password_forgotten');
                    }

                    $login_id = $check_admin['login_id'];
                    $login_groups_id = $check_admin['login_groups_id'];
                    $login_firstname = $check_admin['login_firstname'];
                    $login_email_address = $check_admin['login_email_address'];
                    $login_logdate = $check_admin['login_logdate'];
                    $login_lognum = $check_admin['login_lognum'];
                    $login_modified = $check_admin['login_modified'];
                    $access_levels_id = $check_admin['access_levels_id'];
                    $language = $check_admin['languages'];

                    $adminLoginLogRecord->all_event = 10;

                    tep_session_register('login_id', $login_id);
                    tep_session_register('login_groups_id', $login_groups_id);
                    tep_session_register('login_first_name', $login_firstname);
                    tep_session_register('access_levels_id', $access_levels_id);
                    tep_session_register('language', $language);

                    $lng = new \common\classes\language();
                    $lng->set_language($language);
                    $languages_id = $lng->language['id'];
                    tep_session_register('languages_id', $languages_id);
                    $lng->set_locale();
                    $lng->load_vars();

                    //$date_now = date('Ymd');
                    $device_hash = \common\helpers\Acl::saveManagerDeviceHash($login_id);
                    tep_session_register('device_hash', $device_hash);

                    $adminLoginLogRecord->all_device_id = $device_hash;
                    try {
                        $adminLoginLogRecord->save();
                    } catch (\Exception $exc) {}

                    if ((\common\models\AdminLogin::checkAdminDevice($login_id, $device_hash, $isGuest) != true)
                        OR (\common\models\AdminLoginSession::updateAdminSession($login_id, $device_hash) != true)
                    ) {
                        tep_redirect(tep_href_link(FILENAME_LOGOFF));
                    }
                    tep_db_query("update " . TABLE_ADMIN . " set login_failture = 0, login_failture_ip = '', admin_logdate = now(), admin_lognum = admin_lognum+1 where admin_id = '" . (int)$login_id . "'");

                    if (($login_lognum == 0) || !($login_logdate) || ($login_email_address == 'admin@localhost') || ($login_modified == '0000-00-00 00:00:00')) {
                        tep_redirect(tep_href_link(FILENAME_ADMIN_ACCOUNT));
                    } else {
                        if (sizeof($navigation->snapshot) > 0) {
                            $origin_href = tep_href_link($navigation->snapshot['page'], \common\helpers\Output::array_to_string($navigation->snapshot['get'], array(tep_session_name())), $navigation->snapshot['mode']);
                            $navigation->clear_snapshot();
                            tep_redirect($origin_href);
                        } else {
                            tep_redirect(tep_href_link(FILENAME_DEFAULT));
                        }
                    }
                }
            }
            if ($_GET['login'] == 'fail') {
                $this->errorMessage = $errorMessage;
            }

            try {
                $adminLoginLogRecord->save();
            } catch (\Exception $exc) {}

        }
        if (!\Yii::$app->request->isAjax AND tep_session_is_registered('admin_multi_session_error')) {
            $this->errorMessage = ADMIN_MULTI_SESSION_ERROR;
            tep_session_unregister('admin_multi_session_error');
        }
        return $this->render('index');
    }

}
