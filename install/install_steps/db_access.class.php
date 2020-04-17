<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

class db_access extends install_generic {

    public static $shortcuts = array('pfh' => array('file_handler', array('installer')));
    public static $before = 'php_check';
    public $next_button = 'inst_db';

    private $dbhost = 'localhost';
    private $dbname = '';
    private $dbuser = '';

    public static function before() {
        return self::$before;
    }

    public function get_output() {
        $content = '
		<table width="100%" border="0" cellspacing="1" cellpadding="2" class="no-borders no-padding">
                    <tr>
                        <td align="right">' . $this->lang['dbhost'] . ':</td>
                        <td><input type="text" name="dbhost" size="25" value="' . $this->dbhost . '" class="input" required /></td>
                        <td align="right">' . $this->lang['dbuser'] . ':</td>
                        <td><input type="text" name="dbuser" size="25" value="' . $this->dbuser . '" class="input" required /></td>
                    </tr>
                    <tr>
                        <td align="right">' . $this->lang['dbname'] . ':</td>
                        <td><input type="text" name="dbname" size="25" value="' . $this->dbname . '" class="input" required /></td>
                        <td align="right">' . $this->lang['dbpass'] . ':</td>
                        <td><input type="password" name="dbpass" size="25" value="" class="input" /></td>
                    </tr>
		</table>';
        return $content;
    }

    public function get_filled_output() {
        if (defined('DB_SERVER')) {
            $this->dbhost = DB_SERVER;
        }
        if (defined('DB_DATABASE')) {
            $this->dbname = DB_DATABASE;
        }
        if (defined('DB_SERVER_USERNAME')) {
            $this->dbuser = DB_SERVER_USERNAME;
        }
        return $this->get_output();
    }

    public function parse_input() {
        $this->dbhost = $_POST['dbhost'];
        $this->dbname = $_POST['dbname'];
        $this->dbuser = $_POST['dbuser'];
        $this->dbpass = $_POST['dbpass'];

        $link = mysqli_connect($this->dbhost, $this->dbuser, $this->dbpass);
        if (!$link) {
            $this->log('install_error', 'Can\'t connect to database server.');
            return false;
        }
        $db_selected = mysqli_select_db($link, $this->dbname);
        if (!$link)
        {
            $this->log('install_error', 'Wrong database name.');
            return false;
        }
        
        $content  = '<?php' . "\n";
        $content .= "define('DB_SERVER', '" . $this->dbhost . "');" . "\n";
        $content .= "define('DB_SERVER_USERNAME', '" . $this->dbuser . "');" . "\n";
        $content .= "define('DB_SERVER_PASSWORD', '" . $this->dbpass . "');" . "\n";
        $content .= "define('DB_DATABASE', '" . $this->dbname . "');" . "\n";
        $content .= "define('USE_PCONNECT', 'false');" . "\n";
        $content .= "define('STORE_SESSIONS', 'mysql');" . "\n";
        $response = file_put_contents($this->root_path . 'includes/local/configure.php', $content);
        if ($response === false) {
            $this->log('install_error', 'Can\'t save config file.');
            return false;
        }

        $hostname = $_SERVER['HTTP_HOST'];
        $pathname = rtrim(trim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/'), '/');
        if (!empty($pathname)) {
            $pathname = '/' . $pathname;
        }
        
        $content  = '<?php' . "\n";
        $content  .= "define('HTTP_SERVER', 'http://" . $hostname . "');" . "\n";
        $content  .= "define('HTTPS_SERVER', 'https://" . $hostname . "');" . "\n";
        $content  .= "define('HTTP_CATALOG_SERVER', 'http://" . $hostname . "');" . "\n";
        $content  .= "define('HTTPS_CATALOG_SERVER', 'https://" . $hostname . "');" . "\n";
        $content  .= "define('ENABLE_SSL', true);" . "\n";
        $content  .= "define('ENABLE_SSL_CATALOG', true);" . "\n";
        $content  .= "" . "\n";
        $content  .= 'define(\'DIR_FS_DOCUMENT_ROOT\', $_SERVER[\'DOCUMENT_ROOT\']);' . "\n";
        $content  .= "define('DIR_WS_ADMIN', '" . $pathname . "/admin/');" . "\n";
        $content  .= "define('DIR_FS_ADMIN', DIR_FS_DOCUMENT_ROOT . DIR_WS_ADMIN);" . "\n";
        $content  .= "define('DIR_WS_CATALOG', '" . $pathname . "/');" . "\n";
        $content  .= "define('DIR_FS_CATALOG', DIR_FS_DOCUMENT_ROOT . DIR_WS_CATALOG);" . "\n";
        $content  .= "define('DIR_WS_IMAGES', 'images/');" . "\n";
        $content  .= "define('DIR_WS_ICONS', DIR_WS_IMAGES . 'icons/');" . "\n";
        $content  .= "define('DIR_WS_CATALOG_IMAGES', DIR_WS_CATALOG . 'images/');" . "\n";
        $content  .= "define('DIR_WS_INCLUDES', 'includes/');" . "\n";
        $content  .= "define('DIR_WS_BOXES', DIR_WS_INCLUDES . 'boxes/');" . "\n";
        $content  .= "define('DIR_WS_FUNCTIONS', DIR_WS_INCLUDES . 'functions/');" . "\n";
        $content  .= "define('DIR_WS_CLASSES', DIR_WS_INCLUDES . 'classes/');" . "\n";
        $content  .= "define('DIR_WS_MODULES', DIR_WS_INCLUDES . 'modules/');" . "\n";
        $content  .= "define('DIR_WS_LANGUAGES', DIR_WS_INCLUDES . 'languages/');" . "\n";
        $content  .= "define('DIR_WS_CATALOG_LANGUAGES', DIR_WS_CATALOG . 'includes/languages/');" . "\n";
        $content  .= "define('DIR_FS_CATALOG_LANGUAGES', DIR_FS_CATALOG . 'includes/languages/');" . "\n";
        $content  .= "define('DIR_FS_CATALOG_IMAGES', DIR_FS_CATALOG . 'images/');" . "\n";
        $content  .= "define('DIR_FS_CATALOG_MODULES', DIR_FS_CATALOG . 'includes/modules/');" . "\n";
        $content  .= "define('DIR_FS_BACKUP', DIR_FS_ADMIN . 'backups/');" . "\n";
        $content  .= "define('DIR_FS_CATALOG_XML', DIR_FS_CATALOG . 'xml/');" . "\n";
        $content  .= "define('DIR_FS_DOWNLOAD', DIR_FS_CATALOG . 'download/');" . "\n";
        $content  .= "define('DIR_WS_DOWNLOAD', DIR_WS_CATALOG . 'download/');" . "\n";
        $content  .= "define('DIR_FS_CATALOG_FONTS', DIR_FS_ADMIN . 'includes/fonts/');" . "\n";
        //$content  .= "define('DIR_FS_CATALOG_MAINPAGE_MODULES', DIR_FS_CATALOG_MODULES . 'mainpage_modules/');" . "\n";
        $content  .= "define('DIR_WS_TEMPLATES', DIR_WS_CATALOG . 'templates/');" . "\n";
        $content  .= "define('DIR_FS_TEMPLATES', DIR_FS_CATALOG . 'templates/');" . "\n";
        $content  .= "" . "\n";
        $content  .= "define('DB_SERVER', '" . $this->dbhost . "');" . "\n";
        $content  .= "define('DB_SERVER_USERNAME', '" . $this->dbuser . "');" . "\n";
        $content  .= "define('DB_SERVER_PASSWORD', '" . $this->dbpass . "');" . "\n";
        $content  .= "define('DB_DATABASE', '" . $this->dbname . "');" . "\n";
        $content  .= "define('USE_PCONNECT', 'false');" . "\n";
        $content  .= "define('STORE_SESSIONS', '');" . "\n";
        $content  .= "" . "\n";
        $response = file_put_contents($this->root_path . 'admin/includes/local/configure.php', $content);
        if ($response === false) {
            $this->log('install_error', 'Can\'t save admin config file.');
            return false;
        }

        $path = dirname(dirname($_SERVER['SCRIPT_FILENAME']));
        $restore_from = $path . '/sql/trueloaded.sql';
        
        $sqls = $this->parse_sql_file($restore_from);
        foreach($sqls as $sql) {
            $result = mysqli_query($link, $sql);
            if (!$result) {
                $this->log('install_error', 'Can\'t update database.');
                return false;
            }
        }
        
        mysqli_close($link);

        $this->log('install_success', $this->lang['dbcheck_success']);
        
        return true;
    }
    
    private function parse_sql_file($filename) {
        $file = file_get_contents($filename);
        $sqls = explode(";\n", str_replace("\n\n", "\n", str_replace("\r", "\n", $file)));
        $sqls = preg_replace('/^#.*$/m', '', $sqls);
        $sqls = preg_replace('/\s{2,}/', ' ', $sqls);
        //$sqls = preg_replace('/\v/', '', $sqls);
        return $sqls;
    }

}
