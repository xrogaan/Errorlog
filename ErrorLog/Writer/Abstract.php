<?php

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
     * Store data into the log
     *
     * @param string  $message
     * @param string  $file
     * @param integer $line
     * @param array   $errno (optionnal)
     * @param mixed   $extra (optionnal)
     */
    abstract public function store($message, $file, $line, $raised, $trace, $extra) {}
}

