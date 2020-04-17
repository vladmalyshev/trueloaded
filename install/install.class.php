<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

class install {

    public $log = [];
    private $current_step = 'start';
    private $previous = 'start';
    private $steps = array();
    private $order = array();
    private $done = array();
    private $retry_step = false;
    public $data = array();

    public function log($type, $message) {
        $this->log[] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    public function init() {
        $this->current_step = $_POST['current_step'];
        if (empty($this->current_step)) {
            $this->current_step = 'start';
        }

        if (defined('TL_INSTALLED') && TL_INSTALLED) {
            if ($this->current_step == 'end' && !isset($_POST['next'])) {

            } elseif ($this->current_step != 'end') {
                echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                    <html>
                        <head>
                            <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
                            <link rel="shortcut icon" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAABwUlEQVQ4jY3TP0jUYRgH8M/7O7jBJCIEj9BSidb2lgipzIKCcFCDHJocgluCggiCkAi9QqIgJGpoyAYhqkmohloayq2hQyoOryQaROLod2+Dd/jrOqHv9Lzf5/k+/3jeoAXpfH5HFMeCcBSvejt6fgpOixYFDyvD5dVsfGgRT2AaOxvUTO+2nu+YarzXcBk3K8NlkGTE13A/I26HTpRwb9fzgc0E6Xx+LIqXmlFRXMIopkR3IyfxMpPonOg8hHQ+3xHFT0EoNJxPMJ4bqdWypXc9GyC4jguZcfoTnGqKo7iMs61iqBwvw0W8zoxzJsFgMygIt3MjtfWtFlAZLtdxK0MdSrA7Q7zZSpzBu4zdl6DeeHzDyn8kWMfnhl1P8ADH5tZG9+S/ftmy/SaqH9/W6mlHPw5iNuRK1QJuYAg/iPvTYuFXO3GuVN0e+RD4jQVMJ1jFAXRhH2EuV6rm/xWvdOFpoA97MYG10HAeIbzQOKzIUtjY9nsxdgphEJONIk2Mp8XuR2GzQnUSszLn3R4RrqTFwlVaPlOuVB3CHRtttkOFWEyLhcdNIrRGJDMr+RDCCeJhwkAkCSxjEQtpsfuvBf8BAruMCRP39I8AAAAASUVORK5CYII=" type="image/x-icon" />
                            <link rel="stylesheet" type="text/css" media="screen" href="//code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css" />
                            <title>Installation - Error</title>
                        </head>
                        <body>
                        <div class="ui-widget">
                            <div class="ui-state-error ui-corner-all" style="padding: 0pt 0.7em;">
                                <p>
                                <strong>Alert:</strong> Already Installed. Remove the \'install\' folder, or remove your configure.php to install again.</p>
                            </div>
                        </div>
                        </body>
                    </html>';
                die();
            }
        }

        $this->data = isset($_POST['step_data']) ? unserialize(base64_decode($_POST['step_data'])) : array();
        if (isset($_POST['next']) && $this->current_step == 'end')
            $this->parse_end();
        if (isset($_POST['install_done'])) {
            $this->done = (strpos($_POST['install_done'], ',') !== false) ? explode(',', $_POST['install_done']) : (($_POST['install_done'] != '') ? array($_POST['install_done']) : array());
        }

        $this->init_language();
        $this->scan_steps();
        if (!(in_array($this->current_step, array_keys($this->steps)) || $this->current_step == 'start') && $this->current_step != 'end') {
            $this->log('install_error', 'invalid current step');
        }
        if (isset($_POST['select'])) {
            $this->current_step = $_POST['select'];
        } elseif (isset($_POST['next']) || isset($_POST['prev']) || $this->current_step == 'start' || isset($_POST['skip'])) {
            if ($this->current_step == 'start' || isset($_POST['skip']) || ($this->current_step != 'end' && $this->parse_step() && isset($_POST['next'])))
                $this->next_step();
            if (isset($_POST['prev']) && !$this->retry_step)
                $this->current_step = $_POST['prev'];
        }
        $this->show();
    }

    private function init_language() {
        if (!isset($_POST['inst_lang'])) {
            $usersprache = explode(",", $_SERVER["HTTP_ACCEPT_LANGUAGE"]);
            $usersprache = explode(";", $usersprache[0]);

            if (strlen($usersprache[0]) == "5") {
                $code = substr($usersprache[0], 3, 2);
            } elseif (strlen($usersprache[0]) == "2") {
                $code = $usersprache[0];
            } else {
                $code = "";
            }
            $code = strtolower($code);
            $language = $this->translate_iso_langcode($code);
            if (!is_file($this->root_path . 'language/' . $language . '/lang_install.php')) {
                $language = "english";
            }
        } else {
            $language = $_POST['inst_lang'];
        }

        if (!include_once($this->root_path . '/install/language/' . $language . '/install.php')) {
            die('Could not include the language files! Check to make sure that "' . $this->root_path . 'language/' . $language . '/install.php" exists!');
        }
        $this->lang = $lang;
    }

    private function scan_steps() {
        $steps = scandir($this->root_path . 'install/install_steps');
        foreach ($steps as $file) {
            if (substr($file, -10) != '.class.php')
                continue;
            $step = substr($file, 0, -10);
            include_once($this->root_path . 'install/install_steps/' . $file);
            if (!class_exists($step)) {
                $this->log('install_error', 'invalid step-file');
            }
            if (empty($this->data[$step])) {
                $this->data[$step] = array();
            }
            $this->steps[] = $step;
            $this->order[call_user_func(array($step, 'before'))] = $step;
            $ajax = call_user_func(array($step, 'ajax'));
            if ($ajax && isset($_POST[$ajax])) {
                $_step = new $step();
                if (method_exists($_step, 'ajax_out'))
                    $_step->ajax_out();
            }
        }
        $this->order = $this->sort_steps();
    }

    private function sort_steps() {
        $arrOut = array();
        $current = 'start';
        for ($i = 0; $i < count($this->order); $i++) {
            $arrOut[$current] = $this->order[$current];
            $current = $this->order[$current];
        }
        return $arrOut;
    }

    private function parse_step() {
        $step = end($this->order);
        while ($step != $this->current_step) {
            if (in_array($step, $this->done, true)) {
                $_step = new $step();
                if (method_exists($_step, 'undo'))
                    $_step->undo();
                unset($this->done[array_search($step, $this->done)]);
            }
            $step = array_search($step, $this->order);
            if (!in_array($step, $this->steps)) {
                $this->pdl->log('install_error', $this->lang['step_order_error']);
                return false;
            }
        }
        $step = $this->current_step;
        $_step = new $step();
        $back = $_step->parse_input();
        $this->data[$this->current_step] = $_step->data;
        if ($back && !in_array($this->current_step, $this->done))
            $this->done[] = $this->current_step;
        if (!$back && in_array($this->current_step, $this->done))
            unset($this->done[array_search($this->current_step, $this->done)]);
        if (!$back)
            $this->retry_step = true;
        return $back;
    }

    private function next_step() {
        $old_current = $this->current_step;
        foreach ($this->steps as $step) {
            if (call_user_func(array($step, 'before')) == $this->current_step) {
                $this->current_step = $step;
                break;
            }
        }
        if ($old_current == $this->current_step)
            $this->current_step = 'end';
    }

    private function next_button() {
        if ($this->current_step == 'end')
            return $this->lang['inst_finish'];
        if ($this->retry_step)
            return $this->lang['retry'];
        $step = $this->current_step;
        $_step = new $step();
        return $this->lang[$_step->next_button];
    }

    private function end() {
        $config = file_get_contents($this->root_path . 'includes/local/configure.php');
        $config .= 'define(\'TL_INSTALLED\', true);' . "\n\n";
        $response = file_put_contents($this->root_path . 'includes/local/configure.php', $config);
        if ($response === false) {
            $this->log('install_error', 'Cant save config file.');
            return false;
        }
        @chmod($this->root_path . 'includes/configure.php', 0444);
        @chmod($this->root_path . 'includes/local/configure.php', 0644);
        @chmod($this->root_path . 'admin/includes/local/configure.php', 0644);

        return $this->lang['install_end_text'];
    }

    private function parse_end() {
        include $this->root_path . 'includes/local/configure.php';
        if (defined('TL_INSTALLED') && TL_INSTALLED) {
            $path = dirname($_SERVER['SCRIPT_FILENAME']);
            @unlink($path);
        }
        header('Location: ' . $this->root_path);
        exit;
    }

    private function get_content() {
        $this->previous = array_search($this->current_step, $this->order);
        if ($this->current_step == 'end')
            return $this->end();
        $step = $this->current_step;
        $_step = new $step();
        if (in_array($this->current_step, $this->done))
            $content = $_step->get_filled_output();
        else
            $content = $_step->get_output();
        $this->data[$this->current_step] = $_step->data;
        return $content;
    }

    private function gen_menu() {
        $menu = '';
        $count_step = '1';
        foreach ($this->order as $step) {
            $class = (in_array($step, $this->done)) ? 'done' : 'notactive';
            if (in_array(array_search($step, $this->order), $this->done))
                $class .= ' done2';
            if ($step == $this->current_step)
                $class = 'now';
            $menu .= "\n\t\t\t\t\t" . '<li class="' . $class . '" id="' . $step . '"><span class="countStep">' . $count_step . '</span><span>' . $this->lang[$step] . '<input type="hidden" name="select" id="back_' . $step . '" disabled="disabled" value="' . $step . '" /></span></li>';
            $count_step++;
        }
        return $menu;
    }

    private function lang_drop() {
        $drop = '<select name="inst_lang" id="language_drop">';
        $options = array();
        $files = scandir($this->root_path . '/install/language');
        foreach ($files as $file) {
            if (file_exists($this->root_path . '/install/language/' . $file . '/install.php'))
                $options[] = $file;
        }
        sort($options);
        foreach ($options as $option) {
            $selected = ($this->langcode == $option) ? ' selected="selected"' : '';
            $drop .= '<option value="' . $option . '"' . $selected . '>' . ucfirst($option) . '</option>';
        }
        return $drop . '</select>';
    }

    private function show() {
        if (class_exists($this->current_step)) {
            $step = $this->current_step;
            $_step = new $step();
        }
        
        $hostname = $_SERVER['HTTP_HOST'];
        $pathname = rtrim(trim(dirname(dirname($_SERVER['SCRIPT_NAME']) . '../'), '/'), '/');
        
        $progress = round(100 * (count($this->done) / count($this->order)), 0);
        $content = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
                <link rel="shortcut icon" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAABwUlEQVQ4jY3TP0jUYRgH8M/7O7jBJCIEj9BSidb2lgipzIKCcFCDHJocgluCggiCkAi9QqIgJGpoyAYhqkmohloayq2hQyoOryQaROLod2+Dd/jrOqHv9Lzf5/k+/3jeoAXpfH5HFMeCcBSvejt6fgpOixYFDyvD5dVsfGgRT2AaOxvUTO+2nu+YarzXcBk3K8NlkGTE13A/I26HTpRwb9fzgc0E6Xx+LIqXmlFRXMIopkR3IyfxMpPonOg8hHQ+3xHFT0EoNJxPMJ4bqdWypXc9GyC4jguZcfoTnGqKo7iMs61iqBwvw0W8zoxzJsFgMygIt3MjtfWtFlAZLtdxK0MdSrA7Q7zZSpzBu4zdl6DeeHzDyn8kWMfnhl1P8ADH5tZG9+S/ftmy/SaqH9/W6mlHPw5iNuRK1QJuYAg/iPvTYuFXO3GuVN0e+RD4jQVMJ1jFAXRhH2EuV6rm/xWvdOFpoA97MYG10HAeIbzQOKzIUtjY9nsxdgphEJONIk2Mp8XuR2GzQnUSszLn3R4RrqTFwlVaPlOuVB3CHRtttkOFWEyLhcdNIrRGJDMr+RDCCeJhwkAkCSxjEQtpsfuvBf8BAruMCRP39I8AAAAASUVORK5CYII=" type="image/x-icon" />
		<link rel="stylesheet" type="text/css" media="screen" href="libraries/jquery/core/core.min.css" />
		<script type="text/javascript" language="javascript" src="libraries/jquery/core/core.min.js"></script>
		<link href="libraries/FontAwesome/font-awesome.min.css" rel="stylesheet">
		<link rel="stylesheet" type="text/css" media="screen" href="style/install.css" />
		<link rel="stylesheet" type="text/css" media="screen" href="style/jquery_tmpl.css" />
		<script type="text/javascript">
			//<![CDATA[
		$(function() {
                        ' . ($this->current_step == 'end' ? 'window.open("https://builtwith.com/' . $hostname . '/'. $pathname . '", "_blank");' : '') . '
                                
			$("#language_drop").change(function(){
				$("#form_install").submit();
			});
			$("#progressbar").progressbar({
				value: ' . $progress . '
			});
			$(".done, .done2, #previous_step").click(function(){
				$("#back_"+$(this).attr("id")).removeAttr("disabled");
				$("#form_install").submit();
			});
			
			$("#form_install").on("submit", function(){
			    $("#content").append("<div class=\'preloader\'></div>")
			})

			' . $_step->head_js . '
		});
			//]]>
		</script>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>' . sprintf($this->lang['page_title'], VERSION_EXT) . '</title>
	</head>

	<body>
		<form action="index.php" method="post" id="form_install">
		<div id="outerWrapper">
			<div id="header">
				<div id="logo"></div>
				<div id="languageselect"><i class="fa fa-globe"></i> ' . $this->lang['language'] . ': ' . $this->lang_drop() . '</div>
				<div id="logotext">Installation ' . VERSION_EXT . '</div>
			</div>

		<div id="installer">
			<div id="steps">
				<ul class="steps">' . $this->gen_menu() . '</ul>
        <div id="progressbar"><span class="install_label">' . $progress . '%</span></div>
			</div>
			<div id="main">
				<div id="content">
					';
        if (count($this->log) > 0) {
            $error = "<br />";
            foreach ($this->log as $log) {
                $type = $log['type'];
                $error .= $this->$type($log['message']);
            }
            $content .= '<h1 class="hicon home">' . $this->lang[$_POST['current_step']] . '</h1>' . $error;
        }

        $content .= '
					<h1 class="hicon home">' . (($this->current_step == 'licence') ? sprintf($this->lang['page_title'], VERSION_EXT) : $this->lang[$this->current_step]) . '</h1>
					' . $this->get_content() . '
					<div class="buttonbar">';
        if ($this->previous != 'start' && $this->current_step != 'end')
            $content .= '
						<button type="button" id="previous_step" class="prevstep">' . $this->lang['back'] . '</button>
						<input type="hidden" name="prev" value="' . $this->previous . '" id="back_previous_step" disabled="disabled" />';
        if ($_step->skippable)
            $content .= '
						<input type="submit" name="' . (($_step->parseskip) ? 'next' : 'skip') . '" value="' . $this->lang['skip'] . '" class="' . (($_step->parseskip) ? 'nextstep' : 'skipstep') . '" />';
        $content .= '
						<button type="submit" name="next" class="blue-btn" />' . $this->next_button() . '</button>
						<input type="hidden" name="current_step" value="' . $this->current_step . '" />
						<input type="hidden" name="install_done" value="' . implode(',', $this->done) . '" />
						<input type="hidden" name="step_data" value="' . base64_encode(serialize($this->data)) . '" />
					</div>
				</div>
			</div>
		</div>
		<div id="footer">
        Copyright &copy; 2005 - ' . date('Y', time()) . ' <a target="_blank" href="https://www.holbi.co.uk">Holbi Group Ltd</a>
		</div>
		</div>
		</form>
	</body>
</html>';
        echo $content;
    }

    public function install_error($log) {
        return '<div class="infobox infobox-large infobox-red clearfix">
		<i class="fa fa-exclamation-triangle fa-4x pull-left"></i><span>' . $this->lang['error'] . '. ' . $log . '</span>
	</div>';
    }

    public function install_warning($log) {
        return '<div class="infobox infobox-large infobox-red clearfix">
			<i class="fa fa-exclamation-triangle fa-4x pull-left"></i><span>' . $this->lang['warning'] . '. ' . $log . '</span>
		</div>';
    }

    public function install_success($log) {
        return '<div class="infobox infobox-large infobox-green clearfix">
		<i class="fa fa-check-circle" aria-hidden="true"></i><span>' . $this->lang['success'] . '. ' . $log . '</span>
	</div>';
    }

    public function translate_iso_langcode($isoCode) {
        $language_codes = array(
            'en' => 'English',
        );
        if (isset($language_codes[$isoCode])) {
            return mb_strtolower($str,  mb_detect_encoding($str));
//            return utf8_strtolower($language_codes[$isoCode]);
        } else {
            return "english";
        }
    }

}

abstract class install_generic {

    public static $before = 'start';
    public static $ajax = false;
    public $head_js = '';
    public $next_button = 'continue';
    public $skippable = false;
    public $parseskip = false;
    public $data = array();

    public function __construct() {
        global $install;
        $this->lang = $install->lang;
        $this->data = $install->data[get_class($this)];
        $this->root_path = $install->root_path;
    }

    public static function before() {
        return self::$before;
    }

    public static function ajax() {
        return self::$ajax;
    }

    public function log($type, $message) {
        global $install;
        $install->log($type, $message);
    }

    public function prepare_input($string) {
        $string = stripslashes($string);
        $string = preg_replace('/ +/', ' ', trim($string));
        $string = preg_replace("/[<>]/", '_', $string);
        return addslashes($string);
    }

    abstract public function get_output();

    abstract public function get_filled_output();

    abstract public function parse_input();
}
