<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\models\promotions;

use Yii;

class PromotionsBonusService {

    private $enabled = false;
    private $map = null;
    private $groups = [
        'account' => [
            'name' => 'Account',
            'items' => [
                'create_account'     => __NAMESPACE__ . '\Bonuses\CreateAccount',
                'verify_email'       => __NAMESPACE__ . '\Bonuses\VerifyEmail',
                'login'              => __NAMESPACE__ . '\Bonuses\Login',
                'signing_newsletter' => __NAMESPACE__ . '\Bonuses\SignNewsletter',
                'complete_profile'   => __NAMESPACE__ . '\Bonuses\CompleteProfile',
                'create_wishlist'    => __NAMESPACE__ . '\Bonuses\CreateWishlist',
                'add_to_wishlist'    => __NAMESPACE__ . '\Bonuses\AddWishlist',
                'setting_target'     => __NAMESPACE__ . '\Bonuses\SettingTarget',
            ],
        ],
        'community' => [
            'name' => 'Community',
            'items' => [
                'post_review'        => __NAMESPACE__ . '\Bonuses\PostReview',
                'add_blog'           => __NAMESPACE__ . '\Bonuses\AddBlog',
                'commenting'         => __NAMESPACE__ . '\Bonuses\Comment',
                'rate_product'       => __NAMESPACE__ . '\Bonuses\RateProduct',
                'sharing_media'      => __NAMESPACE__ . '\Bonuses\ShareMedia',
            ],
        ],
        'shopping' => [
            'name' => 'Shopping',
            'items' => [
                'sharing_wishlist'   => __NAMESPACE__ . '\Bonuses\ShareWishlist',
                'using_bumpup'       => __NAMESPACE__ . '\Bonuses\UseBumpup',
                'using_essentials'   => __NAMESPACE__ . '\Bonuses\UseEssentials',
                'comparing_products' => __NAMESPACE__ . '\Bonuses\CompareProducts',
                'using_pricebeat'    => __NAMESPACE__ . '\Bonuses\UsePriceBeat',
                'set_alert'          => __NAMESPACE__ . '\Bonuses\Alert',
                'set_reminder'       => __NAMESPACE__ . '\Bonuses\Reminder',
                'tell_friend'        => __NAMESPACE__ . '\Bonuses\TellFriend',
            ],
        ],
    ];

    public function __construct($enabled = false) {
        $this->enabled = $enabled;
        if (is_null($this->map)) {
            $this->map = [];
            $maps = \yii\helpers\ArrayHelper::getColumn($this->groups, 'items');
            if (is_array($maps)) {
                foreach ($maps as $map)
                    $this->map = array_merge($this->map, $map);
            }
        }
    }

    public function getAllGroups() {

        if (is_array($this->groups)) {
            foreach ($this->groups as $group_code => $group) {
                $_group = $this->getGroup($group_code);
                if ($_group) {
                    $this->groups[$group_code] = $_group;
                    if (is_array($this->groups[$group_code]['items']) && count($this->groups[$group_code]['items'])) {
                        $this->groups[$group_code]['group_enabled'] = true;
                    } else {
                        $this->groups[$group_code]['group_enabled'] = false;
                    }
                }
            }
        }
        return $this->groups;
    }

    public function getGroup($group) {
        if (isset($this->groups[$group])) {
            $this->groups[$group]['name'] = PromotionsBonusGroups::getGroupTitle($group);

            if (is_array($this->groups[$group]['items'])) {
                foreach ($this->groups[$group]['items'] as $item_code => $item) {
                    if (class_exists($item)) {
                        $this->groups[$group]['items'][$item_code] = $item::create($item_code);
                        if ($this->enabled && !$this->groups[$group]['items'][$item_code]->bonus_points_status) {
                            unset($this->groups[$group]['items'][$item_code]);
                        }
                    }
                }
            }
            return $this->groups[$group];
        }
        return false;
    }

    public function getDefaultGroupTitle($group) {
        if (isset($this->groups[$group])) {
            return $this->groups[$group]['name'];
        } else {
            return '';
        }
    }

    public function getAction($action) {
        if (is_array($this->map)) {
            if (isset($this->map[$action]) && class_exists($this->map[$action])) {
                $class = $this->map[$action];
                return $class::getEnabledActionByCode($action)->with('description')->one();
            }
        }
        return false;
    }

}
