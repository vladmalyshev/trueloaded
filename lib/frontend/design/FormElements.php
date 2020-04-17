<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design;

use Yii;
use common\classes\Images;
use backend\design\Style;

class FormElements
{

    public static function radioButton()
    {
        return [
            'item' => function($index, $label, $name, $checked, $value) {
                return '
    <label class="radio-button">
        <input type="radio" name="' . $name . '" value="' . $value . '"' . ($checked ? 'checked' : '') . '>
        <span>' . $label . '</span>
    </label>';
            }
        ];
    }
}

