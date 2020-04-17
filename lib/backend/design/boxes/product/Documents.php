<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\design\boxes\product;

use Yii;
use yii\base\Widget;

class Documents extends Widget
{

  public $id;
  public $params;
  public $settings;
  public $visibility;

  public function init()
  {
    parent::init();
  }

  public function run()
  {
    global $languages_id;

    $types = array();
    $types_query = tep_db_query("select * from " . TABLE_DOCUMENT_TYPES . " where language_id='" . $languages_id . "' order by document_types_name");
    while ($type = tep_db_fetch_array($types_query)){
      $types[] = $type;
    }

    return $this->render('../../views/documents-products.tpl', [
      'id' => $this->id, 'params' => $this->params, 'settings' => $this->settings,
      'visibility' => $this->visibility,
      'types' => $types,
    ]);
  }
}