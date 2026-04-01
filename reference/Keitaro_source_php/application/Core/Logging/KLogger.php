<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Logging;

class KLogger
{
    private $_filename = NULL;
    private $_jid = NULL;
    const SEPARATOR = "\n";
    const OLD_SEPARATOR = "\n- - - - - - - - - -\n";
    const FORMAT = "Y-m-d H:i:s";
    const DEFAULT_LIMIT = 200;
    const JID_SIZE = 5;
    public function __construct($filename = NULL)
    {
        $this->setFilename($filename);
    }
    public function setFilename($filename)
    {
        $this->_filename = $filename;
    }
    public function getFilePath()
    {
        if (!isset($this->_filename)) {
            throw new \Core\Exception("log filename is now set");
        }
        $dir = ROOT . "/var/log/";
        $file = $dir . $this->_filename;
        return $file;
    }
    public function getMaxSize()
    {
        return \Traffic\Service\ConfigService::instance()->get("system", "log_max_size", 1000000);
    }
    public function checkSize()
    {
        $file = $this->getFilePath();
        if (is_file($file) && $this->getMaxSize() < filesize($file)) {
            unlink($file);
        }
    }
    public function log($message)
    {
        $this->checkSize();
        $file = $this->getFilePath();
        if (empty($message)) {
            return NULL;
        }
        if (is_array($message)) {
            $message = json_encode($message, JSON_PARTIAL_OUTPUT_ON_ERROR);
        }
        $message = str_replace("\n", "; ", $message);
        $message = "[" . date(FORMAT) . "] (jid: " . $this->getJid() . ") " . $message . SEPARATOR;
        @file_put_contents($file, $message, FILE_APPEND);
    }
    public function getRows($offset = NULL, $limit = NULL)
    {
        if (!isset($limit)) {
            $limit = DEFAULT_LIMIT;
        }
        if (!isset($offset)) {
            $offset = 0;
        }
        $data = @file_get_contents(@$this->getFilePath());
        $data = str_replace(OLD_SEPARATOR, SEPARATOR, $data);
        $rows = explode(SEPARATOR, $data);
        rsort($rows);
        $chunk = array_slice($rows, $offset, $limit);
        $items = [];
        foreach ($chunk as $row) {
            if (preg_match("/^\\[(.*?)] \\(jid: (.*?)\\)\\s(.*)/", $row, $result)) {
                $items[] = ["datetime" => $result[1], "jid" => $result[2], "message" => $result[3]];
            }
        }
        return ["total" => count($rows) - 1, "rows" => $items];
    }
    public function clean()
    {
        file_put_contents($this->getFilePath(), "");
    }
    public function getJid()
    {
        if (!isset($this->_jid)) {
            $pos = rand(0, 15);
            $this->_jid = substr(md5(rand(1999999, 9999999)), $pos, JID_SIZE);
        }
        return $this->_jid;
    }
}

?>