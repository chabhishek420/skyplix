<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Http\Service;

class HttpService extends \Traffic\Service\AbstractService
{
    private $_client = NULL;
    const HEADERS = "headers";
    const DEFAULT_AGENT = "Keitaro HTTP Client";
    public function client()
    {
        return $this->_client;
    }
    public function buildDefaultClient()
    {
        $client = new \GuzzleHttp\Client();
        $this->setClient($client);
    }
    public function setClient(\GuzzleHttp\ClientInterface $client)
    {
        $this->_client = $client;
    }
    public function get($uri, $headers = [], $options = [])
    {
        if (!is_string($uri)) {
            throw new \InvalidArgumentException("'uri' must be a string (got: " . $uri . ")");
        }
        $request = new \GuzzleHttp\Psr7\Request("get", $uri, $this->_withDefaultHeaders($headers));
        return $this->send($request, $options);
    }
    public function getAsync($uri, $headers = [], $options = [])
    {
        if (!is_string($uri)) {
            throw new \InvalidArgumentException("'uri' must be a string (got: " . $uri . ")");
        }
        $request = new \GuzzleHttp\Psr7\Request("get", $uri, $this->_withDefaultHeaders($headers));
        return $this->sendAsync($request, $options);
    }
    public function post($uri, $headers = [], $options = [])
    {
        if (!is_string($uri)) {
            throw new \InvalidArgumentException("'uri' must be a string");
        }
        $request = new \GuzzleHttp\Psr7\Request("post", $uri, $this->_withDefaultHeaders($headers));
        return $this->send($request, $options);
    }
    public function send(\GuzzleHttp\Psr7\Request $request, $options = [])
    {
        $options = $this->_withOptionsDefaults($options);
        return $this->client()->send($request, $options);
    }
    public function sendAsync(\GuzzleHttp\Psr7\Request $request, $options = [])
    {
        $options = $this->_withOptionsDefaults($options);
        return $this->client()->sendAsync($request, $options);
    }
    public function download($uri, $destination, $headers = [], $options = [])
    {
        if ($this->client() instanceof \Traffic\Http\HttpMockClient) {
            return $this->client()->download($uri, $destination, $headers, $options);
        }
        $request = new \GuzzleHttp\Psr7\Request("get", $uri, $headers);
        $dir = dirname($destination);
        if (!is_dir($dir)) {
            mkdir($dir, 511, true);
        }
        $options["sink"] = $destination;
        return $this->send($request, $options);
    }
    public static function settleLimit($promises, $concurrency)
    {
        $results = [];
        return GuzzleHttp\Promise\each_limit($promises, $concurrency, function ($value, $idx) {
            $results[$idx] = ["state" => \GuzzleHttp\Promise\PromiseInterface::FULFILLED, "value" => $value];
        }, function ($reason, $idx) {
            $results[$idx] = ["state" => \GuzzleHttp\Promise\PromiseInterface::REJECTED, "reason" => $reason];
        })->then(function () {
            ksort($results);
            return $results;
        });
    }
    private function _withDefaultHeaders($headers)
    {
        if (empty($headers)) {
            $headers = [];
        }
        $headers["UserAgent"] = DEFAULT_AGENT;
        return $headers;
    }
    private function _withOptionsDefaults($options = [])
    {
        if (empty($options[\GuzzleHttp\RequestOptions::VERIFY])) {
            $options[\GuzzleHttp\RequestOptions::VERIFY] = false;
        }
        if (!isset($options[\GuzzleHttp\RequestOptions::CONNECT_TIMEOUT])) {
            $options[\GuzzleHttp\RequestOptions::CONNECT_TIMEOUT] = 5;
        } else {
            if (empty($options[\GuzzleHttp\RequestOptions::CONNECT_TIMEOUT])) {
                unset($options[\GuzzleHttp\RequestOptions::CONNECT_TIMEOUT]);
            }
        }
        if (!isset($options[\GuzzleHttp\RequestOptions::TIMEOUT])) {
            $options[\GuzzleHttp\RequestOptions::TIMEOUT] = 5;
        } else {
            if (empty($options[\GuzzleHttp\RequestOptions::TIMEOUT])) {
                unset($options[\GuzzleHttp\RequestOptions::TIMEOUT]);
            }
        }
        return $options;
    }
}

?>