<?php
/**
 * Goals: 
 *  - log all errors
 *  - stores errors in files/databases/syslog
 *  - replace php error handler
 *  - use as php exception handler.
 */

require_once 'ErrorLog/Exception.php';

class ErrorLog {

    protected static $_instance = null;
    protected $dump_session_data = true;
    
    protected $previous_error_handler = false;
    protected $previous_exception_handler = false;
    
    protected $writers = null;
    protected $writers_path = dirname(__FILE__).'/';
    
    protected function __construct() {}
    
    public static function init()
    {
        return static::$_instance = new static();
    }
    
    /**
     * Return an instance of the object or null if the value of $auto_create is false.
     * 
     * @param boolean $auto_create
     * @return ErrorLog|null
     */
    public static function getInstance($auto_create = true)
    {
        if ((bool) $auto_create && is_null(static::$_instance)) {
            static::init();
        }
        return static::$_instance;
    }
    
    public function factory($config = array())
    {
        if (!is_array($config))
        {
            $config = (array) $config;
        }
        
        if (isset($config['writers']) && is_array($config['writers']))
        {
            foreach($config['writers'] as $writer => $writer_config)
            {
                self::loadWriter($writer, $writer_config);
            }
        }
        
        if (isset($config['dump_session_data']))
        {
            $this->dump_session_data = (bool) $config['dump_session_data'];
        }
        
        return $this;
    }
    
    /**
     * Load a writer.
     * If self::registerExceptionHandler() is already called, it restore the default php handler before throwing anything.
     *
     * @param string $writer
     * @param array  $config
     * @throw ErrorLog_Exception on file/object missing
     * @return ErrorLog
     */
    protected loadWriter($writer, $config = array())
    {
        if (!is_array($config))
        {
            $config = (array) $config;
        }
        
        if (file_exists($this->writers_path . $writer))
        {
            require $this->writers_path . $writer;
            $writer_name = 'ErrorLog_Writer_' . $writer;
            if (class_exists($writer_name))
            {
                if (empty($config))
                {
                    $this->writers[$writer] = new $writer_name();
                }
                else
                {
                    $this->writers[$writer] = new $writer_name($config);
                }
            }
            else
            {
                if ($this->previous_exception_handler === false)
                {
                    restore_exception_handler();
                }
                throw new ErrorLog_Exception('Writer object "' . $writer_name . '" couldn\'t be found.');
            }
        }
        else
        {
            if ($this->previous_exception_handler === false)
            {
                restore_exception_handler();
            }
            throw new ErrorLog_Exception('The file for the writer ' . $writer . ' couldn\'t be found.');
        }
        return $this;
    }
    
    function logException(Exception $exception)
    {
        if ($this->dump_session_data)
        {
            $data['SESSION'] = $_SESSION;
        }
        
        $this->writer->store($exception->getMessage(), $exception->getFile(), $exception->getLine(), $exception->getTrace(), $data);
    }
    
    function logPhpError($errno, $errstr, $errfile='', $errline=0, (array) $errcontext=array())
    {
        if ($this->dump_session_data)
        {
            $data['SESSION'] = $_SESSION;
        }
        
        if (!empty($errorcontext))
        {
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
	
    function registerErrorHandler($level=false)
    {
        if ($level === false)
        {
            $level = E_ALL | E_STRICT;   
        }
        
        $this->previous_error_handler = set_error_handler(array($this, 'logPhpError'), $level);
    }
    
    function registerExceptionHandler()
    {
        $this->previous_exception_handler = set_exception_handler(array($this, 'logException'));
    }
}

