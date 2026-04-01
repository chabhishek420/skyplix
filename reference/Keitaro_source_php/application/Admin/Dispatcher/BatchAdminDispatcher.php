<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Admin\Dispatcher;

class BatchAdminDispatcher implements \Core\Dispatcher\DispatcherInterface
{
    const PARAMS_PARAM = "params";
    const POST_DATA_PARAM = "postData";
    public function dispatch(\Traffic\Request\ServerRequest $request)
    {
        $response = \Traffic\Response\Response::build(["disable_cache" => true]);
        $subRequests = $this->_getSubRequests($request);
        $result = [];
        $virtualContext = new \Admin\Context\AdminContext();
        foreach ($subRequests as $key => $subRequest) {
            $subResponse = \Traffic\Response\Response::build(["headers" => [\Traffic\Response\ContentType::HEADER => \Traffic\Response\ContentType::JSON]]);
            try {
                $dispatcher = new AdminDispatcher($subResponse);
                $subResponse = $dispatcher->dispatch($subRequest);
            } catch (\Exception $e) {
                $subResponse = $virtualContext->handleException($e, $subRequest);
                $result[] = ["body" => json_decode($subResponse->getBody(), true), "headers" => \Core\ServerRenderer\ServerRenderer::headersToList($subResponse->getHeaders()), "statusCode" => $subResponse->getStatusCode()];
            }
        }
        $response = $response->withBody(\Traffic\Response\ResponseFactory::safeBody($result))->withHeader(\Traffic\Response\ContentType::HEADER, \Traffic\Response\ContentType::JSON);
        return $response;
    }
    private function _getSubRequests(\Traffic\Request\ServerRequest $request)
    {
        $requests = [];
        foreach ($request->getParsedBody() as $params) {
            $subRequest = \Traffic\Request\ServerRequest::build();
            if (!empty($params[PARAMS_PARAM])) {
                $subRequest = $subRequest->withQueryParams($params[PARAMS_PARAM]);
            } else {
                $subRequest = $subRequest->withQueryParams($params);
            }
            if (!empty($params[POST_DATA_PARAM])) {
                if (is_string($params[POST_DATA_PARAM])) {
                    $postParams = json_decode($params[POST_DATA_PARAM], true);
                } else {
                    $postParams = $params[POST_DATA_PARAM];
                }
                $subRequest = $subRequest->withParsedBody($postParams);
            }
            $requests[] = $subRequest;
        }
        return $requests;
    }
}

?>