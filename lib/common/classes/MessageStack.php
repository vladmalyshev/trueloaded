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

class MessageStack {

    public $messages = [];

    public function initFlash() {
        foreach (\Yii::$app->session->getAllFlashes(true) as $class => $messages) {
            if (is_array($messages)) {
                foreach ($messages as $message) {
                    $this->add($message['text'], $class, $message['type']);
                }
            } else {
                $this->add($messages, $class);
            }
        }
        return $this;
    }

    public function add($message, $class = 'header', $type = 'error') {
        $this->messages[$class][] = ['text' => $message, 'type' => $type];
    }

    public function add_session($message, $class = 'header', $type = 'error') {
        \Yii::$app->session->addFlash($class, ['text' => $message, 'type' => $type]);
    }

    public function reset() {
        $this->messages = [];
    }

    public function convert_to_session($only_class = '', $replace_to_class = '') {
        foreach ($this->messages as $_class => $_message) {
            if (empty($only_class) || $only_class == $_class) {
                $this->add_session($_message['text'], (empty($replace_to_class) ? $_class : $replace_to_class), $_message['type']);
                $this->remove_current($_class, $_message['text']);
                unset($this->messages[$_class]);
            }
        }
        $this->messages = array_values($this->messages);
    }
    
    public function remove_current($class, $message) {
        global $cart;
        if (is_object($cart) && $cart->basketID && \Yii::$app->user->isGuest) {
            tep_db_query("delete from " . TABLE_CUSTOMERS_ERRORS . " where customers_id = '" . (int) \Yii::$app->user->getId() . "' and basket_id = '" . (int) $cart->basketID . "' and error_entity='" . tep_db_input($class) . "' and error_message = '" . tep_db_input($message) . "'");
        }
    }

    public function outputHead($simple = false, $class = 'header') {
        $html = '';
        if (!is_array($this->messages[$class])) {
            return $html;
        }
        if ($simple) {
            foreach ($this->messages[$class] as $error) {
                foreach ($error as $key => $item) {
                    if ($key == 'text')
                        $html .= $item . "<br>";
                }
            }
            return $html;
        }
        foreach ($this->messages[$class] as $error) {
            $html .= '<div class="popup-box-wrap pop-mess" style="top: 200px;">
            <div class="around-pop-up"></div>
            <div class="popup-box">
                 <div class="pop-up-close pop-up-close-alert"></div>
                    <div class="pop-up-content">
                        <div class="popup-heading">' . TEXT_NOTIFIC . '</div>
                        <div class="popup-content pop-mess-cont pop-mess-cont-' . $error['type'] . '">
                        ' . $error['text'] . '
                        </div>
                    </div>
                    <div class="noti-btn">
                            <div></div>
                            <div><span class="btn btn-primary">' . TEXT_BTN_OK . '</span></div>
                        </div>
            </div>
            <script>
            setTimeout(function(){
                $("body").scrollTop(0);
                $(".popup-box-wrap.pop-mess").insertAfter("#container");
                $(".pop-mess .pop-up-close-alert, .noti-btn .btn").click(function(){
                    $(this).parents(".pop-mess").remove();
                });}
                , 100);
            </script>
         </div>';
        }
        return $html;
    }

    public function output($class = 'header') {
        $html = '';
        if (\Yii::$app->session->has($class)){
            $this->initFlash();
        }
        if (!is_array($this->messages[$class])) {
            return $html;
        }
        if (count($this->messages[$class]) > 0) {
            $html .= '<div class="messageBox">';
            foreach ($this->messages[$class] as $message) {
                $html .= '<div class="info">' . $message['text'] . '</div>';
            }
            $html .= '</div>';
        }
        return $html;
    }

    public function getErrors() {
        return $this->messages;
    }
    
    public function asArray($class = 'header'){
        if (\Yii::$app->session->has($class)){
            $this->initFlash();
        }
        if (isset($this->messages[$class])){
            return ['messages' => $this->messages[$class]];
        }
        return [];
    }

    public function size($class = 'header') {
        $count = 0;
        if (is_array($this->messages[$class])) {
            $count = count($this->messages[$class]);
        }
        if (\Yii::$app->session->hasFlash($class)){
            $count++;
        }
        return $count;
    }

    public function save_to_base($class, $message, $type, $title = '') {
        global $cart;
        if (is_object($cart) && $cart->basketID && !\Yii::$app->user->isGuest && $class != 'header' && ($type == 'error' || $type == 'warning')) {
            $sql_array = array(
                'customers_id' => (int) \Yii::$app->user->getId(),
                'basket_id' => (int) $cart->basketID,
                'error_entity' => tep_db_prepare_input($class),
                'error_title' => tep_db_prepare_input($title),
                'error_message' => tep_db_prepare_input($message),
                'error_date' => 'now()'
            );
            tep_db_perform(TABLE_CUSTOMERS_ERRORS, $sql_array);
        }
    }

}
