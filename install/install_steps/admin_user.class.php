<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

class admin_user extends install_generic {

    public static $before = 'inst_settings';
    public $next_button = 'create_user';

    private $username = '';
    private $useremail = '';

    public static function before() {
        return self::$before;
    }

    public function get_output() {
        $content = '<table width="100%" border="0" cellspacing="1" cellpadding="2" class="no-borders table-db-access">
                    <tr>
                        <td align="right" width="25%">' . $this->lang['username'] . ':</td>
                        <td align="left" width="25%"><input type="text" name="username" value="' . $this->username . '" class="input" required /></td>
                        <td align="right" width="25%">' . $this->lang['user_email'] . ':</td>
                        <td align="left" width="25%"><input type="text" name="user_email" value="' . $this->useremail . '" class="input" size="30" required /></td>
                    </tr>
                    <tr>
                        <td align="right">' . $this->lang['user_password'] . ':</td>
                        <td align="left"><input type="password" name="user_password1" value="" class="input" required /></td>
                        <td align="right">' . $this->lang['user_pw_confirm'] . ':</td>
                        <td align="left"><input type="password" name="user_password2" value="" class="input" required /></td>
                    </tr>
            </table>';
        return $content;
    }

    public function get_filled_output() {
        return $this->get_output();
    }

    public function parse_input() {
        $this->username = $_POST['username'];
        $this->useremail = $_POST['user_email'];
        if ($_POST['user_password1'] == '' || empty($this->username) || empty($this->useremail)) {
            $this->log('install_error', $this->lang['user_required']);
            return false;
        }
        if ($_POST['user_password1'] != $_POST['user_password2']) {
            $this->log('install_error', $this->lang['no_pw_match']);
            return false;
        }

        include_once $this->root_path . 'includes/local/configure.php';
        $link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD);
        if (!$link) {
            $this->log('install_error', 'Can\'t connect to database server.');
            return false;
        }
        $db_selected = mysqli_select_db($link, DB_DATABASE);
        if (!$link)
        {
            $this->log('install_error', 'Wrong database name.');
            return false;
        }

        $password = '';
        for ($i = 0; $i < 10; $i++) {
            $password .= mt_rand();
        }
        $salt = substr(md5($password), 0, 2);
        $password = md5($salt . $_POST['user_password1']) . ':' . $salt;

        $query = "UPDATE admin SET " .
                "admin_username='" . $this->prepare_input($this->username) . "'" .
                ", admin_email_address='" . $this->prepare_input($this->useremail) . "'" .
                ", admin_password='" . $this->prepare_input($password) . "'" .
                ", admin_created=now()" .
                ", admin_modified=now()" .
                " WHERE admin_id=28;";
        $result = mysqli_query($link, $query);
        if (!$result) {
            $this->log('install_error', 'Can\'t update settings.');
            return false;
        }

        mysqli_close($link);
        return true;
    }

}
