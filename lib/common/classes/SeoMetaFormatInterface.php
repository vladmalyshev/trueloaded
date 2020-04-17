<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\classes;


interface SeoMetaFormatInterface
{
    /**
     * Get page title tag, otherwise title will be calculated from meta const
     * db column overwrite_head_title_tag
     * @return string
     */
    public function ownMetaTitle();

    /**
     * Get page meta description tag, otherwise will be calculated from meta const.
     * db column overwrite_head_desc_tag
     * @return string
     */
    public function ownMetaDescription();

    /**
     * Get value of ##key## for meta const
     *
     * @param $key
     * @return mixed
     */
    public function getMetaFormatKey($key);

}