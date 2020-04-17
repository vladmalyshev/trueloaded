<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\modules\email;


interface MailerInterface {
    
    public function ready();
    
    public function add_html($email_text, $text);
    
    public function add_text($text);
    
    public function add_attachment($file, $name);
    
    public function build_message();
    
    public function addBcc($bcc);
    
    public function send($to_name, $to_email_address, $from_email_name, $from_email_address, $email_subject, $headers);
}