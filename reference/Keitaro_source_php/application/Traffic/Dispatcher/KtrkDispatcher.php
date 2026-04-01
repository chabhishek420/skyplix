<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Dispatcher;

class KtrkDispatcher extends ClickApiDispatcher
{
    public function dispatch(\Traffic\Request\ServerRequest $request)
    {
        $response = self::dispatch($request);
        if (empty($response)) {
            throw new \Exception("Empty response");
        }
        if ($response->getStatusCode() !== 200) {
            return $response;
        }
        $json = json_encode(["sub_id" => $this->getPipelinePayload()->getRawClick()->getSubId(), "token" => $this->getPipelinePayload()->getRawClick()->getToken()]);
        $response = $response->withHeader(\Traffic\Response\ContentType::HEADER, \Traffic\Response\ContentType::JS)->withBody(\Traffic\Response\ResponseFactory::safeBody("KTracking.response(" . $json . ");"));
        return $response;
    }
}

?>