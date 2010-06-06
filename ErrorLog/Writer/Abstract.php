<?php
/**
 * @category ErrorLog
 * @package ErrorLog_Writer
 * @copyright Copyright (c) 2010, Bellière Ludovic
 * @license http://opensource.org/licenses/mit-license.php MIT license
 */

abstract class Errorlog_Writer_Abstract {

    protected $_format;
    protected $_logData;
    protected $_activeLogType;
    protected $_logTypes;

    const DEFAULT_FORMAT = '%timestamp% %errorName% (%errorLevel%): %message%';

    public function __construct() {
        $this->_activeLogType = array(
            ErrorLog::LOG_NONE,
            ErrorLog::LOG_MESSAGE,
            ErrorLog::LOG_PHPERROR,
            ErrorLog::LOG_EXCEPTION
        );

        self::init();
    }

    abstract function init();

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

    protected function storeUndefined($logData)
    {
        $this->_logData = $logData;
        if (self::checkDefaultKeys()) {
            self::_write();
        }
    }

    protected function storeMessage($logData)
    {
        $this->_logData = $logData;
        $required = array('message', 'severity');
        if (self::checkDefaultKeys($required))
        {
            self::_write();
        }
    }
    
    protected function storePhpError($logData)
    {
        $this->logData = $logData;
        $required = array('message','severity','file','line');
        if (self::checkDefaultKeys($required))
        {
            self::_write();
        }
    }

    protected function storeException($logData)
    {
        if (empty($logData['backtrace'])) {
            $logData['backtrace'] = debug_backtrace();
        }
        $this->logData = $logData;
        $required = array('message','severity','file','line');
        if (self::checkDefaultKeys($required))
        {
            self::_write();
        }
    }

    /**
     * Set the current log type.
     *
     * If the given arguement doesn't match the active log types, an exception
     * is raised.
     *
     * @param integer $type
     */
    protected function setType($type) {
        if (!in_array($logType, $this->_activeLogType))
        {
            throw new ErrorLog_Exception('The logType given does not exists or isn\'t active.');
        }
        $this->_logType = $type;
    }

    /**
     * Register a format for the given log type.
     *
     * @param string  $format
     * @param integer $logType
     */
    public function setFormat($format,$logType='ALL') {
        if (empty($format))
        {
            throw new ErrorException('Trying to override the format with an empty value.');
        }

        if (!in_array($logType, $this->_activeLogType))
        {
            /*
             * instead of throwing an error and spoil the original error message
             * we will just add a notice to the end of the current message and
             * use the default format.
             */
            //throw new ErrorException('Invalid logType given.');
            $format = self::DEFAULT_FORMAT;
            $this->_logData['extra'] = 'WARNING: Invalid logType given. You are seeing the default log format';
        }

        $this->_format[$logType] = $format;
    }

    /**
     * Format with data and return the message.
     *
     * @return string
     */
    public function getMessage()
    {
        $tmpTable = '';
        $format = (!is_null($this->_format)) ? $this->_format : self::DEFAULT_FORMAT;
        foreach ($this->_logData as $key => $value)
        {
            if (is_array($value))
            {
                $tmpTable = '<table>';
                foreach ($value as $tableKey => $tableValue)
                {
                    $tmpTable.= '<tr><td>' . $tableKey . '</td><td>' . $tableValue . '</td></tr>';
                }
                $tmpTable.= '</table>';
                $value = $tmpTable;
                unset($tmpTable);
            }
            str_replace($key, $value, $format);
        }
        return $format;
    }

    /**
     * Check the existence in the report of the given key
     *
     * @param array $extraKey
     * @return boolean
     */
    protected function checkDefaultKeys (array $extraKey=array()) {
        if (is_null($this->_logData)) {
            return false;
        }
        $check = array_flip(array_merge(array('raised', 'env', 'params'), $extraKey));
        if ($n=count(array_diff_key($check, $this->_logData)) >= 1) {
            throw new ErrorLog_Exception($n . ' required key are missing from the report.');
        }
        return true;
    }
}

