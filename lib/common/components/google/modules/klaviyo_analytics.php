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

final class klaviyo_analytics extends AbstractGoogle {

    public $config;
    public $code = 'klaviyo_analytics';

    public function getParams() {

        $this->config = [
            $this->code => [
                'name' => 'Klaviyo Analytics',
                'fields' => [
                    [
                        'name' => 'api_key',
                        'value' => '',
                        'type' => 'text'
                    ],
                ],
                'pages' => [
                    'all',
                ],
                'priority' => 5,
                'example' => false
            ],
        ];
        return $this->config;
    }

    public function renderWidget($example = false) {
        global $request_type;
        if (\Yii::$app->response->getIsNotFound()) return '';
        $elements = $this->config[$this->code];
        $api_key = $elements['fields'][0]['value'];
        return $this->getSelectedCode($api_key, $example);
    }

    private function getSelectedCode($api_key, $example ) {
            return <<<EOD
<script async type="text/javascript" src="//static.klaviyo.com/onsite/js/klaviyo.js?company_id={$api_key}"></script>
<script>
  var _learnq = _learnq || [];
</script>
EOD;
    }

}
