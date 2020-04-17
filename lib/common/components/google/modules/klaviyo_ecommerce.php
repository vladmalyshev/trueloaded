<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\components\google\modules;

use common\classes\Order;

final class klaviyo_ecommerce extends AbstractGoogle  {

    public $config;
    public $code = 'klaviyo_ecommerce';

    public function getParams() {

        $this->config = [
            $this->code => [
                'name' => 'Klaviyo ECommerce Tracking',
                'fields' => [
                ],
                'pages' => [
                    'checkout',
                ],
                'pages_only' => true,
                'priority' => 6,
                'example' => true,
            ],
        ];
        return $this->config;
    }

    public function renderWidget() {
        
    }

    public function renderExample() {
        return "<pre>" . <<<EOD
Server-To-Server transferring...Klaviyo analytics required
EOD
                        . "</pre>";
    }
}
