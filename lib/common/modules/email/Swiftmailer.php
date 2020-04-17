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

use Yii;

class Swiftmailer implements MailerInterface {

    private $mailer;
    private $message;

    public function __construct() {
        
        if (!$this->ready()) {
            return false;
        }
        $transport = (new \Swift_SmtpTransport(SMTP_HOST, SMTP_PORT))
            ->setUsername(SMTP_USERNAME)
            ->setPassword(SMTP_PASSWORD)
            //->registerPlugin($plugin)
            //->setAuthMode($mode)
            //->setExtensionHandlers($handlers)
            //->setAddressEncoder($addressEncoder)
            //->setPipelining($enabled)
            //->setSourceIp($source)
            //->setStreamOptions($options)
          ;
        if (defined('SMTP_ENCRYPTION') && !empty(SMTP_ENCRYPTION)) {
            $transport->setEncryption(SMTP_ENCRYPTION);
        }
        $this->mailer = new \Swift_Mailer($transport);
        $this->message = $this->mailer->createMessage();
        //$this->message = new \Swift_Message();
    }

    public function ready() {
        if (!defined('SMTP_HOST')) {
            return false;
        }
        if (!defined('SMTP_PORT')) {
            return false;
        }
        if (!defined('SMTP_USERNAME')) {
            return false;
        }
        if (!defined('SMTP_PASSWORD')) {
            return false;
        }
        if (empty(SMTP_HOST)) {
            return false;
        }
        if (empty(SMTP_PORT)) {
            return false;
        }
        if (empty(SMTP_USERNAME)) {
            return false;
        }
        if (empty(SMTP_PASSWORD)) {
            return false;
        }
        return true;
    }

    public function add_html($email_text, $text) {
        $this->message->setBody($email_text, 'text/html');
        $this->message->addPart($text, 'text/plain');
    }

    public function add_text($text) {
        $this->message->setBody($text);
    }

    public function add_attachment($file, $name) {
        //$this->message->attachContent($file, ['fileName' => $name, 'contentType' => mime_content_type($name)]);
        $attachment = new \Swift_Attachment($file, $name);
        $this->message->attach($attachment);
    }

    public function build_message() {
        
    }

    public function addBcc($bcc) {
        $this->message->setBcc($bcc);
    }

    public function send($to_name, $to_email_address, $from_email_name, $from_email_address, $email_subject, $headers) {
        $this->message
            ->setSubject($email_subject)
            ->setFrom([$from_email_address => $from_email_name])
            ->setTo([$to_email_address => $to_name])
            //->setBody('test')
            ;
        if (is_array($headers)) {
            $messageHeaders = $this->message->getHeaders();
            foreach ($headers as $key => $value) {
                $messageHeaders->addTextHeader($key, $value);
            }
        }
        return $this->mailer->send($this->message);
    }

}
