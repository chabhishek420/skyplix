<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Admin\AdminRequest;

class AdminRequestFactory
{
    const CONTROLLER_PARAM = "controller";
    const ACTION_PARAM = "action";
    public static function build(\Traffic\Request\ServerRequest $request)
    {
        $factory = new AdminRequestFactory();
        $adminRequest = $factory->buildAdminRequest($request);
        $factory->checkDefaultController($adminRequest);
        $factory->checkAuthorization($adminRequest);
        return $adminRequest;
    }
    public function buildAdminRequest(\Traffic\Request\ServerRequest $request)
    {
        $adminRequest = new AdminRequest();
        if ($request->getParam("object")) {
            $parts = explode(".", $request->getParam("object"));
            $adminRequest->setController($parts[0]);
            $adminRequest->setAction(isset($parts[1]) ? $parts[1] : AdminRequest::INDEX_ACTION);
        } else {
            $adminRequest->setController($request->getParam(CONTROLLER_PARAM));
            $adminRequest->setAction($request->getParam(ACTION_PARAM));
        }
        return $adminRequest;
    }
    public function checkDefaultController(AdminRequest $adminRequest)
    {
        if (!$adminRequest->getController()) {
            $adminRequest->setController(AdminRequest::HOME_CONTROLLER)->setAction(AdminRequest::INDEX_ACTION);
        }
    }
    public function checkAuthorization(AdminRequest $adminRequest)
    {
        if ($adminRequest->isAuthController() || $adminRequest->isGuestAllowedActions()) {
            return NULL;
        }
        $user = \Component\Users\Service\CurrentUserService::instance()->get();
        if (empty($user) || !$user->isAdmin() && \Core\Application\FeatureService::instance()->isBasic()) {
            $adminRequest->setController(AdminRequest::AUTH_CONTROLLER)->setAction(AdminRequest::INDEX_ACTION);
        } else {
            if (!\Component\Users\Service\AclService::instance()->isResourceAllowed($user, $adminRequest->getController())) {
                $accessDeniedMessage = \Core\Locale\LocaleService::t("exceptions.access_denied") . " - " . mb_ucfirst($adminRequest->getController());
                throw new \Core\Exceptions\DenyError($accessDeniedMessage);
            }
        }
    }
}

?>