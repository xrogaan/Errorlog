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
    abstract public function open() {}
    
    /**
     * Close a log ressource.
     */
    abstract public function close() {}
    
    /**
     * Store error data into the log
     *
     * @param string  $message
     * @param string  $file
     * @param integer $line
     * @param array   $errno (optionnal)
     * @param mixed   $extra (optionnal)
     */
    public function store($message, $file, $line, $trace=array(), $extra=array())
    {    
        if (empty($trace))
        {
            $trace = null;
        }
        
        if (empty($extra))
        {
            $extra = null;
        }
        
        return array(
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
            'extra'   => $extra
        );
    }
}

