<?php
/**
 * @category ErrorLog
 * @package ErrorLog_Writer
 * @copyright Copyright (c) 2010, Bellière Ludovic
 * @license http://opensource.org/licenses/mit-license.php MIT license
 */

abstract class Errorlog_Writer_Abstract {
    
    /**
     * Open a log ressource.
     */
    abstract public function open();
    
    /**
     * Close a log ressource.
     */
    abstract public function close();

    /**
     * Write a message to the log.
     */
    abstract protected function _write();

    /**
     * Store error data into the log
     *
     * @param string  $message
     * @param string  $file
     * @param integer $line
     * @param array   $errno (optionnal)
     * @param mixed   $extra (optionnal)
     */
    public function store($logData, $logType)
    {    
        switch ($logType)
        {
            default:
            case ErrorLog::LOG_NONE:
                self::storeUndefined($logData);
                break;
            case ErrorLog::LOG_MESSAGE:
                self::storeMessage($logData);
                break;
            case ErrorLog::LOG_PHPERROR:
                self::storePhpError($logData);
                break;
            case ErrorLog::LOG_EXCEPTION:
                self::storeException($logData);
                break;
        }
    }
    
    protected function storeMessage($message, $severity) {
        return array(
            'message'  => $message,
            'severity' => $severity
        );
    }
    
    protected function storePhpError($logData) {
        $required = array('message','severity','file','line','raised','env');
    }

    protected function storeException($logData) {

        if (empty($logData['backtrace'])) {
            $logData['backtrace'] = debug_backtrace();
        }

        return $logData;
    }
}

