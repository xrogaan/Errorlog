<?php
/**
 * @category Taplod
 * @package Taplod_Db
 * @copyright Copyright (c) 2009, Bellière Ludovic
 * @license http://opensource.org/licenses/mit-license.php MIT license
 */

require_once 'ErrorLog/Db/Abstract.php';

class ErrorLog_Db_Adapter_Pdo_Sqlite extends ErrorLog_Db_Adapter_Abstract {
    protected $_fetchMode = PDO::FETCH_ASSOC;

    protected $_pdoType = 'sqlite';

    /**
     * (non-PHPdoc)
     * @see Db/Adapter/ErrorLog_Db_Adapter_Abstract#_connect()
     */
    protected function _connect() {
        if ($this->_connection) {
            return;
        }

        if (!extension_loaded('pdo')) {
            require 'ErrorLog_Exception.php';
            throw new ErrorLog_Exception('The pdo is required for this adapter.');
        }

        $dsn = $this->_pdoType . ':' . $this->_config['dbname'];

        try {
            $this->_connection = new PDO($dsn);
            $this->_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            require_once 'ErrorLog_Exception.php';
            throw new ErrorLog_Exception($e->getMessage());
        }
    }

        protected function _checkRequiredConfigOptions(array $config) {
            if (!array_key_exists('dbname', $config)) {
                require_once 'ErrorLog_Exception.php';
                throw new ErrorLog_Exception("Configuration array must have a 'dbname' key.");
            }
        }

    /**
     * (non-PHPdoc)
     * @see Db/Adapter/ErrorLog_Db_Adapter_Abstract#isConnected()
     */
    public function isConnected() {
        return ($this->_connection instanceof PDO);
    }

    /**
     * (non-PHPdoc)
     * @see Db/Adapter/ErrorLog_Db_Adapter_Abstract#closeConnection()
     */
    public function closeConnection() {
        $this->_connection = null;
    }
}