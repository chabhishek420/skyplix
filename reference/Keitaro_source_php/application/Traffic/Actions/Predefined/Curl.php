<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Actions\Predefined;

class Curl extends \Traffic\Actions\AbstractAction
{
    protected $_weight = 3;
    const TIMEOUT = 10;
    public function getType()
    {
        return TYPE_REDIRECT;
    }
    protected function _execute()
    {
        $url = trim($this->getActionPayload());
        if (\Traffic\Service\ConfigService::instance()->isDemo()) {
            return "Here would be content of " . $url;
        }
        $opts = ["localDomain" => $this->getServerRequest()->getHeaderLine(\Traffic\Request\ServerRequest::HEADER_HOST), "url" => $url, "user_agent" => $this->getRawClick()->getUserAgent(), "referrer" => $this->getPipelinePayload()->getActionOption("referrer")];
        $result = \Traffic\Actions\CurlService::instance()->request($opts);
        if (!empty($result["content_type"])) {
            $this->setContentType($result["content_type"]);
        }
        if (!empty($result["error"])) {
            $this->setContent("Oops! Something went wrong on the requesting page");
        } else {
            if (!empty($result["body"])) {
                $contentType = $result["content_type"];
                $body = $result["body"];
                if (strstr($contentType, "image") || strstr($contentType, "application/pdf")) {
                    $content = base64_encode($body);
                } else {
                    $content = $this->processMacros($result["body"]);
                }
                $content = \Traffic\Tools\Tools::utf8ize($content);
                $this->setContent($content);
                $this->setDestinationInfo("CURL: " . $url);
            }
        }
    }
}

?>