<?php
/**
 * @category ErrorLog
 * @package ErrorLog_Writer
 * @copyright Copyright (c) 2010, Ludovic Bellière
 * @license http://opensource.org/licenses/mit-license.php MIT license
 */

require_once 'ErrorLog/Writer/Abstract.php';

/**
 * Write messages to the syslog.
 *
 * @category ErrorLog
 * @package ErrorLog_Writer
 * @copyright Copyright (c) 2010, Ludovic Bellière
 * @license http://opensource.org/licenses/mit-license.php MIT license
 * @author Ludovic Bellière
 */
class ErrorLog_Writer_Syslog extends Errorlog_Writer_Abstract {

    protected $facility = LOG_USER;
    protected $application = 'ErrorLog';
    
    /**
     * Map ErrorLog severities to the syslog() priorities
     */
    protected $priorityList = array(
        ErrorLog::EMERG   => LOG_EMERG,
        ErrorLog::ALERT   => LOG_ALERT,
        ErrorLog::CRIT    => LOG_CRIT,
        ErrorLog::ERR     => LOG_ERR,
        ErrorLog::WARNING => LOG_WARNING,
        ErrorLog::NOTICE  => LOG_NOTICE,
        ErrorLog::INFO    => LOG_INFO,
        ErrorLog::DEBUG   => LOG_DEBUG
    );
    
    /**
     * Initialize settings.
     */
    protected function init() {
        // config params are : facility, application name
        if (isset($this->_config['facility'])) {
            $this->facility = $this->_config['facility'];
        }
        
        if (isset($this->_config['application'])) {
            $this->application = $this->_config['application'];
        }
        self::open();
    }
    
    /**
     * Open connection to system logger
     */
    public function open() {
        openlog($this->application, LOG_PID, $this->facility);
    }
    
    /**
     * Close connection to system logger
     */
    public function close() {
        closelog();
    }
    
    /**
     * Write a message the the syslog
     */
    protected function _write() {
        $message = self::getMessage();
        $priority = $this->priorityList[$this->_logData['severity']];
        syslog($priority, $message);
    }
}