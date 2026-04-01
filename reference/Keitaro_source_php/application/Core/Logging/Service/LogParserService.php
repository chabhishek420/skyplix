<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Logging\Service;

class LogParserService extends \Traffic\Service\AbstractService
{
    const SYSTEM_FORMAT = 1;
    const POSTBACK_FORMAT = 2;
    const SENT_POSTBACK_FORMAT = 3;
    const TRAFFIC_FORMAT = 4;
    const BUFFER_SIZE = 65535;
    public function getLogList($path, $filename)
    {
        $res = [];
        $files = new \FilesystemIterator($path);
        $files = new \RegexIterator($files, "/[\\/\\\\]" . $filename . "(\\-\\d{4}\\-\\d{2})?(\\-\\d{2})?\\.log\$/");
        foreach ($files as $fileinfo) {
            if ($fileinfo->isFile()) {
                $res[] = $fileinfo->getFilename();
            }
        }
        sort($res);
        return $res;
    }
    public function cleanLog($path, $filename)
    {
        $list = $this->getLogList($path, $filename);
        foreach ($list as $file) {
            unlink($path . "/" . $file);
        }
    }
    public function getRows($path, $filename, $offset, $limit, $format, $query = NULL)
    {
        $list = $this->getLogList($path, $filename);
        rsort($list);
        $pos = 0;
        $result = [];
        foreach ($list as $file) {
            $handle = fopen($path . "/" . $file, "r");
            fseek($handle, -1, SEEK_END);
            $size = ftell($handle);
            $remainder = "";
            while (0 < $size) {
                $bufferLength = BUFFER_SIZE < $size ? BUFFER_SIZE : $size;
                fseek($handle, $size - $bufferLength, SEEK_SET);
                $buffer = fread($handle, $bufferLength) . $remainder;
                $lines = array_reverse(explode("\n", $buffer));
                $remainder = array_pop($lines);
                foreach ($lines as $line) {
                    if ($this->matched($line, $query)) {
                        $line = mb_convert_encoding($line, "UTF-8", "UTF-8");
                        $record = $this->parse($line, $format);
                        if (!empty($record)) {
                            if ($offset <= $pos) {
                                if ($limit <= count($result)) {
                                } else {
                                    $result[] = $record;
                                }
                            }
                            $pos++;
                        }
                    }
                }
                $size -= BUFFER_SIZE;
                if (strlen($remainder)) {
                    if ($this->matched($remainder, $query)) {
                        $record = $this->parse($remainder, $format);
                        if (!empty($record)) {
                            if ($offset <= $pos) {
                                if ($limit > count($result)) {
                                    $result[] = $record;
                                }
                            }
                            $pos++;
                        }
                    }
                }
            }
            return $result;
        }
    }
    protected function matched($line, $query)
    {
        if (empty($query)) {
            return true;
        }
        $parts = explode("&&", $query);
        $matchCount = 0;
        foreach ($parts as $part) {
            if (stristr($line, trim($part))) {
                $matchCount++;
            }
        }
        return $matchCount == count($parts);
    }
    protected function _filterLogMessage($message, $filterBinary)
    {
        $message = trim($message);
        if ($filterBinary) {
            $message = preg_replace("/[\\x00-\\x07\\x10-\\x1F\\x7F]/u", "", $message);
        }
        return $message;
    }
    public function parse($line, $format)
    {
        $result = [];
        switch ($format) {
            case SYSTEM_FORMAT:
                if (preg_match("/^\\[([^\\]]*?)]\\s\\[([^\\]]*?)]\\s\\(jid:(.*?)\\)\\s\\[\\\"([^\\]]*?)\\\"]\\s*:\\s*(.*)\$/", $line, $match)) {
                    $result = ["datetime" => $match[1], "level_name" => $match[2], "jid" => $match[3], "context" => $match[4], "message" => $this->_filterLogMessage($match[5], false)];
                } else {
                    if (preg_match("/^\\[([^\\]]*?)]\\s(([^:]*?):\\s.*\\s)\\[\\\"([^\\]]*?)\\\"]\\s*/", $line, $match)) {
                        $result = ["datetime" => $match[1], "level_name" => $this->_getTrackerLogLevel($match[3]), "jid" => "unknown", "context" => $match[4], "message" => $this->_filterLogMessage($match[2], false)];
                    } else {
                        if (preg_match("/^\\[([^\\]]*?)]\\s(.*)\$/", $line, $match)) {
                            $result = ["datetime" => $match[1], "level_name" => "ERROR", "jid" => "unknown", "context" => "unknown", "message" => $this->_filterLogMessage($match[2], false)];
                        }
                    }
                }
                break;
            case TRAFFIC_FORMAT:
            case POSTBACK_FORMAT:
            case SENT_POSTBACK_FORMAT:
                if (preg_match("/^\\[([^\\]]*?)]\\s\\(jid:(.*?)\\)\\s*:\\s*(.*)\$/", $line, $match)) {
                    $result = ["datetime" => $match[1], "jid" => $match[2], "message" => $this->_filterLogMessage($match[3], true)];
                }
                return $result;
                break;
            default:
                throw new \Exception("Unknown log format");
        }
    }
    private function _getTrackerLogLevel($_getTrackerLogLevel, $PHPLogLevel)
    {
        switch ($PHPLogLevel) {
            case "main.WARNING":
                return "WARNING";
                break;
            case "main.NOTICE":
                return "NOTICE";
                break;
            case "main.DEBUG":
                return "INFO";
                break;
            case "main.ERROR":
            default:
                return "ERROR";
        }
    }
}

?>