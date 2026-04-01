<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Macros\Predefined;

class Debug extends \Traffic\Macros\AbstractClickMacro
{
    public function process(\Traffic\Model\BaseStream $stream, \Traffic\RawClick $rawClick)
    {
        $uri = $this->getServerRequest()->getUri();
        $output = ["headers" => $this->getServerRequest()->getHeaders(), "server_params" => $this->getServerRequest()->getServerParams(), "click" => $rawClick->serialize(), "method" => $this->getServerRequest()->getMethod(), "uri" => ["scheme" => $uri->getScheme(), "host" => $uri->getHost(), "path" => $uri->getPath(), "port" => $uri->getPort(), "query" => $uri->getQuery(), "user_info" => $uri->getUserInfo(), "fragment" => $uri->getFragment()], "url" => (int) $uri];
        return json_encode($output, JSON_PRETTY_PRINT);
    }
}

?>