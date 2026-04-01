<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Dispatcher;

class GatewayRedirectDispatcher implements \Core\Dispatcher\DispatcherInterface
{
    public function dispatch(\Traffic\Request\ServerRequest $request)
    {
        $response = \Traffic\Response\Response::build(["disable_cache" => true]);
        $token = $request->getParam("token");
        $ua = $request->getHeaderLine(\Traffic\Request\ServerRequest::HEADER_USER_AGENT);
        try {
            $decoded = \Firebase\JWT\JWT::decode($token, \Traffic\LpToken\Service\LpTokenService::generateUserKey($ua), ["HS256"]);
            $code = $this->_code($decoded->url);
            return $response->withBody(\Traffic\Response\ResponseFactory::safeBody($code));
        } catch (\UnexpectedValueException $e) {
            return \Traffic\Response\ResponseFactory::build(["status" => 400, "body" => "Bad Request"]);
        } catch (\DomainException $e) {
            return \Traffic\Response\ResponseFactory::build(["status" => 400, "body" => "Bad Request"]);
        }
    }
    private function _code($url)
    {
        return "<html>\n            <head>\n                <meta http-equiv=\"REFRESH\" content=\"1; URL='" . $url . "'\">\n                <script type=\"application/javascript\">window.location = \"" . $url . "\";</script>\n            </head>\n            </html>";
    }
}

?>