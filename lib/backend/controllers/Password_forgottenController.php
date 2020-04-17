<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;

/**
 * Password forgotten controller to handle user requests.
 */
class Password_forgottenController extends Controller {

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
        global $language;
        if (file_exists(DIR_WS_LANGUAGES . $language . '/' . 'login.php')) {
            include(DIR_WS_LANGUAGES . $language . '/' . 'login.php');
        }
        if (isset($_GET['action']) && ($_GET['action'] == 'process')) {
            $email_address = tep_db_prepare_input($_POST['email_address']);
            $firstname = tep_db_prepare_input($_POST['firstname']);
            $log_times = $_POST['log_times'] + 1;
            if ($log_times >= 4) {
                tep_session_register('password_forgotten');
            }
            // Check if email exists
            $check_admin_query = tep_db_query("select admin_id as check_id, admin_firstname as check_firstname, admin_lastname as check_lastname, admin_email_address as check_email_address from " . TABLE_ADMIN . " where admin_email_address = '" . tep_db_input($email_address) . "'");
            if (!tep_db_num_rows($check_admin_query)) {
                $_GET['login'] = 'fail';
            } else {
                $check_admin = tep_db_fetch_array($check_admin_query);
                if ($check_admin['check_firstname'] != $firstname) {
                    $_GET['login'] = 'fail';
                } else {
                    $_GET['login'] = 'success';

                    $makePassword = $this->randomize();
                    //{{
                    $currentPlatformId = \Yii::$app->get('platform')->config()->getId();
                    $platform_config = \Yii::$app->get('platform')->config($currentPlatformId);

                    $STORE_NAME = $platform_config->const_value('STORE_NAME');
                    $STORE_OWNER_EMAIL_ADDRESS = $platform_config->const_value('STORE_OWNER_EMAIL_ADDRESS');
                    $STORE_OWNER = $platform_config->const_value('STORE_OWNER');
                    
                    $email_params = array();
                    $email_params['STORE_NAME'] = $STORE_NAME;
                    $email_params['NEW_PASSWORD'] = $makePassword;
                    $email_params['CUSTOMER_FIRSTNAME'] = $check_admin['check_firstname'];
                    $email_params['HTTP_HOST'] = \common\helpers\Output::get_clickable_link(tep_href_link(FILENAME_LOGIN));
                    $email_params['CUSTOMER_EMAIL'] = $check_admin['check_email_address'];
                    $email_params['STORE_OWNER_EMAIL_ADDRESS'] = $STORE_OWNER_EMAIL_ADDRESS;
                    list($email_subject, $email_text) = \common\helpers\Mail::get_parsed_email_template('Admin Password Forgotten', $email_params);
                    //}}
                    \common\helpers\Mail::send($check_admin['check_firstname'] . ' ' . $check_admin['admin_lastname'], $check_admin['check_email_address'], $email_subject, $email_text, $STORE_OWNER, $STORE_OWNER_EMAIL_ADDRESS, $email_params);
                    //\common\helpers\Mail::send($check_admin['check_firstname'] . ' ' . $check_admin['admin_lastname'], $check_admin['check_email_address'], ADMIN_EMAIL_SUBJECT, sprintf(ADMIN_EMAIL_TEXT, $check_admin['check_firstname'], \common\helpers\Output::get_clickable_link(HTTP_SERVER . DIR_WS_ADMIN), $check_admin['check_email_address'], $makePassword, STORE_OWNER), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, $email_params);
                    tep_db_query("update " . TABLE_ADMIN . " set admin_password = '" . tep_db_input(\common\helpers\Password::encrypt_password($makePassword)) . "', reset_ip='" . tep_db_input($_SERVER['REMOTE_ADDR']) . "', reset_date = now() where admin_id = '" . $check_admin['check_id'] . "'");
                }
            }

            echo $_GET['login'];
        }
    }

    function randomize() {
        $salt = "ABCDEFGHIJKLMNOPQRSTUVWXWZabchefghjkmnpqrstuvwxyz0123456789";
        srand((double) microtime() * 1000000);
        $i = 0;
        while ($i <= 7) {
            $num = rand() % 33;
            $tmp = substr($salt, $num, 1);
            $pass = $pass . $tmp;
            $i++;
        }
        return $pass;
    }

}
