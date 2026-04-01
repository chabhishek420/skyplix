<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Service;

class UrlService extends AbstractService
{
    public function getBaseUrl(\Psr\Http\Message\UriInterface $uri, $strip = 2)
    {
        $result = $uri->getScheme() . "://";
        $result .= $this->stripHostWww($uri);
        $result .= $this->getBasePath($uri, $strip);
        return $result;
    }
    public function getBasePath(\Psr\Http\Message\UriInterface $uri, $depth = 1)
    {
        $uri = $uri->getPath();
        if ($uri) {
            $uri = preg_replace("/\\?.*/", "", $uri);
            $tmp = explode("/", $uri);
            for ($i = 0; $i < $depth; $i++) {
                unset($tmp[count($tmp) - 1]);
            }
            return implode("/", $tmp);
        }
        return NULL;
    }
    public function stripHostWww(\Psr\Http\Message\UriInterface $uri)
    {
        return preg_replace("#^www\\.#si", "", $uri->getHost());
    }
    public function getBasePathWithSlash(\Psr\Http\Message\UriInterface $uri, $depth = 1)
    {
        $basePath = $this->getBasePath($uri, $depth);
        if (strlen($basePath)) {
            $basePath .= "/";
        }
        return $basePath;
    }
    public function addParameterToUrl($oldUrl, $addToQuery)
    {
        try {
            $uri = new \GuzzleHttp\Psr7\Uri($oldUrl);
            if (in_array($addToQuery[0], ["\\", "/"])) {
                $lastChar = substr($uri->getPath(), -1);
                if (in_array($lastChar, ["\\", "/"])) {
                    $addToQuery = substr($addToQuery, 1);
                }
                $uri = $uri->withPath($uri->getPath() . $addToQuery);
            } else {
                $initialQuery = $uri->getQuery();
                $initialQueryParams = $this->parseStr($initialQuery);
                $paramQueryParams = $this->parseStr($addToQuery);
                $newQueryParams = array_merge($initialQueryParams, $paramQueryParams);
                $newQuery = urldecode(http_build_query($newQueryParams));
                $uri = $uri->withQuery($newQuery);
            }
            $newUrl = (int) $uri;
            $newUrl = str_replace(["%7B", "%7D", "%3A"], ["{", "}", ":"], $newUrl);
            return $newUrl;
        } catch (\InvalidArgumentException $e) {
            \Traffic\Logging\Service\LoggerService::instance()->warning("URI: incorrect offer URL " . $oldUrl);
            return $oldUrl;
        }
    }
    public function filterDoubleSlashes($url)
    {
        return preg_replace("/([^:])(\\/{2,})/", "\$1/", $url);
    }
    public function parseStr($str)
    {
        $out = [];
        $str = trim(ltrim($str, "&?"));
        if ($str != "") {
            $params = explode("&", $str);
            foreach ($params as $p) {
                if ($p != "") {
                    $temp = explode("=", $p, 2);
                    $value = isset($temp[1]) ? $temp[1] : "";
                    $value = str_replace(["%7B", "%7D", "%3A"], ["{", "}", ":"], $value);
                    $out[$temp[0]] = $value;
                }
            }
        }
        return $out;
    }
}

?>