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
        
        if (array_key_exists('formatter', $this->_config)) {
            if (is_array($this->_config['formatter'])) {
                foreach($this->_config['formatter'] as $type => $format) {
                    $this->setFormat($format, $type);
                } else {
                    $this->setFormat($this->_config['formatter']['format'], $this->_config['formatter']['type']);
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
        if (empty($format))
        {
            throw new ErrorLog_Exception('Trying to override the format with an empty value.');
        }

        if ($logType != 'ALL' && !in_array($logType, $this->_logTypes))
        {
            /*
             * instead of throwing an error and spoil the original error message
             * we will just add a notice to the end of the current message and
             * use the default format.
             */
            //throw new ErrorException('Invalid logType given.');
            $format = self::DEFAULT_FORMAT;
            $this->_logData['extra'][] = 'WARNING: Invalid logType given. You are seeing the default log format';
        }

        $this->_format[$logType] = $format;
    }

    /**
     * Formats data and return the message.
     *
     * @return string
     */
    public function getMessage()
    {
        $tmpTable = '';
        $format = (!is_null($this->_format)) ? $this->_format[$this->_activeLogType] : self::DEFAULT_FORMAT;
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
     * Formats data from an array to a html table then return it.
     * 
     * @param array $array 
     * @return string      Formatted string
     */
    protected function buildMessageFromArray(array $array) {
        if (empty($array))
        {
            return '';
        }

        $tmpTable = '<table>';
        foreach ($array as $tableKey => $tableValue)
        {
            $tmpTable.= '<tr><td>' . $tableKey . '</td><td>' . $tableValue . '</td></tr>';
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

