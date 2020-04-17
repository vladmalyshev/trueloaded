<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP\Reader;


interface ReaderInterface
{

    public function readColumns();
    public function read();

    public function currentPosition();
    public function setDataPosition($position);
    public function getProgress();

}