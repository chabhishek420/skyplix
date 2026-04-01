<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Dispatcher;

class PostbackDispatcher implements \Core\Dispatcher\DispatcherInterface
{
    const PIXEL = "R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw==";
    const JSONP = "jsonp";
    const GIF = "gif";
    public function dispatch(\Traffic\Request\ServerRequest $request)
    {
        $response = \Traffic\Response\Response::build(["disable_cache" => true]);
        $key = $this->_findKey($request);
        $request = $this->convertCustomHeaders($request);
        $msg = "Received postback " . (int) $request->getUri();
        if ($request->isPost()) {
            $msg .= ", post params: " . json_encode($request->getParsedBody(), JSON_PARTIAL_OUTPUT_ON_ERROR);
        }
        $this->log($msg);
        if (\Component\AffiliateNetworks\Repository\NetworkTemplatesRepository::instance()->getSecret() !== $key) {
            $message = "Incorrect postback code (" . $key . " in " . (int) $request->getUri() . ")";
            $this->log($message);
            return $this->_updateBody($response, $message);
        }
        try {
            $postback = \Component\Postback\Postback::buildFromParams($request->getAllParams());
            if (!$postback->getSubId()) {
                $subId = \Traffic\Cookies\Service\CookiesService::instance()->getRaw($request, \Traffic\Cookies\Service\CookiesService::SUB_ID_KEY);
                if (!empty($subId)) {
                    $postback->setSubId($subId);
                }
            }
            if (!$postback->getSubId()) {
                $this->log("Incorrect subid \"" . $postback->getSubId() . "\". Postback ignored.");
                $message = "Incorrect subid";
            } else {
                \Component\Postback\DelayedCommand\ProcessPostbackCommand::processPostback($postback);
                $this->log("Using \"" . $postback->getSubId() . "\" as subid. Postback added to queue.");
                $message = "Success";
            }
        } catch (\Component\Postback\PostbackError $e) {
            $this->log($e->getMessage());
            $message = $e->getMessage();
        } catch (\Core\Exceptions\NotFoundError $e) {
            $this->log($e->getMessage());
            $message = $e->getMessage();
            return $this->_updateBody($response, $message, $request->getParam("return"));
        }
    }
    private function _updateBody(\Traffic\Response\Response $response, $message, $return = NULL)
    {
        switch ($return) {
            case JSONP:
                $code = "KTracking && KTracking.response(\"" . htmlspecialchars($message, ENT_QUOTES) . "\")";
                $response = $response->withHeader(\Traffic\Response\ContentType::HEADER, \Traffic\Response\ContentType::JS)->withBody(\Traffic\Response\ResponseFactory::safeBody($code));
                break;
            case GIF:
                $response = $response->withHeader(\Traffic\Response\ContentType::HEADER, \Traffic\Response\ContentType::GIF)->withBody(\Traffic\Response\ResponseFactory::safeBody(base64_decode(PIXEL)));
                break;
            default:
                $response = $response->withBody(\Traffic\Response\ResponseFactory::safeBody(htmlentities($return)));
                if (empty($return)) {
                    $response = $response->withBody(\Traffic\Response\ResponseFactory::safeBody($message));
                }
                return $response;
        }
    }
    public function log($msg)
    {
        \Core\Logging\Service\PostbackLoggerService::instance()->log($msg);
    }
    public function convertCustomHeaders(\Traffic\Request\ServerRequest $request)
    {
        $postBody = (int) $request->getBody();
        if (is_string($postBody) && strstr($postBody, "partnerNotify")) {
            $params = $this->_parseMosbillRequest($postBody);
            $request = $request->withParsedBody($params);
        }
        return $request;
    }
    private function _parseMosbillRequest($body)
    {
        $params = [];
        $body = preg_replace("/<\\?xml.*?>/", "", $body);
        $body = preg_replace("/<partnerNotify>/", "", $body);
        if (preg_match_all("/<(.*?)>(.*?)<\\/.*?>/si", $body, $result)) {
            for ($i = 0; $i < count($result[0]); $i++) {
                $params[$result[1][$i]] = $result[2][$i];
            }
        }
        return $params;
    }
    private function _findKey(\Traffic\Request\ServerRequest $request)
    {
        $key = NULL;
        if ($request->getParam(\Core\Router\TrafficRouter::PARAM_KEY)) {
            $key = $request->getParam(\Core\Router\TrafficRouter::PARAM_KEY);
        }
        $params = $request->getQueryParams();
        if (!empty($params[0])) {
            $key = $params[0];
        }
        return $key;
    }
}

?>