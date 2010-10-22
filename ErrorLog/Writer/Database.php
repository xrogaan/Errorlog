<?php
/**
 * @category ErrorLog
 * @package ErrorLog_Writer
 * @copyright Copyright (c) 2010, Ludovic Bellière
 * @license http://opensource.org/licenses/mit-license.php MIT license
 */

require_once 'ErrorLog/Writer/Abstract.php';

/**
 * Write messages in a file.
 *
 * @category ErrorLog
 * @package ErrorLog_Writer
 * @copyright Copyright (c) 2010, Ludovic Bellière
 * @license http://opensource.org/licenses/mit-license.php MIT license
 * @author Ludovic Bellière
 */
class ErrorLog_Writer_Database extends Errorlog_Writer_Abstract
{

    protected $adapter = 'none';
    protected function init() {
        if (!isset($this->_config['adapter'])) {
            require_once 'ErrorLog/Exception.php';
            throw new ErrorLog_Exception("Adapter name's missing.");
        }
        
        self::open();
    }
    
    /**
     * (non-PHPdoc)
     * @see ErrorLog/Writer/ErrorLog_Writer_Abstract#open()
     */
    public function open() {
        require_once 'ErrorLog/Db.php';
        $this->_db = new ErrorLog_Db($this->_config['adapter'], $this->_config);
    }
    
    /**
     * (non-PHPdoc)
     * @see ErrorLog/Writer/ErrorLog_Writer_Abstract#close()
     */
    public function close(){}

    /**
     * (non-PHPdoc)
     * @see ErrorLog/Writer/ErrorLog_Writer_Abstract#_write()
     */
    protected function _write() {
        $this->_logData['timestamp'] = $this->_logData['raised'];
        $this->_logData['timestamp'] = $this->_logData['raised'];
        
        if (isset($this->_logData['extra']) && is_array($this->_logData['extra'])) {
            $this->_logData['extra'] = self::buildMessageFromArray($this->_logData['extra']);
        }
        if (isset($this->_logData['params']) && is_array($this->_logData['params'])) {
            $this->_logData['params'] = self::buildMessageFromArray($this->_logData['params']);
        }
        if (isset($this->_logData['env']) && is_array($this->_logData['env'])) {
            $this->_logData['env'] = self::buildMessageFromArray($this->_logData['env']);
        }
        
        $this->_db->insert($this->_config['table']);
    }
    

}