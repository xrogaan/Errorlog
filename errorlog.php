<?php
/**
 * Goals: 
 *  - log all errors
 *  - stores errors in files/databases/syslog
 *  - replace php error handler
 *  - use as php exception handler.
 */


class ErrorLog {

    protected static $_instance = null;
    protected $dump_session_data = true;
    
    protected $previous_error_handler;
    protected $previous_exception_handler;
    
    protected $writer = null;
    
    protected function __construct() {
    }
    
    public static function init() {
        return static::$_instance = new static();
    }
    
    /**
     * Return an instance of the object or null if the value of $auto_create is false.
     * 
     * @param boolean $auto_create
     * @return ErrorLog|null
     */
    public static function getInstance($auto_create = true) {
        if ((bool) $auto_create && is_null(static::$_instance)) {
            static::init();
        }
        return static::$_instance;
    }
    
    public function factory($writer, $config = array()) {
        if (!is_array($config)) {
            $config = (array) $config;
        }
        
        
    }
    
    function logException(Exception $exception) {
        if ($this->dump_session_data) {
            $data['SESSION'] = $_SESSION;
        }
        
        $this->writer->store($exception->getMessage(), $exception->getFile(), $exception->getLine(), $exception->getTrace(), $data);
    }
    
    function logPhpError($errno, $errstr, $errfile='', $errline=0, (array) $errcontext=array()) {
        if ($this->dump_session_data) {
            $data['SESSION'] = $_SESSION;
        }
        
        if (!empty($errorcontext)) {
            $data['errorcontext'] = $errorcontext;
        }
        
        $this->writer->store($errstr, $errfile, $errline, null, $data);
    }

    /**
     * @param string $message
     */
    function log($message) {
    }
    
    /**
     * @param string $message
     * @param int    $errorlevel
     */ 
    static public function warn($message, $errorlevel) {
    }

    /**
     * @param string $message
     * @param int    $errorlevel
     */ 
    function error($message, $errorlevel) {
    }
	
    function registerErrorHandler($level=false) {
        if ($level === false) {
            $level = E_ALL | E_STRICT;   
        }
        $this->previous_error_handler = set_error_handler(array($this, 'logPhpError'), $level);
    }
    
    function registerExceptionHandler() {
        $this->previous_exception_handler = set_exception_handler(array($this, 'logException'));
    }
}

