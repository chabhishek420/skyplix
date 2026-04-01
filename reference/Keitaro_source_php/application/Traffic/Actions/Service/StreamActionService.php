<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Actions\Service;

class StreamActionService extends \Traffic\Service\AbstractService
{
    const DESTINATION_LIMIT = 50;
    const TRUNCATED = "... (truncated)";
    public function buildDestination(\Traffic\Request\ServerRequest $serverRequest, \Traffic\Model\Campaign $campaign, $url, \Traffic\Model\BaseStream $stream = NULL, \Traffic\RawClick $rawClick, \Traffic\Model\Conversion $conversion = NULL)
    {
        if (empty($stream)) {
            $stream = new \Traffic\Model\BaseStream();
        }
        $action = \Traffic\Actions\Repository\StreamActionRepository::instance()->getNewActionInstance($stream->getActionType());
        if (method_exists($action, "destination")) {
            $destination = call_user_func_array([$action, "destination"], [$stream, $rawClick]);
        } else {
            $pageContext = new \Core\Sandbox\SandboxContext(["server_request" => $serverRequest, "stream" => $stream, "raw_click" => $rawClick, "conversion" => $conversion, "campaign" => $campaign]);
            $destination = \Traffic\Macros\MacrosProcessor::process($pageContext, $url);
        }
        return $destination;
    }
    public function truncateDestination($text)
    {
        if (DESTINATION_LIMIT < mb_strlen($text)) {
            $text = mb_substr($text, 0, DESTINATION_LIMIT) . TRUNCATED;
        }
        return $text;
    }
}

?>