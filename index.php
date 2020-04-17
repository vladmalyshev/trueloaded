<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

if (file_exists('includes/local/configure.php')) include('includes/local/configure.php');
if (!defined('TL_INSTALLED') || TL_INSTALLED != true) {
    header('Location: ' . 'install/index.php');
    exit;
}

require('includes/application_top.php');

require(__DIR__ . '/lib/frontend/web/index.php');

require_once('includes/application_bottom.php');
