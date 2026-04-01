<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Admin\Controller;

class BaseController
{
    use Helper\ControllerHelper;
    use Helper\AclHelper;
    private $_response = NULL;
    private $_serverRequest = NULL;
    public function init()
    {
        if (!$this->getServerRequest()) {
            throw new \Exception("serverRequest is not set");
        }
        if (!$this->getResponse()) {
            throw new \Exception("response is not set");
        }
    }
    public function setResponse(\Traffic\Response\Response $response)
    {
        $this->_response = $response;
    }
    public function setServerRequest(\Traffic\Request\ServerRequest $request)
    {
        $this->_serverRequest = $request;
    }
    public function getServerRequest()
    {
        if (empty($this->_serverRequest)) {
            throw new \Exception("No Request object");
        }
        return $this->_serverRequest;
    }
    public function getResponse()
    {
        return $this->_response;
    }
    public function getParam($name)
    {
        return $this->getServerRequest()->getParam($name);
    }
    public function getPostParam($name)
    {
        return $this->getServerRequest()->getParsedBodyParam($name);
    }
    public function getPostParams()
    {
        return $this->getServerRequest()->getParsedBody();
    }
    public function isPost()
    {
        return $this->getServerRequest()->isPost();
    }
    public function redirect($url)
    {
        $this->header("Location", $url);
    }
    public function header($name, $value)
    {
        $this->setResponse($this->getResponse()->withHeader($name, $value));
    }
    protected function serialize($payload, $serializer)
    {
        return \Core\Json\SerializerFactory::serialize($payload, $serializer);
    }
    protected function _sendJson($json = NULL)
    {
        $response = $this->getResponse()->withHeader(\Traffic\Response\ContentType::HEADER, \Traffic\Response\ContentType::JSON)->withBody(\Traffic\Response\ResponseFactory::safeBody($json));
        $this->setResponse($response);
    }
    protected function status($status)
    {
        $this->setResponse($this->getResponse()->withStatus($status));
    }
    public function isAjax()
    {
        return $this->getServerRequest()->isAjax();
    }
    public function getCommonContextParams()
    {
        return ["serverRequest" => $this->getServerRequest(), "isProduction" => \Core\Application\Application::instance()->isProduction(), "isDevelopment" => \Core\Application\Application::instance()->isDevelopment(), "revision" => \Core\Application\Application::instance()->revision(), "assetsHost" => \Component\System\Service\AssetsService::instance()->url(\Core\Application\Application::instance()->isDevelopment(), $this->getServerRequest()), "signedIn" => \Component\Users\Service\CurrentUserService::instance()->signedIn()];
    }
    public function renderView($filePath, $contextParams = [], $layoutPath = NULL)
    {
        $contextParams = array_merge($this->getCommonContextParams(), $contextParams);
        $sandboxSubject = \Core\Sandbox\SandboxSubject::fromFile($filePath, $layoutPath);
        $sandboxContext = new \Core\Sandbox\SandboxContext($contextParams);
        return (int) (new \Core\Sandbox\UnsafeSandbox\UnsafeSandbox())->execute($sandboxSubject, $sandboxContext)->getBody();
    }
}

?>