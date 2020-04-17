<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP\Provider;


use backend\models\EP\Messages;

interface DatasourceInterface
{

    public function getProgress();

    public function prepareProcess(Messages $message);

    public function processRow(Messages $message);

    public function postProcess(Messages $message);

}