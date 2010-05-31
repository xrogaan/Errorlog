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
    public function store($message, $severity, $file='', $line=0, $trace=array(), $extra=null)
    {    
        if (empty($trace))
        {
            $trace = null;
        }

        
        if (empty($file) && !$line)
        {
            $this->storeMessage($message, $severity);
        }
        
       $logData = array(
            'message' => $message,
            'file'    => $file,
            'line'    => $line,
            'raised'  => date('c'),
            'trace'   => $trace,
            'params'  => array(
                'post'    => $_POST,
                'get'     => $_GET,
                'cookie'  => $_COOKIE
            ),
            'env'     => $_SERVER,
        );

        if (!empty($extra)) {
            if (is_array($extra)) {
                $info = array();
                foreach ($extra as $key => $value) {
                    if (is_string($key)) {
                        $logData[$key] = $value;
                    } else {
                        $info[] = $value;
                    }
                }
            } else {
                $info = $extra;
            }

            if (!empty($info)) {
                $logData['info'] = $info;
            }
        }
    }
    
    protected function storeMessage($message, $severity) {
        return array(
            'message'  => $message,
            'severity' => $severity
        );
    }

    protected function storeException($errstr, $code, $errfile, $errline, $traceback, $severity=0) {

        if (is_null($severity) && $code !== false) {
            $severity = $code;
        }

        if (empty($traceback)) {
            $traceback = debug_backtrace();
        }

        return array(
            'message'   => $errstr,
            'severity'  => $severity,
            'file'      => $errfile,
            'line'      => $errline,
            'traceback' => $traceback,
            'code'      => $code
        );
    }
}

