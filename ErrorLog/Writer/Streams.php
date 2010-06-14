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
class ErrorLog_Writer_Streams extends Errorlog_Writer_Abstract
{

    protected $handler;
    protected $mode = 'a';
    protected $filename;

    protected function init()
    {
        if (isset($this->_config['stream'])) { // write to an existing stream
            if (get_resource_type($this->_config['stream']) != 'stream')
            {
                require_once 'ErrorLog/Exception.php';
                throw new ErrorLog_Exception('Resource is not a stream.');
            }
            $this->handler = $this->_config['stream'];
        } elseif (isset($this->_config['file'])) { // write to a file
            if (!isset($this->_config['mode']))
            {
                require_once 'ErrorLog/Exception.php';
                throw new ErrorLog_Exception("Can't work on file without the type of access required to open the stream.");
            }
            $this->mode     = $this->_config['mode'];
            $this->filename = $this->_config['file'];
            if (strpos($this->filename, '://') === false)
            {
                if (!file_exists($this->filename) && !is_writable(dirname($this->filename)))
                {
                    require_once 'ErrorLog/Exception.php';
                    throw new ErrorLog_Exception("Can't write to file.");
                }
            }
            self::open();
        } else {
            throw new ErrorLog_Exception("Missing parameters. I don't know what to do !");
        }
    }

    public function open()
    {
        if (!$this->handler = fopen($this->filename, $this->mode))
        {
            require_once 'ErrorLog/Exception.php';
            throw new ErrorLog_Exception('Can not open file (' . $this->filename . ')');
        }
    }

    public function close()
    {
        if (is_resource($this->handler))
        {
            fclose($this->handler);
        }
    }

    public function _write()
    {
        $message = self::getMessage();
        if (fwrite($this->handler, $message) === false)
        {
            require_once 'ErrorLog/Exception.php';
            throw new ErrorLog_Exception('Can not write to file (' . $this->filename . ')');
        }
    }
}
