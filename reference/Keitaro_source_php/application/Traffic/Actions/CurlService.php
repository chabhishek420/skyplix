<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Actions;

class CurlService extends \Traffic\Service\AbstractService
{
    const TIMEOUT = 10;
    public function request($opts)
    {
        while (\Traffic\Service\ConfigService::instance()->isDemo()) {
            $headers = [];
            if (!empty($opts["referrer"])) {
                $headers["Referer"] = $opts["referrer"];
            }
            if (!empty($opts["user_agent"])) {
                $headers["User-Agent"] = $opts["user_agent"];
            }
            try {
                $request = new \GuzzleHttp\Psr7\Request("get", $opts["url"], $headers);
                try {
                    $response = \Traffic\Http\Service\HttpService::instance()->send($request, [\GuzzleHttp\RequestOptions::TIMEOUT => TIMEOUT, \GuzzleHttp\RequestOptions::ALLOW_REDIRECTS => ["track_redirects" => true]]);
                } catch (\GuzzleHttp\Exception\RequestException $e) {
                    if (!$e->hasResponse()) {
                        \Traffic\Logging\Service\LoggerService::instance()->warning("Error while requesting page '" . $opts["url"] . "': " . $e->getMessage());
                        return ["error" => $e->getMessage()];
                    }
                    $response = $e->getResponse();
                    $body = $response->getBody();
                    if (!empty($body)) {
                        $body = $this->adaptAnchors($body);
                        $redirects = $response->getHeader("X-Guzzle-Redirect-History");
                        if (!empty($redirects)) {
                            $uri = new \GuzzleHttp\Psr7\Uri(trim($redirects[count($redirects) - 1]));
                        } else {
                            $uri = new \GuzzleHttp\Psr7\Uri($opts["url"]);
                        }
                        $body = $this->addBasePath($body, $uri);
                    }
                    return ["content_type" => $response->getHeaderLine(\Traffic\Response\ContentType::HEADER), "status" => $response->getStatusCode(), "body" => $body];
                }
            } catch (\InvalidArgumentException $e) {
                $msg = "Error while parsing url " . $opts["url"] . " for curl request: " . $e->getMessage();
                \Traffic\Logging\Service\LoggerService::instance()->error($msg);
                return ["error" => $e->getMessage()];
            }
        }
        return ["body" => "Here would be content of " . $opts["url"]];
    }
    public function addBasePath($page, \Psr\Http\Message\UriInterface $uri, $skipHost = false)
    {
        if ($skipHost) {
            $path = $uri->getPath();
        } else {
            $path = "//" . $uri->getHost() . $uri->getPath();
        }
        $found = preg_match("/<base\\s+href=['\"](.*?)['\"]([^>]*?)>/si", $page, $result);
        $base = "<base href=\"" . $path . "\">";
        if ($found) {
            if (strpos($result[1], "//") === 0 || strpos($result[1], "http") === 0) {
                $base = "";
            } else {
                $page = str_replace($result[0], "", $page);
            }
        }
        if (strstr($page, "<head")) {
            $pattern = "/<head(\\s.*?)?>/";
            $replacement = "<head\$1>" . $base;
        } else {
            $pattern = "/<title(\\s.*?)?>/";
            $replacement = $base . "<title\$1>";
        }
        $page = preg_replace($pattern, $replacement, $page);
        return $page;
    }
    private function _changeAnchors($m)
    {
        if (strpos($m[1], "//") === 0 || strpos($m[1], "http://") === 0 || strpos($m[1], "https://") === 0) {
            return $m[0];
        }
        return " href=\"#" . $m[2] . "\" onclick=\"document.location.hash='" . $m[2] . "';return false;\"";
    }
    private function _changeDoubleAttr($m)
    {
        $matches = [];
        $onclick = [];
        $content = $m[0];
        $found = preg_match_all("/onclick\\s?=\\s?[\"'](.*?)[\"'][\\s>]/", $content, $matches);
        if (1 < $found) {
            while (0 < --$found) {
                $onclick[] = trim($matches[1][$found], ";");
                $content = str_replace(trim($matches[0][$found], ">"), "", $content);
            }
            $onclick[] = $matches[1][$found];
            $content = str_replace($matches[1][$found], implode(";", $onclick), $content);
        }
        return $content;
    }
    public function adaptAnchors($content)
    {
        $content = preg_replace_callback("/\\shref\\s?=\\s?[\"']([^\"^']*?)#([^\"^']*?)[\"']/", [$this, "_changeAnchors"], $content);
        $content = preg_replace_callback("/<a(.*?)[\\s'\"]+>/", [$this, "_changeDoubleAttr"], $content);
        return $content;
    }
    public function adaptResourcePaths($content)
    {
        $callback = function ($m) {
            if (strpos($m[1], "/") === 0) {
                return $m[0];
            }
            return "src=\"" . $m[1] . "\"";
        };
        $content = preg_replace_callback("/src\\s?=\\s?[\"']\\/(.*?)[\"']/", $callback, $content);
        $callback = function ($m) {
            if (strpos($m[2], "/") === 0) {
                return $m[0];
            }
            return "<link " . $m[1] . "href=\"" . $m[2] . "\"";
        };
        $content = preg_replace_callback("/<link ([^>]+)href\\s?=\\s?[\"']\\/(.*?)[\"']/si", $callback, $content);
        return $content;
    }
    public function adaptFormAction($content, $action = "index.php")
    {
        $content = str_replace("action=\"\"", "action=\"" . $action . "\"", $content);
        return $content;
    }
}

?>