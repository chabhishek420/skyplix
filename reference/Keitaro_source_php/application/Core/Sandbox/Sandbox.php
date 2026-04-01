<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Sandbox;

class Sandbox implements SandboxInterface
{
    private $_cgiExecutor = NULL;
    private $_cachedSettings = NULL;
    const SIMPLE_ENGINE = "simple";
    const CGI_ENGINE = "cgi";
    const FCGI_ENGINE = "fcgi";
    const LOCATION = "Location";
    const X_POWERED_BY = "X-Powered-By";
    const STATUS = "Status";
    const HEADERS_SEPARATOR = "\r\n\r\n";
    public function __construct(CgiExecutor\CgiExecutorInterface $cgiExecutor)
    {
        $this->_cgiExecutor = $cgiExecutor;
        $this->_cachedSettings = \Traffic\Repository\CachedSettingsRepository::instance();
    }
    private function _parseOutputToResponse($output, SandboxSubject $subject)
    {
        $headerEnd = strpos($output, HEADERS_SEPARATOR);
        if ($headerEnd === false) {
            \Traffic\Logging\Service\LoggerService::instance()->error("Local landing execution response doesn't contain header separator");
            return \Traffic\Response\Response::build(["status" => 500, "body" => "Internal error"]);
        }
        $headers = substr($output, 0, $headerEnd);
        $output = substr($output, $headerEnd + 4);
        $response = \Traffic\Response\Response::build(["status" => 200, "body" => $output]);
        return $this->_applyHeaders($response, $subject, $headers);
    }
    private function _applyHeaders(\Traffic\Response\Response $response, SandboxSubject $subject, $headers)
    {
        $resultHeaders = [];
        foreach (explode("\n", $headers) as $initialHeader) {
            $header = \Traffic\Response\ResponseFactory::parseHeaderString($initialHeader);
            $headerName = $header["name"];
            $headerValue = $header["value"];
            switch ($headerName) {
                case X_POWERED_BY:
                case STATUS:
                    $response = $response->withStatus((int) $headerValue);
                    break;
                case LOCATION:
                    if (!strstr($headerValue, "://")) {
                        $headerValue = $subject->localPath() . "/" . $headerValue;
                    }
                    $resultHeaders[$headerName][] = $headerValue;
                    break;
                default:
                    $resultHeaders[$headerName][] = $headerValue;
            }
        }
        foreach ($resultHeaders as $headerName => $headerValue) {
            $response = $response->withHeader($headerName, $headerValue);
        }
        return $response;
    }
    private function _getExecParams(SandboxSubject $subject, SandboxContext $sandboxContext)
    {
        if (!$subject->filePath()) {
            throw new Exception\SandboxError("Code running in MortalSandbox is not supported");
        }
        $rawClick = $sandboxContext->rawClick();
        if (!empty($rawClick)) {
            $rawClick = $rawClick->getData();
        }
        $result = ["rawclick" => $rawClick, "get" => $sandboxContext->serverRequest()->getQueryParams(), "post" => $sandboxContext->serverRequest()->getParsedBody(), "server" => $sandboxContext->serverRequest()->getServerParams(), "cookie" => $sandboxContext->serverRequest()->getCookieParams(), "filepath" => $subject->filePath()];
        if ($subject->filePath()) {
            $result[] = $subject->filePath();
        } else {
            $result["code"] = $subject->code();
        }
        return $result;
    }
    private function _getLandingDesc(SandboxContext $sandboxContext)
    {
        $params = [];
        if (!empty($sandboxContext->campaign)) {
            $params["campaign"] = $sandboxContext->campaign->getId();
        }
        if (!empty($sandboxContext->stream)) {
            $params["campaign"] = $sandboxContext->stream->getCampaignId();
            $params["stream"] = $sandboxContext->stream->getId();
        }
        if (!empty($params)) {
            return " (" . http_build_query($params, "", "; ") . ")";
        }
        return "";
    }
    public function execute(\Traffic\Response\Response $execute, SandboxSubject $subject, SandboxContext $sandboxContext)
    {
        $command = $this->_cgiExecutor->command();
        $params = $this->_getExecParams($subject, $sandboxContext);
        $input = "params=" . urlencode(json_encode($params));
        $timeout = (int) $this->_cachedSettings->get(\Traffic\Model\Setting::LP_PHP_TIMEOUT, 1);
        $env = ["REDIRECT_STATUS" => "true", "SCRIPT_FILENAME" => ROOT . "/bin/execute_script.php", "REQUEST_METHOD" => "POST", "REMOTE_ADDR" => "127.127.127.127", "CONTENT_LENGTH" => strlen($input)];
        $process = new \Symfony\Component\Process\Process($command, NULL, $env, $input, $timeout);
        try {
            $process->run();
            $output = $process->getOutput();
            $error = $process->getErrorOutput();
        } catch (\Symfony\Component\Process\Exception\ProcessTimedOutException $e) {
            $output = "Content-type:text/plain;" . HEADERS_SEPARATOR . "Timed out";
            $error = "Exceeded timeout of " . $timeout . " seconds";
            if (!empty($error)) {
                $msg = "Error executing landing" . $this->_getLandingDesc($sandboxContext) . ": " . $error;
                \Traffic\Logging\Service\LoggerService::instance()->error($msg);
            }
            return $this->_parseOutputToResponse($output, $subject);
        } catch (\Exception $e) {
            throw $e;
        }
    }
    public static function executeInChild()
    {
        $result = [];
        parse_str(file_get_contents("php://input"), $result);
        $subjectData = $result["subjectData"];
        $contextData = $result["contextData"];
        $subjectData = base64_decode($subjectData);
        $contextData = base64_decode($contextData);
        $subject = unserialize($subjectData);
        $sandboxContext = unserialize($contextData);
        extract($sandboxContext->asHash());
        $get = $sandboxContext->serverRequest()->getQueryParams();
        $post = $sandboxContext->serverRequest()->getParsedBody();
        $cookies = $sandboxContext->serverRequest()->getCookieParams();
        $_SERVER = $sandboxContext->serverRequest()->getServerParams();
        $_GET = $get;
        $_POST = $post;
        $_COOKIE = $cookies;
        $_REQUEST = array_merge($get, $post, $cookies);
        if ($subject->filePath()) {
            include $subject->filePath();
        } else {
            eval($subject->code());
        }
    }
}

?>