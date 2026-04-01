<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Admin\Context;

class AdminApiContext extends AdminContext implements \Core\Context\ContextInterface
{
    public function bootstrap()
    {
        \Core\Application\Bootstrap::initAdminContext();
    }
    public function modifyRequest(\Traffic\Request\ServerRequest $serverRequest)
    {
        $router = new \Admin\AdminApi\AdminApiRouter($serverRequest->getParam(\Core\Router\TrafficRouter::PARAM_VERSION));
        $serverRequest = $router->match($serverRequest);
        $serverRequest = $serverRequest->withHeader(\Traffic\Request\ServerRequest::HEADER_X_REQUESTED_WITH, \Traffic\Request\ServerRequest::XMLHTTPREQUEST);
        return $serverRequest;
    }
    public function dispatcher(\Traffic\Request\ServerRequest $serverRequest)
    {
        if (!\Core\Application\FeatureService::instance()->hasAdminApiFeature()) {
            $msg = "Admin API is available only in Business editions";
            return new \Core\Dispatcher\SimpleDispatcher($this->_errorResponse(\Traffic\Response\StatusCode::PAYMENT_REQUIRED, $msg));
        }
        if (!$this->_authorize($serverRequest)) {
            $msg = "Invalid API key";
            return new \Core\Dispatcher\SimpleDispatcher($this->_errorResponse(\Traffic\Response\StatusCode::UNAUTHORIZED, $msg));
        }
        if ($serverRequest instanceof NoRouteRequest) {
            $msg = "URL '" . $serverRequest->getMethod() . " " . $serverRequest->getUri() . "' does not match any route";
            return new \Core\Dispatcher\SimpleDispatcher($this->_errorResponse(\Traffic\Response\StatusCode::BAD_REQUEST, $msg));
        }
        $response = \Traffic\Response\Response::build(["headers" => [\Traffic\Response\ContentType::HEADER => \Traffic\Response\ContentType::JSON]]);
        return new \Admin\Dispatcher\AdminDispatcher($response);
    }
    public function handleException(\Exception $e, \Traffic\Request\ServerRequest $serverRequest)
    {
        if ($e instanceof \Core\Application\Exception\LicenseError) {
            $response = self::handleException($e, $serverRequest);
        } else {
            $response = \Traffic\Response\Response::buildJson(["status" => \Traffic\Response\StatusCode::PAYMENT_REQUIRED, "body" => ["error" => $e->getMessage()]]);
        }
        return $response;
    }
    private function _authorize(\Traffic\Request\ServerRequest $request)
    {
        $apiKey = $request->getParam("api_key");
        if (empty($apiKey)) {
            $apiKey = $request->getHeaderLine("Api-Key");
        }
        if (empty($apiKey)) {
            return false;
        }
        $key = \Component\Users\Repository\ApiKeysRepository::instance()->findFirst("`key` = " . \Core\Db\Db::quote(trim($apiKey)));
        if (empty($key)) {
            return false;
        }
        $user = \Component\Users\Repository\UserRepository::instance()->find($key->get("user_id"));
        \Component\Users\Service\CurrentUserService::instance()->set($user);
        return true;
    }
    private function _errorResponse($status, $error, $type = \Traffic\Response\ContentType::JSON)
    {
        if ($type === \Traffic\Response\ContentType::JSON) {
            $error = ["error" => $error];
        }
        return \Traffic\Response\Response::build()->withStatus($status)->withHeader(\Traffic\Response\ContentType::HEADER, $type)->withBody(\Traffic\Response\ResponseFactory::safeBody($error));
    }
}

?>