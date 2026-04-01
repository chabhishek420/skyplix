<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Landings\LocalFile;

class PageWrapper
{
    public static function wrap(PageInfo $pageInfo, \Core\Sandbox\SandboxContext $pageContext)
    {
        $wrapper = new self();
        return $wrapper->wrapPage($pageInfo, $pageContext);
    }
    public function wrapPage(PageInfo $pageInfo, \Core\Sandbox\SandboxContext $pageContext)
    {
        while (empty($pageInfo)) {
            if (empty($pageContext)) {
                throw new \Exception("pageContext is not set");
            }
            $this->_validateFile($pageInfo);
            if ($this->_mustReadAsPlainText($pageInfo)) {
                \Traffic\Logging\Service\LoggerService::instance()->debug("PHP disabled, running without sandbox");
                $response = \Traffic\Response\Response::build(["body" => file_get_contents($pageInfo->indexFilePath()), "headers" => [\Traffic\Response\ContentType::HEADER => \Traffic\Response\ContentType::HTML]]);
            } else {
                $subject = \Core\Sandbox\SandboxSubject::fromFile($pageInfo->indexFilePath());
                $cgiExecutor = new \Core\Sandbox\CgiExecutor\CgiExecutor();
                $fcgiExecutor = new \Core\Sandbox\CgiExecutor\FcgiExecutor(\Traffic\Service\ConfigService::instance()->get("system", \Traffic\Service\ConfigService::SANDBOX_FCGI_PATH), \Traffic\Service\ConfigService::instance()->get("system", \Traffic\Service\ConfigService::SANDBOX_FPM_PATH));
                $sandbox = \Core\Sandbox\SandboxFactory\SandboxFactory::create(\Traffic\Service\ConfigService::instance()->get("system", \Traffic\Service\ConfigService::SANDBOX_ENGINE), $cgiExecutor, $fcgiExecutor);
                try {
                    $response = $sandbox->execute($subject, $pageContext);
                } catch (\Exception $error) {
                    if (\Core\Application\Application::instance()->isDevelopment() || \Core\Application\Application::instance()->isDebug()) {
                        throw $error;
                    }
                    \Traffic\Logging\Service\LoggerService::instance()->error("Landing: " . $pageInfo->uri() . "; Error: " . $error->getMessage());
                    $response = \Traffic\Response\Response::build(["status" => 502, "body" => $error->getMessage(), "headers" => [\Traffic\Response\ContentType::HEADER => \Traffic\Response\ContentType::HTML]]);
                }
            }
            return $this->_adaptResponseBody($response, $pageInfo, $pageContext);
        }
        throw new \Exception("pageInfo is not set");
    }
    private static function _isRRForSandbox()
    {
        return \Core\Application\Application::instance()->isRoadRunnerRunning() || \Component\System\Service\StatusService::instance()->isEngineRoadRunner();
    }
    private function _adaptResponseBody($response, PageInfo $pageInfo, \Core\Sandbox\SandboxContext $pageContext)
    {
        $adaptedBody = $this->_adaptBody($response->getBody(), $pageInfo, $pageContext);
        return $response->withBody(\Traffic\Response\ResponseFactory::safeBody($adaptedBody));
    }
    private function _validateFile(PageInfo $pageInfo)
    {
        $indexFilePath = $pageInfo->indexFilePath();
        if (!file_exists($indexFilePath)) {
            throw new Validator\IncompatibleLocalFile("LocalFile: file " . $indexFilePath . " not found");
        }
    }
    private function _adaptBody($text, PageInfo $pageInfo, \Core\Sandbox\SandboxContext $pageContext)
    {
        $text = $this->_adaptAnchors($text);
        $text = $this->_adaptBasePath($text, $pageInfo->uri(), $pageContext);
        $text = $this->_adaptResourcePaths($text);
        $text = $this->_adaptFormAction($text);
        return $this->_processMacros($text, $pageContext);
    }
    private function _mustReadAsPlainText(PageInfo $pageInfo)
    {
        if (!$pageInfo->isPhpAllowed()) {
            return true;
        }
        $exts = ["html", "htm"];
        foreach ($exts as $ext) {
            if (substr($pageInfo->indexFilePath(), -1 * strlen($ext)) == $ext) {
                return true;
            }
        }
        return false;
    }
    private function _adaptAnchors($text)
    {
        return \Traffic\Actions\CurlService::instance()->adaptAnchors($text);
    }
    private function _adaptResourcePaths($text)
    {
        return \Traffic\Actions\CurlService::instance()->adaptResourcePaths($text);
    }
    private function _adaptBasePath($text, \Psr\Http\Message\UriInterface $uri, \Core\Sandbox\SandboxContext $pageContext)
    {
        $skipHost = !$this->_isClickApiRequested($pageContext);
        return \Traffic\Actions\CurlService::instance()->addBasePath($text, $uri, $skipHost);
    }
    private function _adaptFormAction($text)
    {
        return \Traffic\Actions\CurlService::instance()->adaptFormAction($text);
    }
    private function _isClickApiRequested(\Core\Sandbox\SandboxContext $pageContext)
    {
        return (int) $pageContext->serverRequest()->getParam(\Core\Router\TrafficRouter::PARAM_VERSION);
    }
    private function _processMacros($content, \Core\Sandbox\SandboxContext $pageContext)
    {
        return \Traffic\Macros\MacrosProcessor::process($pageContext, $content);
    }
}

?>