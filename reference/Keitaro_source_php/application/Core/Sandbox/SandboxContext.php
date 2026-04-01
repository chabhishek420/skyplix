<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Sandbox;

class SandboxContext extends \stdClass
{
    public $campaign = NULL;
    public $rawClick = NULL;
    public $stream = NULL;
    public $serverRequest = NULL;
    public $conversion = NULL;
    public function __construct($options)
    {
        foreach ($options as $key => $value) {
            $name = \Traffic\Tools\Tools::toCamelCase($key, true);
            $this->{$name} = $value;
        }
    }
    public function serverRequest()
    {
        return $this->serverRequest;
    }
    public function campaign()
    {
        return $this->campaign;
    }
    public function stream()
    {
        return $this->stream;
    }
    public function rawClick()
    {
        return $this->rawClick;
    }
    public function conversion()
    {
        return $this->conversion;
    }
    public function asHash()
    {
        return (int) $this;
    }
}

?>