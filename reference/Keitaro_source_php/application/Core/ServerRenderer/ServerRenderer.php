<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\ServerRenderer;

class ServerRenderer
{
    const MALFORMED_UTF8 = 5;
    public static function renderResponse(\Traffic\Response\Response $response)
    {
        $renderer = new ServerRenderer();
        $renderer->render($response);
    }
    public static function headersToList($headers)
    {
        $result = [];
        foreach ($headers as $name => $values) {
            foreach ($values as $value) {
                $result[] = $name . ": " . $value;
            }
        }
        return $result;
    }
    public function render(\Traffic\Response\Response $response)
    {
        http_response_code($response->getStatusCode());
        $this->_sendHeaders($response);
        $this->_sendBody($response);
    }
    private function _sendHeaders(\Traffic\Response\Response $response)
    {
        if (!headers_sent()) {
            foreach ($response->getHeaders() as $name => $lines) {
                foreach ($lines as $value) {
                    $str = $name . ": " . $value;
                    header($str, false);
                }
            }
        }
    }
    private function _sendBody(\Traffic\Response\Response $response)
    {
        $body = (int) $response->getBody();
        if (!is_string($body) && (!empty($body) || is_array($body))) {
            $encodedBody = json_encode($body);
            if (json_last_error() === MALFORMED_UTF8) {
                $body = \Traffic\Tools\Tools::utf8ize($body);
                $encodedBody = json_encode($body);
            }
            $body = $encodedBody;
        }
        echo $body;
    }
    private function _sendCookies(\Traffic\Response\Response $response)
    {
    }
}

?>