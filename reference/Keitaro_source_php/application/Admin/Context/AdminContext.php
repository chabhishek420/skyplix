<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Admin\Context;

class AdminContext implements \Core\Context\ContextInterface
{
    const BATCH_PARAM_NAME = "batch";
    const OLD_BULK_PARAM_NAME = "bulk";
    const ERROR_SETTINGS = "settings' doesn't exist";
    public function bootstrap()
    {
        \Core\Application\Bootstrap::initAdminContext();
    }
    public function modifyRequest(\Traffic\Request\ServerRequest $serverRequest)
    {
        \Core\Application\LicenseService::instance()->setDomain($serverRequest->getUri()->getHost());
        return $serverRequest;
    }
    public function dispatcher(\Traffic\Request\ServerRequest $serverRequest)
    {
        if (!$this->_isDomainAllowed($serverRequest->getUri()->getHost())) {
            $response = \Traffic\Response\Response::build()->withStatus(\Traffic\Response\StatusCode::NOT_FOUND);
            return new \Core\Dispatcher\SimpleDispatcher($response);
        }
        $this->_switchLocale($serverRequest);
        if ($this->_isAdminPanelDisabled()) {
            $error = "Page not found";
            $response = \Traffic\Response\Response::buildHtml()->withStatus(\Traffic\Response\StatusCode::NOT_FOUND)->withBody(\Traffic\Response\ResponseFactory::safeBody($error));
            return new \Core\Dispatcher\SimpleDispatcher($response);
        }
        if ($this->_isBatchRequest($serverRequest)) {
            return new \Admin\Dispatcher\BatchAdminDispatcher();
        }
        $response = \Traffic\Response\Response::build(["disable_cache" => true]);
        return new \Admin\Dispatcher\AdminDispatcher($response);
    }
    public function shutdown()
    {
        \Core\EntityEventManager\Service\EntityEventService::instance()->emitEvents();
    }
    public function handleException(\Exception $e, \Traffic\Request\ServerRequest $serverRequest)
    {
        if ($e instanceof \Core\Application\Exception\LicenseError) {
            if ($e instanceof \Core\Validator\ValidationError) {
                if ($e instanceof \Core\Exceptions\DenyError) {
                    if ($e instanceof \Core\Application\Exception\EditionError) {
                        if ($e instanceof \Core\Exceptions\NotFoundError) {
                            if ($e instanceof \ADODB_Exception && strstr($e->getMessage(), ERROR_SETTINGS)) {
                                if (!\Component\Users\Service\CurrentUserService::instance()->exists()) {
                                    return \Traffic\Response\Response::buildJson(["status" => \Traffic\Response\StatusCode::INTERNAL_SERVER_ERROR, "body" => "Internal error, please check the log file."]);
                                }
                                $response = \Traffic\Response\Response::buildJson(["status" => \Traffic\Response\StatusCode::INTERNAL_SERVER_ERROR, "body" => ["error" => $e->getMessage(), "stacktrace" => $e->getTraceAsString()]]);
                                \Traffic\Logging\Service\LoggerService::instance()->error($e->getMessage() . ": \n" . $e->getTraceAsString());
                            } else {
                                if (!\Component\Users\Service\CurrentUserService::instance()->exists()) {
                                    return \Traffic\Response\Response::buildJson(["status" => \Traffic\Response\StatusCode::INTERNAL_SERVER_ERROR, "body" => "Database is empty."]);
                                }
                            }
                        } else {
                            $response = \Traffic\Response\Response::buildJson(["status" => \Traffic\Response\StatusCode::NOT_FOUND, "body" => ["error" => $e->getMessage(), "stacktrace" => $e->getTraceAsString()]]);
                        }
                    } else {
                        $response = \Traffic\Response\Response::buildJson(["status" => \Traffic\Response\StatusCode::PAYMENT_REQUIRED, "body" => ["error" => $e->getMessage()]]);
                    }
                } else {
                    $response = \Traffic\Response\Response::buildJson(["status" => \Traffic\Response\StatusCode::FORBIDDEN, "body" => ["error" => $e->getMessage()]]);
                }
            } else {
                $response = \Traffic\Response\Response::buildJson(["status" => \Traffic\Response\StatusCode::NOT_ACCEPTABLE, "body" => $e->getErrors()]);
            }
        } else {
            $response = \Component\CommonErrorHandler\CommonErrorHandler::handleLicenseError($e, $serverRequest);
        }
        return $response;
    }
    private function _isBatchRequest(\Traffic\Request\ServerRequest $request)
    {
        return $request->hasParam(BATCH_PARAM_NAME) || $request->hasParam(OLD_BULK_PARAM_NAME);
    }
    private function _switchLocale(\Traffic\Request\ServerRequest $request)
    {
        if ($user = \Component\Users\Service\AuthService::instance()->loadFromCookieToken($request)) {
            \Core\Locale\LocaleService::instance()->setLanguage(\Component\Users\Repository\UserPreferenceRepository::instance()->get($user, \Component\Users\Model\UserPreference::LANGUAGE));
        }
    }
    private function _isAdminPanelDisabled()
    {
        return \Core\Application\Application::instance()->isSlaveModeEnabled();
    }
    private function _isDomainAllowed($_isDomainAllowed, $domain)
    {
        $domainId = \Traffic\Repository\CachedSettingsRepository::instance()->get(\Traffic\Model\Setting::ADMIN_ALLOWED_DOMAINS);
        if (!is_numeric($domainId)) {
            return true;
        }
        $domainName = \Component\Domains\Repository\DomainsRepository::instance()->find((int) $domainId)->get("name");
        $allowDomains = ["localhost", $domainName];
        return in_array($domain, $allowDomains, true);
    }
}

?>