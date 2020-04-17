<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap\Theme;


use common\api\models\Soap\SoapModel;

class Theme extends SoapModel
{

    /**
     * @var integer {minOccurs=0, maxOccurs=1}
     * @soap
     */
    var $id;

    /**
     * @var string
     * @soap
     */
    var $theme_name;

    /**
     * @var string
     * @soap
     */
    var $title;

    /**
     * @var string
     * @soap
     */
    var $description;

    /**
     * @var boolean
     * @soap
     */
    var $is_default;

    /**
     * @var integer
     * @soap
     */
    var $sort_order;

    /**
     * @var string
     * @soap
     */
    var $parent_theme;

    /**
     * @var string {minOccurs=0, maxOccurs=1}
     * @soap
     */
    var $theme_archive;
}