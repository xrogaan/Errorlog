<?php
/**
 * @category ErrorLog
 * @package ErrorLog_Writer
 * @copyright Copyright (c) 2010, Bellière Ludovic
 * @license http://opensource.org/licenses/mit-license.php MIT license
 */

abstract class Errorlog_Writer_Abstract {

    protected $_config;
    protected $_format;
    protected $_logData;
    protected $_activeLogType;
    protected $_logTypes;
    
    const DEFAULT_FORMAT = "%timestamp% %errorName% (%errorLevel%): %message%\n";

    public function __construct($config=array()) {

        $this->_config = $config;

        $this->_logTypes = array(
            ErrorLog::LOG_NONE,
            ErrorLog::LOG_MESSAGE,
            ErrorLog::LOG_PHPERROR,
            ErrorLog::LOG_EXCEPTION
        );
        
        $exceptionDefaultFormat = <<<EOF
An exception occured while bootstrapping the application.
=========================================================
%timestamp% %errorName% (%errorLevel%) : %message%

Stack Trace:
%backtrace%
EOF;
        $phperrorDefaultFormat = <<<EOF
An error occured while bootstrapping the application.
=====================================================
%timestamp% %file (%line) : %message%
EOF;
        $defaultFormat = "%timestamp% %errorName% (%errorLevel%): %message%\n";

        $this->setFormat($exceptionDefaultFormat, ErrorLog::LOG_EXCEPTION);
        $this->setFormat($phperrorDefaultFormat, ErrorLog::LOG_PHPERROR);
        $this->setFormat($defaultFormat, ErrorLog::LOG_MESSAGE);

        if (array_key_exists('formatter', $this->_config)) {
            if (is_array($this->_config['formatter'])) {
                foreach($this->_config['formatter'] as $type => $format) {
                    $this->setFormat($format, $type);
                }
             } else {
                $this->setFormat($this->_config['formatter'], 'ALL');
            }
        }

        $this->init();
    }

    abstract protected function init();

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
     * @param array  $logData
     * @param integer  $logType
     */
    public function store($logData, $logType)
    {    
        self::setType($logType);
        
        switch ($logType)
        {
            default:
            case ErrorLog::LOG_NONE:
                $required = array();
                break;
            case ErrorLog::LOG_MESSAGE:
                $required = array('message', 'severity');
                break;
            case ErrorLog::LOG_PHPERROR:
                $required = array('message','severity','file','line');
                break;
            case ErrorLog::LOG_EXCEPTION:
                if (empty($logData['backtrace']))
                {
                    $logData['backtrace'] = debug_backtrace();
                }
                $required = array('message','severity','file','line');
                break;
        }
        
        $logData['errorName']  = ErrorLog::getErrorLevelLabel($logData['severity']);
        $logData['errorLevel'] = $logData['severity'];
        
        $this->_logData = $logData;
        
        if (self::checkDefaultKeys($required))
        {
            $this->_write();
        }
    }
    
    /**
     * Set the current log type.
     *
     * If the given arguement doesn't match the active log types, an exception
     * is raised.
     *
     * @param integer $logType
     */
    protected function setType($logType) {
        if (!in_array($logType, $this->_logTypes))
        {
            throw new ErrorLog_Exception('The logType given ('.$logType.') does not exists or isn\'t active.');
        }
        $this->_activeLogType = $logType;
    }

    /**
     * Register a format for the given log type.
     *
     * @param string  $format  Format specifier for log message.
     * @param integer $logType Kind of log to process.
     */
    public function setFormat($format,$logType='ALL')
    {
        if (empty($format)) {
            throw new ErrorLog_Exception('Trying to override the format with an empty value.');
        }

        if ($logType != 'ALL' && !in_array($logType, $this->_logTypes)) {
            /*
             * instead of throwing an error and spoil the original error message
             * we will just add a notice to the end of the current message and
             * use the default format.
             */
            //throw new ErrorException('Invalid logType given.');
            $format = self::DEFAULT_FORMAT;
            $logType = ErrorLog::LOG_NONE;
            $this->_logData['extra'][] = 'WARNING: Invalid logType given. You are seeing the default log format';
        }
        
        if ($logType == 'ALL') {
            foreach ($this->_logTypes as $tmp_logtype) {
                $this->_format[$tmp_logtype] = $format;
            }
        } else {
            $this->_format[$logType] = $format;
        }
    }

    /**
     * Formats data and return the message.
     *
     * @return string
     */
    public function getMessage()
    {
        $tmpTable = '';
        $format = $this->_format[$this->_activeLogType];
        foreach ($this->_logData as $key => $value)
        {
            if ($key == 'raised')
            {
                $key = 'timestamp';
            }
            
            if (is_array($value))
            {
                if ($key == 'extra' || $key == 'params' || $key == 'env')
                {
                    $tmpValue = array();
                    foreach ($value as $extraKey => $extraValue)
                    {
                        if (is_array($extraValue))
                        {
                            $extraValue = self::buildMessageFromArray($extraValue);
                        }
                        $tmpValue[$extraKey] = $extraValue;
                    }
                    $value = $tmpValue;
                    unset($tmpValue);
                }
                $value = self::buildMessageFromArray($value);
            }
            $format = str_replace("%$key%", $value, $format);
        }
        return $format;
    }
    
    /**
     * indent a string with another string
     *
     * @param string $value
     * @param string $pad_lenght
     */
    function indent($value, $pad_lenght) {
        return implode("\n$pad_lenght",explode("\n", $value));
    }
    
    /**
     * Format an array with a print_r like form.
     *
     * @param array $array
     * @param int   $level
     * @param int   $lastlen
     */
    function buildMessageFromArray(array $array, $level=1, $lastlen=0) {
    
        if (empty($array)) {
            return '';
        }
        
        $level = (int) $level;
        
        $pad_lenght = 0;
        $tmplen = 0;
        foreach ($array as $key => $value) {
            $tmplen = strlen($key);
            if ($pad_lenght < $tmplen) {
                $pad_lenght = $tmplen;
            }
        }
        unset($tmplen,$key,$value);
        
        $prekey = $llen = '';
        $tmp='';
        foreach ($array as $key => $value) {
            if ($lastlen != 0 && $level != 1) {
                $tmpllen = '    ';
                for ($i=1; $i<$level; $llen.=$tmpllen,$i++);
                $prekey = str_pad($llen, $lastlen, ' ');
                $lastlen=0;
            }
            
            $key = $prekey . str_pad($key, $pad_lenght, ' ',STR_PAD_LEFT);
            
            if (is_array($value)) {
                $value = self::buildMessageFromArray($value, 1+$level, $pad_lenght);
                $tmp.="$key:\n". $value;
            } else {
                $tmp.="$key: " . self::indent($value, str_pad('', strlen($key)+2), ' ') . "\n";
            }
        }
        return $tmp;
    }

    /**
     * Formats data from an array to a html table then return it.
     * 
     * @param array $array 
     * @return string      Formatted string
     */
    protected function buildHtmlMessageFromArray(array $array) {
        if (empty($array))
        {
            return '';
        }

        $tmpTable = '<table>';
        foreach ($array as $tableKey => $tableValue)
        {
            $tmpTable.= '<tr><td>' . $tableKey . '</td><td><pre>' . print_r($tableValue,true) . '</pre></td></tr>';
        }
        $tmpTable.= '</table>';
        return $tmpTable;
    }

    /**
     * Check the existence in the report of the given key
     *
     * @param array $extraKey
     * @return boolean
     */
    protected function checkDefaultKeys (array $extraKey=array()) {
        if (is_null($this->_logData))
        {
            return false;
        }
        $check = array_flip(array_merge(array('raised', 'env', 'params'), $extraKey));
        if ($n=count(array_diff_key($check, $this->_logData)) >= 1)
        {
            throw new ErrorLog_Exception($n . ' required key are missing from the report.');
        }
        return true;
    }
}

