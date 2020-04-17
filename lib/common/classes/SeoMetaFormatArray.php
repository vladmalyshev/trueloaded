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


class SeoMetaFormatArray implements SeoMetaFormatInterface
{

    protected $keys = [];

    public function __construct()
    {
    }

    public function ownMetaTitle()
    {
        return isset($this->keys['META_TITLE'])?$this->keys['META_TITLE']:'';
    }

    public function ownMetaDescription()
    {
        return $this->getMetaFormatKey('META_DESCRIPTION');
    }

    public function getMetaFormatKey($key)
    {
        return isset($this->keys[$key])?$this->keys[$key]:'';
    }

}