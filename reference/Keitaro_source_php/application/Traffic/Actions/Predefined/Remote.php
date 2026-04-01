<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Actions\Predefined;

class Remote extends \Traffic\Actions\AbstractAction
{
    protected $_weight = 130;
    protected $_ttl = 60;
    private static $_stubs = [];
    protected function _execute()
    {
        $this->_executeInContext();
    }
    protected function _executeDefault()
    {
        $url = $this->_getRemoteUrl($this->getActionPayload());
        $this->setDestinationInfo($url);
        $this->redirect($url);
    }
    protected function _executeForFrame()
    {
        $url = $this->_getRemoteUrl($this->getActionPayload());
        $this->setDestinationInfo($url);
        $this->setContent(\Traffic\Actions\Service\RedirectService::instance()->frameRedirect($url));
    }
    protected function _executeForScript()
    {
        $url = $this->_getRemoteUrl($this->getActionPayload());
        $this->setDestinationInfo($url);
        $this->setContent(\Traffic\Actions\Service\RedirectService::instance()->scriptRedirect($url));
    }
    protected function _getRemoteUrl($from)
    {
        $filename = $this->_fileName($from);
        if (is_file($filename) && time() - filemtime($filename) < $this->_ttl) {
            $url = trim(@file_get_contents($filename));
        } else {
            $url = trim(strip_tags($this->_request($from)));
            if ($url) {
                file_put_contents($filename, $url);
            }
        }
        if ($url && !strstr($url, "://")) {
            $url = $this->_appendParams($url, $from);
        }
        return $url;
    }
    private function _request($url)
    {
        if (isset(self::$_stubs[$url])) {
            return self::$_stubs[$url];
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, html_entity_decode($url));
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, "REMOTE");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        return curl_exec($ch);
    }
    protected function _fileName($url)
    {
        return ROOT . \Traffic\Cache\Cache::DEFAULT_CACHE_DIR . "/" . md5($url) . ".link";
    }
    protected function _appendParams($actualUrl, $url)
    {
        if (!$actualUrl) {
            return "";
        }
        $url = parse_url($url);
        parse_str($url["query"], $queryParams1);
        $actualUrl = parse_url($actualUrl);
        parse_str($actualUrl["query"], $queryParams2);
        if (!isset($actualUrl["host"]) && isset($actualUrl["path"])) {
            $actualUrl["host"] = $actualUrl["path"];
            $actualUrl["path"] = "/";
        }
        if (!isset($actualUrl["scheme"])) {
            $actualUrl["scheme"] = "http";
        }
        $actualUrl["query"] = http_build_query(array_merge($queryParams1, $queryParams2));
        $newUrl = $actualUrl["scheme"] . "://";
        $newUrl .= $actualUrl["host"];
        if (isset($actualUrl["port"])) {
            $newUrl .= ":" . $actualUrl["port"];
        }
        $newUrl .= $actualUrl["path"];
        if (isset($actualUrl["query"])) {
            $newUrl .= "?" . $actualUrl["query"];
        }
        return $newUrl;
    }
    public static function stub($url, $content)
    {
        self::$_stubs[$url] = $content;
    }
}

?>