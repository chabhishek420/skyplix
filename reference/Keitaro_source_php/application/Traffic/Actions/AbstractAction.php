<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Actions;

abstract class AbstractAction
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
    const TYPE_HIDDEN = "hidden";
    const UPLOAD = "upload";
    public function getResponse()
    {
        return $this->getPipelinePayload()->getResponse();
    }
    public function setResponse(\Traffic\Response\Response $response)
    {
        $this->getPipelinePayload()->setResponse($response);
    }
    public function getType()
    {
        return TYPE_REDIRECT;
    }
    public function run()
    {
        $this->_execute();
        return $this->getPipelinePayload();
    }
    public function setPipelinePayload(\Traffic\Pipeline\Payload $payload)
    {
        $this->_pipelinePayload = $payload;
    }
    public function getPipelinePayload()
    {
        return $this->_pipelinePayload;
    }
    public function getRawActionPayload()
    {
        return $this->getPipelinePayload()->getActionPayload();
    }
    public function getActionPayload()
    {
        return $this->processMacros($this->getRawActionPayload());
    }
    public function getActionOptions()
    {
        return $this->getPipelinePayload()->getActionOptions();
    }
    protected function _execute()
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
    public function getRawClick()
    {
        return $this->getPipelinePayload()->getRawClick();
    }
    public function getCampaign()
    {
        return $this->getPipelinePayload()->getCampaign();
    }
    public function getStream()
    {
        return $this->getPipelinePayload()->getStream();
    }
    public function getLanding()
    {
        return $this->getPipelinePayload()->getLanding();
    }
    public function getOffer()
    {
        return $this->getPipelinePayload()->getOffer();
    }
    public function setContentType($value)
    {
        $this->header(\Traffic\Response\ContentType::HEADER, $value);
    }
    public function addHeader($headerString)
    {
        if (!strstr($headerString, ":")) {
            throw new \Exception("Header must contain ':'");
        }
        $header = \Traffic\Response\ResponseFactory::parseHeaderString($headerString);
        $this->header(trim($header["name"]), trim($header["value"]));
    }
    public function header($name, $value)
    {
        $this->setResponse($this->getResponse()->withHeader($name, $value));
    }
    public function setStatus($status)
    {
        $this->setResponse($this->getResponse()->withStatus($status));
    }
    public function redirect($url)
    {
        $this->header("Location", $url);
        if ($this->getResponse()->getStatusCode() === 200) {
            $this->setStatus(302);
        }
    }
    public function setDestinationInfo($value)
    {
        $this->getRawClick()->setDestination($value);
    }
    public function getServerRequest()
    {
        return $this->getPipelinePayload()->getServerRequest();
    }
    protected function _executeInContext()
    {
        $from = NULL;
        foreach ($this->getServerRequest()->getQueryParams() as $paramName => $paramValue) {
            if (strpos($paramName, "frm") === 0) {
                $from = $paramValue;
                if (strpos($from, "script") === 0) {
                    if (strpos($from, "frame") === 0) {
                        $this->_executeDefault();
                    } else {
                        $this->_executeForFrame();
                    }
                } else {
                    $this->_executeForScript();
                }
            }
        }
    }
    protected function _executeForScript()
    {
        $this->addHeader("Content-type: application/javascript; charset=utf-8");
        $error = \Core\Locale\LocaleService::t("stream_actions.action_incompatible");
        $this->setContent("window.console && console.error(\"" . $error . "\");");
    }
    protected function _executeForFrame()
    {
        $error = \Core\Locale\LocaleService::t("stream_actions.action_incompatible");
        $this->setContent("<script>window.console && console.error(\"" . $error . "\");</script>");
    }
    protected function _executeDefault()
    {
        $this->setContent("Error! Method \"_executeDefault()\" must be implemented in \"" . get_called_class() . "\"");
    }
    protected function processMacros($body)
    {
        $pageContext = new \Core\Sandbox\SandboxContext(["server_request" => $this->getServerRequest(), "campaign" => $this->getCampaign(), "stream" => $this->getStream(), "raw_click" => $this->getRawClick()]);
        return \Traffic\Macros\MacrosProcessor::process($pageContext, $body);
    }
}

?>