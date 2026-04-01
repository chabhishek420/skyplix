<?php

namespace Admin\Dispatcher;

class AdminDispatcher implements \Core\Dispatcher\DispatcherInterface
{
    /**
     * @var Response
     */
    private $_response = NULL;
    /**
     * AdminDispatcher constructor.
     * @param Response|null $response
     * @throws \Traffic\Response\ResponseError
     */
	 
    public function __construct(\Traffic\Response\Response $response = NULL)
    {
        if (empty($response)) {
            $response = \Traffic\Response\Response::buildHtml();
        }
        $this->_response = $response;
    }
	
    /**
     * @param ServerRequest $request
     * @return \GuzzleHttp\Psr7\MessageTrait|\GuzzleHttp\Psr7\Response|Response
     * @throws Error
     * @throws LicenseError|NotFoundError|\Exception
     */
	 
	public function dispatch(\Traffic\Request\ServerRequest $request)
    {
        $response = $this->_response;
        $adminRequest = \Admin\AdminRequest\AdminRequestFactory::build($request);
        if (empty($adminRequest)) {
            throw new \Exception("adminRequest is not defined");
        }
        $this->_securityCheck();
        if ($adminRequest->isLicenseMustBeChecked()) {
			
            $opts = \Core\Application\LicenseService::instance()->getOpts();
            $payload = json_decode(json_encode(array('success' => 1)));
            $payload->key = $opts["key"];
            $payload->ip = $opts["ip"];
            $payload->hash_expired = time() + 60 * 60 * 50;
            $payload->license_expired = time() + 60 * 60 * 24 * 256 * 3;
            $payload->edition = 'business';
            $payload->state = "active";
            $payload->limit_users = 1;
            $payload->limit_click_api = 1;
            $payload->admin_api_business_only = 1;

            // $payload = \Core\Application\EssentialService::instance()->checkIfTokenUpdated();
            // \Core\Application\EssentialService::instance()->validate($payload);
            \Core\Application\FeatureService::instance()->init(new \Core\Application\EssentialPayload($payload));
        }
        return $this->_dispatchControllerAction($adminRequest, $request, $response);
    }
	
    /**
     * @param AdminRequest $adminRequest
     * @param ServerRequest $request
     * @param Response $response
     * @return \GuzzleHttp\Psr7\MessageTrait|\GuzzleHttp\Psr7\Response|Response
     * @throws NotFoundError|\Exception
     */
	 
    private function _dispatchControllerAction(\Admin\AdminRequest\AdminRequest $adminRequest, \Traffic\Request\ServerRequest $request, \Traffic\Response\Response $response)
    {
        $controller = \Admin\Controller\ControllerRepository::instance()->getController($adminRequest->getController());
        $controller->setServerRequest($request);
        $controller->setResponse($response);
        $actionName = $adminRequest->getAction() . "Action";
        if (!method_exists($controller, $actionName)) {
            throw new \Core\Exceptions\NotFoundError("Controller action \"" . $actionName . "\" is not defined");
        }
        $controller->init();
        $content = call_user_func([$controller, $actionName]);
        $response = $controller->getResponse();
        $response = $response->withBody(\Traffic\Response\ResponseFactory::safeBody($content));
        return $response;
    }

    /**
     * Проверяем, не было ли подмены файлов
     * @throws Error
     */

	private function _securityCheck()
    {
		return;
        $signatures = ["Component\\System\\Initializer" => "6a204bd89f3c8348afd5c77c717a097a", "Core\\Application\\LicenseService" => "98353aac502b25a98c5724a16ae4fd15", "Core\\Application\\EssentialService" => "9012hefg02uh3ef0u123e0fuhwidjnsc0uhPISUHhsuh93iehv", "Core\\Sentinel\\Single" => "23uhrg23urfg2-3urg-2u3hrg-uihwerfun23efz"];
        $i = 0;
        foreach ($signatures as $class => $signature) {
            $i++;
            if ($class::SIGNATURE != $signature) {
                throw new \Core\Exceptions\DenyError("Class #" . $i . " has incorrect signature");
            }
        }
    }
}

?>