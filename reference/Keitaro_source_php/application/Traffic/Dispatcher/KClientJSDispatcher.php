<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Dispatcher;

class KClientJSDispatcher extends ClickApiDispatcher
{
    public function dispatch(\Traffic\Request\ServerRequest $request)
    {
        $new = false;
        if ($request->hasParam("_new") && $request->getParam("_new") == 1) {
            $new = true;
        }
        if (!$request->hasParam("sub_id") || $new) {
            return $this->_getCodeWithOutSubId($request);
        }
        return $this->_getCodeWithSubId($request);
    }
    private function _getCodeWithOutSubId(\Traffic\Request\ServerRequest $request)
    {
        $this->setVersion(3);
        $response = self::dispatch($request);
        if (empty($response)) {
            throw new \Exception("Empty response");
        }
        if ($response->getStatusCode() !== 200) {
            return $response;
        }
        $name = $request->getParam("name");
        $codeGenerate = new \Component\CampaignIntegration\KClientJS\CodeGenerator();
        $subId = $this->getPipelinePayload()->getRawClick()->getSubId();
        $token = $this->getPipelinePayload()->getRawClick()->getToken();
        $content = json_decode($response->getBody()->getContents());
        $jsClient = $codeGenerate->generateClientCode($request, $subId, $token, $content, $name);
        if (!empty($content->cookies)) {
            list($request, $response) = \Traffic\Cookies\Service\CookiesService::instance()->setRaws($request, $response, (int) $content->cookies, time() + $content->cookies_ttl * 24 * 60 * 60, $request->getUri()->getPath());
        }
        return $response->withHeader(\Traffic\Response\ContentType::HEADER, \Traffic\Response\ContentType::JS)->withBody(\Traffic\Response\ResponseFactory::safeBody($jsClient));
    }
    private function _getCodeWithSubId(\Traffic\Request\ServerRequest $request)
    {
        $subId = $request->getParam("sub_id");
        $token = $request->getParam("token");
        $name = $request->getParam("name");
        $codeGenerate = new \Component\CampaignIntegration\KClientJS\CodeGenerator();
        $jsClient = $codeGenerate->generateClientCode($request, $subId, $token, "", $name);
        return \Traffic\Response\Response::buildJson()->withHeader(\Traffic\Response\ContentType::HEADER, \Traffic\Response\ContentType::JS)->withBody(\Traffic\Response\ResponseFactory::safeBody($jsClient));
    }
}

?>