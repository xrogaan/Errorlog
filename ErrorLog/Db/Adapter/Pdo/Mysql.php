<?php
/**
 * @category Taplod
 * @package Taplod_Db
 * @copyright Copyright (c) 2009-2010, Bellière Ludovic
 * @license http://opensource.org/licenses/mit-license.php MIT license
 */

require_once 'ErrorLog/Db/Abstract.php';

class ErrorLog_Db_Adapter_Pdo_Mysql extends ErrorLog_Db_Adapter_Abstract {
    protected $_fetchMode = PDO::FETCH_ASSOC;
    
    protected $_pdoType = 'mysql';
    
    /**
     * (non-PHPdoc)
     * @see ErrorLog/Db/Adapter/Abstract.php#_connect()
     */
    protected function _connect() {
        if ($this->_connection) {
            return;
        }
        
        if (!extension_loaded('pdo')) {
            require_once 'ErrorLog/Exception.php';
            throw new ErrorLog_Exception('The pdo is required for this adapter.');
        }
        
        $dsn = $this->_pdoType . ':dbname=' . $this->_config['dbname'] . ';host=' . $this->_config['host'];
        
        try {
            $this->_connection = new PDO($dsn, $this->_config['username'], $this->_config['password']);
            $this->_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            require_once 'ErrorLog/Exception.php';
            throw new ErrorLog_Exception($e->getMessage());
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see  ErrorLog/Db/Adapter/ErrorLog_Db_Adapter_Abstract#isConnected()
     */
    public function isConnected() {
        return ($this->_connection instanceof PDO);
    }
    
    /**
     * (non-PHPdoc)
     * @see Db/Adapter/Adapter/ErrorLog_Db_Adapter_Abstract#closeConnection()
     */
    public function closeConnection() {
        $this->_connection = null;
    }
}
