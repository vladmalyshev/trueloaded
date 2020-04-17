<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

$path_info = !empty($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : (!empty($_SERVER['ORIG_PATH_INFO']) ? $_SERVER['ORIG_PATH_INFO'] : '');
if ($path_info != "" && strpos($path_info, '/pathinfotest') !== false) {
    echo filter_var($path_info, FILTER_SANITIZE_STRING);
    die();
}

$rootPath = './../';

ini_set("display_errors", 0);

define('VERSION_EXT', 'v.3.3 build 32307');
define('VERSION_PHP_RQ', '7.0.0');
define('VERSION_PHP_REC', '7.2.0');
define('REQ_PHP_MEMORY', '128M');
define('REQ_PHP_MEMORY_REC', '256M');

@set_time_limit(0);
@ignore_user_abort(true);

if (file_exists($rootPath . 'includes/local/configure.php'))
    include_once $rootPath . 'includes/local/configure.php';

include_once($rootPath . 'install/install.class.php');
$install = new install();
$install->root_path = $rootPath;
$install->init();
