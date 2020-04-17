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

use common\classes\platform;

final class analytics extends AbstractGoogle {

    use adTrait;

    public $config;
    public $code = 'analytics';

    public function getParams() {

        $this->config = [
            $this->code => [
                'name' => 'Google Analytics',
                'fields' => [
                    [
                        'name' => 'code',
                        'value' => 'UA-',
                        'type' => 'text'
                    ],
                ],
                'pages' => [
                    'all',
                ],
                'type' => [
                    'selected' => 'ga',
                    'ga' => 'Universal Analytics (analytics.js)',
                    '_gaq' => 'Google Analytics (ga.js)',
                ],
                'priority' => 1,
                'example' => true
            ],
        ];
        return $this->config;
    }

    public function renderWidget($example = false) {
        global $request_type;
        if (\Yii::$app->response->getIsNotFound())
            return;
        $elements = $this->config[$this->code];
        if ($request_type == 'SSL') {
            $_server = HTTPS_SERVER;
        } else {
            $_server = HTTP_SERVER;
        }
        if (\frontend\design\Info::isTotallyAdmin()) {
            $path = $_server . DIR_WS_CATALOG . 'themes/basic/js/analytics.js';
        } else {
            $_path = $_server . DIR_WS_HTTP_CATALOG;
            if (is_link($_path)) {
                $_path = readlink($_path);
            }
            $path = $_path . 'themes/basic/js/analytics.js';
        }
        $ua_code = $elements['fields'][0]['value'];
        if ($example) {
            $context = '';
            foreach ($elements['type'] as $_key => $_type) {
                if ($_key == 'selected')
                    continue;
                $context .= '<div class="switchers ' . $_key . '" style="' . ($elements['type']['selected'] != $_key ? "display:none" : "") . '">' . preg_replace("/<script>/", "", preg_replace("/<\/script>/", "", $this->getSelectedCode($_key, $ua_code, $path))) . ' </div> ';
            }
            $context .= '
      <script>
        $(document).ready(function(){
          $("input[name=type]:radio").change(function(){           
            $(".switchers").hide();
            $(".switchers."+$("input[name=type]:radio:checked").val()).show();
          });
        })
      </script>';
            return $context;
        }else {
            return $this->getSelectedCode($elements['type']['selected'], $ua_code, $path);
        }
    }

    private function getSelectedCode($code, $ua_code, $path) {
        if ($code == 'ga') {
            return <<<EOD
<script>

  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','$path','ga');
  
  ga('create', '$ua_code', 'auto');
  ga('send', 'pageview');
  if (!localStorage.hasOwnProperty('ga_cookie')){
      localStorage.ga_cookie = 'false';
  }
  tl(function(){{$this->collectCookie()}})
</script>
EOD;
        } else {
            return <<<EOD
<script>

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', '$ua_code']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
EOD;
        }
    }

    public function renderExample() {
        return "<pre>" . $this->renderWidget(true) . "</pre>";
    }

}
