<?php
/**
 * @category ErrorLog
 * @package ErrorLog_Writer
 * @copyright Copyright (c) 2010, Ludovic Bellière
 * @license http://opensource.org/licenses/mit-license.php MIT license
 */

require_once 'ErrorLog/Writer/Abstract.php';

/**
 * PEAR_Mail
 */
require_once 'Mail.php'; 

/**
 * Send logs by Emails
 *
 * @category ErrorLog
 * @package ErrorLog_Writer
 * @copyright Copyright (c) 2010, Ludovic Bellière
 * @license http://opensource.org/licenses/mit-license.php MIT license
 * @author Ludovic Bellière
 */
class ErrorLog_Writer_Mail extends Errorlog_Writer_Abstract {

    private $recipients;
    
    /**
     * @var PEAR_Mail
     */
    protected $mail_instance;
    
    protected $messages = array();
    protected $application = 'ErrorLog';
    
    const DEFAULT_ERROR_SUBJECT = '[%1$s] An %2$s occured while bootstrapping the application';
    const DEFAULT_SUBJECT = '[%1$s] New messages from your application';

    private function getSubject()
    {
        switch ($this->_activeLogType) {
            case ErrorLog::LOG_EXCEPTION:
                return sprintf(self::DEFAULT_ERROR_SUBJECT, $this->application, 'exception');
            case ErrorLog::LOG_ERROR:
                return sprintf(self::DEFAULT_ERROR_SUBJECT, $this->application, 'error');
            default:
                return sprintf(self::DEFAULT_SUBJECT, $this->application);
        }
    }
    
    private function buildRecipients()
    {
        if (!is_array($this->_config['recipients'])) {
            return $this->_config['recipients'];
        }
        
        return implode(', ', $this->_config['recipients']);
    }

    protected function init()
    {
        if (isset($this->_config['application'])) {
            $this->application = $this->_config['application'];
        }
        
       
        $this->headers = array(
            'From'    => $this->_config['from_email'],
            'To'      => self::buildRecipients(),
            'Subject' => self::getSubject()
        );
        
        self::open();
    }
    
    public function open()
    {
        $this->mail_instance =& Mail::factory($this->_config['backend'], $this->_config['params']);
    }
    
    public function close()
    {
        if (!$this->_config['an_email_for_every_message']) {
            $this->mail_instance->send(self::buildRecipients(), $this->headers, implode("\n\n- - - - -\n",$this->body));
        }
    }
    
    protected function _write()
    {
        // if the error is lethal, we don't wait and directly send  the mail
        if ($this->_logData['severity'] <= ErrorLog::ERR || $this->_config['an_email_for_every_message']) {
            $this->body = self::getMessage();
            $this->mail_instance->send(self::buildRecipients(), $this->headers, $this->body);
        } else {
            $this->body[] = self::getMessage();
        }
    }

}