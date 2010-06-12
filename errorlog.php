<?php
/**
 * @category ErrorLog
 * @package ErrorLog
 * @copyright Copyright (c) 2010, Bellière Ludovic
 * @license http://opensource.org/licenses/mit-license.php MIT license
 */

require_once 'ErrorLog/Exception.php';

class ErrorLog {
    
    // 
    const EMERG   = 0;  // Emergency: system is unusable
    const ALERT   = 1;  // Alert: action must be taken immediately
    const CRIT    = 2;  // Critical: critical conditions
    const ERR     = 3;  // Error: error conditions
    const WARN    = 4;  // Warning: warning conditions
    const NOTICE  = 5;  // Notice: normal, but significant, condition
    const INFO    = 6;  // Informational: informational messages
    const DEBUG   = 7;  // Debug: debug-level messages
	
    const LOG_NONE      = 0x0010; // log type unreconized
    const LOG_MESSAGE   = 0x0011; // simple message
    const LOG_PHPERROR  = 0x0012; // php error, can be lethal
    const LOG_EXCEPTION = 0x0013; // exception, lethal


    protected static $_instance = null;
    protected $dump_session_data = true;
    
    protected $previous_error_handler = false;
    protected $previous_exception_handler = false;
    
    protected $writers = null;
    protected $writers_path = null;

    /**
     * Highter value of this will be ignored.
     *
     * @var integer
     */
    protected $_logLevel = 5;

    /**
     * List of writer where filters don't apply.
     * @var array
     */
    protected $_logLevelException = array();
    
    protected function __construct($config)
    {
        $this->writers_path = dirname(__FILE__).'/ErrorLog/Writer/';

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

        if (isset($config['logLevel'])) {
            if (self::getErrorLevelLabel($config['logLevel']) !== false) {
                self::setLogLevel($config['logLevel']);
            }
            if (isset($config['logLevelException']))
            {
                $this->_logLevelException = (array) $config['logLevelException'];
            }
        }
    }
    
    protected static function init($config)
    {
        return static::$_instance = new static($config);
    }
    
    /**
     * Return an instance of the object or null if the value of $auto_create is false.
     * 
     * @param boolean $auto_create
     * @return ErrorLog|null
     */
    public static function getInstance($config = array(), $auto_create = true)
    {
        if ((bool) $auto_create && is_null(static::$_instance)) {
            static::init($config);
        }
        return static::$_instance;
    }
	
    /**
    *
    */
    public function write($message, $severity, $file='', $line=0, $backtrace='', $extra=array())
    {
        if (empty($message))
        {
            throw new ErrorLog_Exception('First parameter is required.');
        }
        if (!is_int($severity))
        {
            throw new ErrorLog_Exception('Second parameter is invalid.');
        }
        
        $logType = self::LOG_NONE;
        if (isset($extra['logType']))
        {
            $logType = $extra['logType'];
            unset($extra['logType']);
        }
        
        $logData = array(
            'message'   => $message,
            'severity'  => $severity,
            'file'      => $file,
            'line'      => $line,
            'raised'    => date('c'),
            'backtrace' => $backtrace,
            'params'    => array(
                'post'    => $_POST,
                'get'     => $_GET,
                'cookie'  => $_COOKIE
            ),
            'env'       => $_SERVER,
        );

        if (!empty($extra))
        {
            if (is_array($extra))
            {
                $info = array();
                foreach ($extra as $key => $value)
                {
                    if (is_string($key))
                    {
                        $logData[$key] = $value;
                    }
                    else
                    {
                        $info[] = $value;
                    }
                }
            } else
            {
                $info = $extra;
            }

            if (!empty($info))
            {
                $logData['info'] = $info;
            }
        }
        
        self::_write($logData, $logType);
    }

    /**
     * Send data to the loaded writers.
     * Apply a basic filter on the severity level.
     *
     * @param array   $logData
     * @param integer $logType
     */
    protected function _write($logData, $logType) {
        foreach($this->writers as $writername => $writer)
        {
            if (!in_array($writername, $this->_logLevelException) && $logData['severity'] <= $this->_logLevel)
            {
                $writer->store($logData,$logType);
            }
        }
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
    protected function loadWriter($writer, $config = array())
    {
        if (!is_array($config))
        {
            $config = (array) $config;
        }
        
        $writer = ucfirst($writer);
        
        if (file_exists($this->writers_path . $writer . '.php'))
        {
            require $this->writers_path . $writer . '.php';
            $writer_name = 'ErrorLog_Writer_' . $writer;
            if (class_exists($writer_name))
            {
                if (empty($config))
                {
                    $this->writers[strtolower($writer)] = new $writer_name();
                }
                else
                {
                    $this->writers[strtolower($writer)] = new $writer_name($config);
                }
            }
            else
            {
                if ($this->previous_exception_handler !== false)
                {
                    restore_exception_handler();
                }
                throw new ErrorLog_Exception('Writer object "' . $writer_name . '" couldn\'t be found.');
            }
        }
        else
        {
            if ($this->previous_exception_handler !== false)
            {
                restore_exception_handler();
            }
            throw new ErrorLog_Exception('The file for the writer ' . $writer . ' couldn\'t be found.');
        }
        return $this;
    }

    public function setLogLevel($loglevel)
    {
        $this->_logLevel = $loglevel;
    }
    
    public function logException(Exception $exception)
    {
        $data = array();
        if ($this->dump_session_data)
        {
            $data['SESSION'] = $_SESSION;
        }
        
        $logData = array(
            'message'    => $exception->getMessage(),
            'severity'   => $exception->getCode(),
            'file'       => $exception->getFile(),
            'line'       => $exception->getLine(),
            'extra'      => $data,
            'backtrace'  => $exception->getTrace(),
        );
        
        $this->write($logData, self::LOG_EXCEPTION);
    }
    
    public function logPhpError($errno, $errstr, $errfile='', $errline=0, array $errcontext=array())
    {
        $data = array();
        if ($this->dump_session_data)
        {
            $data['SESSION'] = $_SESSION;
        }
        
        if (!empty($errorcontext))
        {
            $data['errorcontext'] = $errorcontext;
        }
        
        $logData = array(
            'message'    => $errstr,
            'severity'   => $errno,
            'file'       => $errfile,
            'line'       => $errline,
            'extra'      => $data,
            'backtrace'  => debug_backtrace()
        );
        
        $this->write($logData, self::LOG_PHPERROR);
    }

    /**
     * @param string $message
     */
    public function log($message)
    {
        if (empty($message) || !is_string($message))
        {
            return false;
        }    
        $this->write($message, self::INFO);
        return true;
    }
    
    /**
     * @param string $message
     * @param int    $errorlevel
     */ 
    public function warn($message, $errorlevel=4)
    {
        if (empty($message) || !is_string($message))
        {
            return false;
        }    
        $this->write($message, $errorlevel);
        return true;
    }

    /**
     * @param string $message
     * @param int    $errorlevel
     */ 
    public function error($message, $file, $line, $severity=3)
    {
        if (empty($message) || !is_string($message))
        {
            return false;
        }   
        $this->write($message, $severity, $file, $line, debug_backtrace());
        return true;
    }
	
    public function registerErrorHandler($level=false)
    {
        if ($level === false)
        {
            $level = E_ALL | E_STRICT;   
        }
        
        $this->previous_error_handler = set_error_handler(array($this, 'logPhpError'), $level);
    }
    
    public function registerExceptionHandler()
    {
        $this->previous_exception_handler = set_exception_handler(array($this, 'logException'));
    }

    static public function getErrorLevelLabel($errorLevel)
    {
        $r = new ReflectionClass('ErrorLog');
        $constants = array_flip($r->getConstants());

        if (array_key_exists($errorLevel, $constants) && strpos($constants[$errorLevel], 'LOG_') === false)
        {
            return $constants[$errorLevel];
        }
        else
        {
            return false;
        }
    }

    public function __destruct()
    {
        if (!empty($this->writers))
        {
            foreach ($this->writers as $name => $object)
            {
                $object->close();
            }
        }
    }
}

