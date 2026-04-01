<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamActions;

class AbstractAction
{
    protected $_name = NULL;
    protected $_weight = NULL;
    protected $_response = NULL;
    private $_pipelinePayload = NULL;
    const CAMPAIGNS = "campaigns";
    const STREAMS = "streams";
    const URL = "url";
    const TEXT = "text";
    const NOTHING = "nothing";
    const TYPE_REDIRECT = "redirect";
    const TYPE_OTHER = "other";
    public function getResponse()
    {
        return $this->_response;
    }
    public function setResponse(\Traffic\Response\Response $response)
    {
        $this->_response = $response;
    }
    public function setPipelinePayload(\Traffic\Pipeline\Payload $payload)
    {
        $this->_pipelinePayload = $payload;
    }
    public function getPipelinePayload()
    {
        return $this->_pipelinePayload;
    }
    public function getType()
    {
        return TYPE_REDIRECT;
    }
    public function run(\Component\Streams\Model\BaseStream $stream, \Component\Clicks\Model\RawClick $rawClick)
    {
        $this->_execute($stream, $rawClick);
        return $this->getResponse();
    }
    protected function _execute(\Component\Streams\Model\BaseStream $stream, \Component\Clicks\Model\RawClick $rawClick)
    {
        $className = get_called_class();
        throw new \Core\Application\Exception\Error("Method '_execute' must be implemented in " . $className);
    }
    public function setInfo($name, $weight)
    {
        $this->_name = $name;
        $this->_weight = $weight;
    }
    public function getField()
    {
        return URL;
    }
    public function getName()
    {
        return $this->_name;
    }
    public function getWeight()
    {
        return $this->_weight;
    }
    public function setContent($content)
    {
        $this->setResponse($this->getResponse()->withBody(\Traffic\Response\ResponseFactory::safeBody($content)));
    }
    public function getCharset()
    {
    }
    public function setCharset($charset)
    {
    }
    public function setContentType($value)
    {
        $this->setResponse($this->getResponse()->withHeader(\Traffic\Response\ContentType::HEADER, $value));
    }
    public function addHeader($header)
    {
        $pos = strpos($header, ":");
        $value = substr($header, $pos + 1);
        $name = substr($header, 0, $pos);
        $this->setResponse($this->getResponse()->withHeader(trim($name), trim($value)));
    }
    public function redirect($url)
    {
        $newResponse = $this->getResponse()->withHeader("Location", $url)->withStatus(\Traffic\Response\StatusCode::MOVED_TEMPORARILY);
        $this->setResponse($newResponse);
    }
    public function getServerRequest()
    {
        return $this->getPipelinePayload()->getServerRequest();
    }
    protected function _executeInContext(\Component\Streams\Model\BaseStream $stream, \Component\Clicks\Model\RawClick $visitor)
    {
        $from = NULL;
        foreach ($this->getServerRequest()->getQueryParams() as $paramName => $paramValue) {
            if (strpos($paramName, "frm") === 0) {
                $from = $paramValue;
                if (strpos($from, "script") === 0) {
                    if (strpos($from, "frame") === 0) {
                        $this->_executeDefault($stream, $visitor);
                    } else {
                        $this->_executeForFrame($stream, $visitor);
                    }
                } else {
                    $this->_executeForScript($stream, $visitor);
                }
            }
        }
    }
    protected function _executeForScript(\Component\Streams\Model\BaseStream $stream, \Component\Clicks\Model\RawClick $rawClick)
    {
        $this->addHeader("Content-type: application/javascript; charset=utf-8");
        $error = \Core\Locale\LocaleService::t("stream_actions.action_incompatible");
        $this->setContent("window.console && console.error(\"" . $error . "\");");
    }
    protected function _executeForFrame(\Component\Streams\Model\BaseStream $stream, \Component\Clicks\Model\RawClick $rawClick)
    {
        $error = \Core\Locale\LocaleService::t("stream_actions.action_incompatible");
        $this->setContent("<script>window.console && console.error(\"" . $error . "\");</script>");
    }
    protected function _executeDefault(\Component\Streams\Model\BaseStream $stream, \Component\Clicks\Model\RawClick $rawClick)
    {
        $this->setContent("Error! Method \"_executeDefault()\" must be implemented in \"" . get_called_class() . "\"");
    }
}

?>