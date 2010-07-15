<?php
/**
 * Some work comes form taplod (http://github.com/xrogaan/taplod/)
 *
 * @category Taplod
 * @package Taplod_Db
 * @copyright Copyright (c) 2009-2010, Ludovic Bellière
 * @license http://opensource.org/licenses/mit-license.php MIT license
 */

abstract class ErrorLog_Db_Adapter_Abstract {

    protected $_db_connection;
    protected $_config = array();

    protected $_mark_query_time;
    protected $_queries_log;
    
    private $_sql;
    
    
    function __construct($config = array()) {
        
        if (!is_array($config)) {
            throw new ErrorLog_Exception('Argument 1 passed to ' . __CLASS__ . '::' . __FUNCTION__ . ' must be an array, ' . gettype($config) . ' given.');
        }
        if (!array_key_exists('dbname', $config)) {
            require_once 'ErrorLog/Exception.php';
            throw new ErrorLog_Exception("Configuration array must have a 'dbname' key.");
        }

        if (!array_key_exists('username', $config)) {
            require_once 'ErrorLog/Exception.php';
            throw new ErrorLog_Exception("Configuration array must have a 'username' key.");
        }

        if (!array_key_exists('password', $config)) {
            require_once 'ErrorLog/Exception.php';
            throw new ErrorLog_Exception("Configuration array must have a 'password' key.");
        }

        if (!array_key_exists('host', $config)) {
            $config['host'] = 'localhost';
        }

        $this->_config = array_merge($this->_config, $config);
    
    }
    
    /**
     * Formate la requête avec les arguments fournis et quote les valeurs de manière intelligente.
     *
     * Les %s dans la requête sql sont remplacés par les arguments qui sont transformés en chaînes mysql.
     * self::_autoQuote( sql, arg1, arg2... )
     *
     * Exemple :
     * <code>
     * <?php
     * // give: UPDATE fuck SET a='1' WHERE b='popo'
     * echo self::_autoQuote( 'UPDATE hop SET a=%s WHERE b=%s', 1,'popo' );
     * ?>
     * </code>
     *
     * @return string
     */
    private function _autoQuote() {
        $args = func_get_args();
        list($_, $sql) = each($args);

        if (count($args) == 1) return $sql;

        $params = array();
        while ( list( $_, $val ) = each($args) ) {
            switch(gettype($val)) {
                case 'integer':
                    $type = PDO::PARAM_INT;
                    break;
                case 'double':
                    $type = PDO::PARAM_INT;
                    break;
                case 'boolean':
                    $type = PDO::PARAM_BOOL;
                    break;
                case NULL:
                    $type = PDO::PARAM_NULL;
                    break;
                case 'string':
                default:
                    $type = PDO::PARAM_STR;
            }
            $params[] = self::quote($val, $type);
        }
        return vsprintf($sql, $params);
    }
    
    
    public function quote($data,$type) {
        switch ($type) {
            case PDO::PARAM_INT:
                return $data;
                break;
            case PDO::PARAM_BOOL:
                return ($data) ? true : false ;
                break;
            case PDO::PARAM_NULL:
                return 'NULL';
                break;
            case PDO::PARAM_STR:
            default:
                return $this->getConnection()->quote($data);
                break;
        }
    }
    
    /**
     * Formate la requête avec les arguments fournis.
     *
     * Effectue la requête, la met dans le log de bas de page avec son temps, renvoie la requête.
     * Les %s dans la requête sql sont remplacés par les arguments qui sont transformés en chaînes mysql.
     *
     * $obj->query( sql, arg1, arg2... )
     * Exemple :
     *  $obj->query( 'UPDATE hop SET a=%s WHERE b=%s', 1,'popo' )
     *  donne    UPDATE hop SET a='1' WHERE b='popo'
     *
     * @return PDOStatement_Timer
     */
    public function query () {
        try {
            if (is_null($this->_connection)) {
                $this->getConnection();
            }
            $args = func_get_args();

            $t = microtime(true);
            $this->_sql = call_user_func_array(array('self','_autoQuote'), $args);
            $r = $this->getConnection()->query($this->_sql);
            $this->_mark_query_time = microtime(true);

            $this->_queries_log[] = array($this->_mark_query_time-$t, 'query', $args);
        } catch (PDOException $exception) {
            require_once 'ErrorLog/Exception.php';
            throw new ErrorLog_Exception($exception->getMessage());
        }
        return $r;
    }
    
    /**
     * Build & exec insert query
     *
     * Cette fonction va construire une requête d'insertion et va automatiquement
     * échapper les valeurs selon leurs type.
     * Exemple: $obj->insert('table',array('id'=>1,'data'=>'Mon premier insert'));
     *    va passer a query: "INSERT INTO `table` (id, data) VALUES (1, 'Mon premier insert')"
     *
     * @see function _autoQuote
     * @return PDOStatement_Timer
     */
    public function insert($table, $data) {
        $columns = array();
        $values  = array();

        foreach ($data as $key => $value) {
            $columns[] = $key;
            $values[]  = '%s'; //gettype($value) == 'integer' ? '%d' : '%s';
        }

        $sqlTemplate[] = 'INSERT INTO `' . $table . '` (' . implode(',', $columns) . ') VALUES (' . implode(',', $values) . ')';
        $sql = call_user_func_array(array('self','_autoQuote'), array_merge($sqlTemplate,array_values($data)));
        self::query($sql);
        return $this->getConnection()->lastInsertId();
    }
    
    /**
     * Add an item into the log
     */
    protected function _markQueriesLog($pdostatement=false) {
        if ($pdostatement) {
            $this->_queries_log['PDOStatement'][count($this->_queries_log['PDOStatement'])-1][0] += microtime(true)-$this->_mark_query_time;
        } else {
            $this->_queries_log[count($this->_queries_log)-2][0] += microtime(true)-$this->_mark_query_time;
        }
    }

    /**
     * Used to return the logs
     *
     * @return array
     */
    public function getQueriesLog() {
        return $this->_queries_log;
    }
    
    /**
     * Get initialized instance of an adapter or create one.
     * @return Taplod_Db_Adapter_Abstract
     */
    public function getConnection() {
        $this->_connect();
        return $this->_connection;
    }
    
    /**
     * Creates a connection to the database.
     *
     * @return void
     */
    abstract protected function _connect();
    
    /**
     * Test if a connection is active
     *
     * @return boolean
     */
    abstract public function isConnected();

    /**
     * Force the connection to close.
     *
     * @return void
     */
    abstract public function closeConnection();

}