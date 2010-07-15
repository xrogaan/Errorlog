<?php
/**
 * @category ErrorLog
 * @package ErrorLog_Db
 * @copyright Copyright (c) 2009-2010, Bellière Ludovic
 * @license http://opensource.org/licenses/mit-license.php MIT license
 */

/**
 * @category ErrorLog
 * @package ErrorLog_Db
 * @copyright Copyright (c) 2009-2010, Bellière Ludovic
 * @license http://opensource.org/licenses/mit-license.php MIT license
 */
class ErrorLog_Db {
    
    /**
     *
     * @param string $adapter
     * @param mixed $config
     * @return ErrorLog_Db_Adapter_Abstract
     */
    public static function factory ($adapter, $config = array()) {
        if (!is_array($config)) {
            $config = (array) $config;
        }

        
        if (!is_string($adapter) || empty($adapter)) {
            /**
             * @see ErrorLog_Exception
             */
            require 'ErrorLog/Exception.php';
            throw new ErrorLog_Exception('Adapter name must be specified in a string');
        }
        
        $adapterNamespace = 'ErrorLog_Db_Adapter';
        if (isset($config['adapterNamespace'])) {
            $adapterNamespace = $config['adapterNamespace'];
        }
        
        $adapterName = $adapterNamespace . '_' . $adapter;
        
        self::loadClass($adapterName);
        
        $dbAdapter = new $adapterName($config);
        
        if (! $dbAdapter instanceof ErrorLog_Db_Adapter_Abstract) {
            require 'ErrorLog/Exception.php';
            throw new ErrorLog_Exception ("Adapter Class '$adapterName' does not extend ErrorLog_Db_Adapter_Abstract");
        }
        
        return $dbAdapter;
    }
    
    /**
     * Load a class from a php file.
     */
    protected function loadClass($class) {
        if (class_exists($class, false) || interface_exists($class, false)) {
            return false;
        }
        
        $filename = trim(str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php');
        
        if (self::fileExists($filename)) {
            try {
                include_once $filename;
            } catch (exception $e) {
                die($e->getMessage());
            }
        } else {
            require 'ErrorLog/Exception.php';
            throw new ErrorLog_Exception("File '$filename' was not found.");
        }
        
        if (!class_exists($class, false) && !interface_exists($class, false)) {
            require 'ErrorLog/Exception.php';
            throw new ErrorLog_Exception("Class \"$class\" was not found in the source file \"$file\".");
        }
    }
    
    protected function fileExists($filename) {
        $path = explode(PATH_SEPARATOR,ini_get('include_path'));

        foreach ($path as $dir) {
            $file = $dir.DIRECTORY_SEPARATOR.$filename;
            if (file_exists($file)) {
                return true;
            } else {
                if (defined('APPLICATION_ENVIRONMENT') && APPLICATION_ENVIRONMENT == 'development')
                    echo "$file doesn't exists.";
            }
        }
        return false;
    }

}
