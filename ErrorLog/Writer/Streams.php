<?php
/**
 * @category ErrorLog
 * @package ErrorLog_Writer
 * @copyright Copyright (c) 2010, Ludovic Bellière
 * @license http://opensource.org/licenses/mit-license.php MIT license
 */

/**
 * Description of Streams
 *
 * @author Ludovic Bellière
 */
class ErrorLog_Writer_Streams extends Errorlog_Writer_Abstract
{

    protected $handler;
    protected $mode = 'a';
    protected $filename;

    protected function init()
    {

        if (isset($this->config['url'])) { // write to an url
            //
        } elseif (isset($this->config['stream'])) { // write to an existing stream
            if (get_resource_type($this->config['stream']) != 'stream')
            {
                throw new ErrorLog_Exception('Resource is not a stream.');
            }
        } elseif (isset($this->config['file'])) { // write to a file
            if (!isset($this->config['mode']))
            {
                throw new ErrorLog_Exception("Can't work on file without the type of access required to open the stream.");
            }
            $this->mode     = $this->config['mode'];
            $this->filename = $this->config['file'];
            if (!file_exists($this->filename))
            {
                if (!is_writable(dirname($this->filename)))
                {
                    throw new ErrorLog_Exception("Can't write to file.");
                }
            }
            self::open();
        } else {
            throw new ErrorLog_Exception("Missing parameters. I don't know what to do !");
        }
    }

    protected function open()
    {
        if (!$this->handler = fopen($this->filename, $this->mode))
        {
            throw new ErrorLog_Exception('Can not open file (' . $this->filename . ')');
        }
    }

    protected function close()
    {
        if (is_resource($this->handler))
        {
            fclose($this->handler);
        }
    }

    protected function _write()
    {
        $message = self::getMessage();
        if (fwrite($this->handler, $message) === false)
        {
            throw new ErrorLog_Exception('Can not write to file (' . $this->filename . ')');
        }
    }
}
