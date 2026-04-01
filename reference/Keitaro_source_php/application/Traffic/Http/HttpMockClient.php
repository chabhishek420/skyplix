<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Http;

class HttpMockClient implements \GuzzleHttp\ClientInterface
{
    private $_stubs = [];
    private $_requested = [];
    public function stub($request, $response)
    {
        if (is_string($request)) {
            $request = new \GuzzleHttp\Psr7\Request("GET", $request);
        }
        if (is_string($response)) {
            $response = new \GuzzleHttp\Psr7\Response(200, [], $response);
        }
        if (is_integer($response)) {
            $response = new \GuzzleHttp\Psr7\Response($response, [], "");
        }
        array_unshift($this->_stubs, [$request, $response]);
    }
    public function stubAll($response)
    {
        if (is_string($response)) {
            $response = new \GuzzleHttp\Psr7\Response(200, [], $response);
        }
        $this->_stubs[] = [NULL, $response];
    }
    public function stubHttpThrowException($request, $status)
    {
        if (is_string($request)) {
            $request = new \GuzzleHttp\Psr7\Request("GET", $request);
        }
        $response = new \GuzzleHttp\Psr7\Response($status, []);
        $error = new \GuzzleHttp\Exception\RequestException("stub error '" . $status . "'", $request, $response);
        $this->stub($request, $error);
    }
    public function httpRequestHasBeenMade($url)
    {
        return !empty($this->_requested[$url]);
    }
    public function flushStubs()
    {
        $this->_requested = [];
        $this->_stubs = [];
    }
    public function send(\Psr\Http\Message\RequestInterface $request, $options = [])
    {
        $current = [];
        foreach ($this->_stubs as $row) {
            list($_request, $_response) = $row;
            if (is_null($_request)) {
                $response = $_response;
            } else {
                if ((int) $_request->getUri() == (int) $request->getUri() && $_request->getMethod() == $request->getMethod()) {
                    $response = $_response;
                } else {
                    $current[] = $_request->getMethod() . " " . $_request->getUri();
                }
            }
            if (empty($response)) {
                $message = "External requests are disabled for testing. Please make a stub:" . PHP_EOL . "  \$this->stubHttp('" . $request->getUri() . "', 'body')" . PHP_EOL . "OR instance of Request and Response" . PHP_EOL . "  \$this->stubHttp(Request \$request, Response \$response)" . PHP_EOL . PHP_EOL . "Current: " . PHP_EOL . "- " . implode(PHP_EOL . "- ", $current);
                throw new Error\CatchedRequestException($message);
            }
            $this->_requested[(int) $request->getUri()] = true;
            if ($response instanceof \GuzzleHttp\Exception\RequestException) {
                throw $response;
            }
            return $response;
        }
    }
    public function download($uri, $destination, $headers = [], $options = [])
    {
        $request = new \GuzzleHttp\Psr7\Request("get", $uri, $headers);
        $response = $this->send($request, $options);
        $dir = dirname($destination);
        if (!is_dir($dir)) {
            mkdir($dir, 511, true);
        }
        file_put_contents($destination, $response->getBody());
    }
    public function sendAsync(\Psr\Http\Message\RequestInterface $request, $options = [])
    {
        $response = $this->send($request, $options);
        $promise = new \GuzzleHttp\Promise\Promise(function () {
            $promise->resolve($response);
        });
        $promise->then(function ($response) {
            return $response;
        });
        return $promise;
    }
    public function requestAsync($method, $uri, $options = [])
    {
        throw new \Exception("Not Implemented");
    }
    public function getConfig($option = NULL)
    {
        throw new \Exception("Not Implemented");
    }
    public function request($method, $uri, $options = [])
    {
        throw new \Exception("Not Implemented");
    }
}

?>