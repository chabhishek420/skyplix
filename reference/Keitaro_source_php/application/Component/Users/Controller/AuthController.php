<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Controller;

class AuthController extends \Admin\Controller\BaseController
{
    public function indexAction()
    {
        $this->header(\Traffic\Response\ContentType::HEADER, \Traffic\Response\ContentType::HTML);
        return $this->renderView(ROOT . "/application/layouts/login.phtml", ["title" => $this->t("sign_in.title"), "login" => $this->getParam("login"), "password" => $this->getParam("password"), "translations" => ["sign_in" => \Core\Locale\LocaleService::t("sign_in")]]);
    }
    public function loginAction()
    {
        \Component\Users\Service\BruteForceDetectionService::instance()->prune();
        $userIp = $this->getServerRequest()->getServerParam(\Traffic\Request\ServerRequest::REMOTE_ADDR);
        if (\Component\Users\Service\BruteForceDetectionService::instance()->isBlocked($userIp)) {
            return ["message" => $this->t("sign_in_errors.authorization_blocked", \Component\Users\Service\BruteForceDetectionService::instance()->getBlockExpiresAt($userIp))];
        }
        $login = $this->getPostParam("login");
        $password = $this->getPostParam("password");
        if (empty($login)) {
            return ["message" => $this->t("sign_in_errors.enter_login")];
        }
        if (empty($password)) {
            return ["message" => $this->t("sign_in_errors.enter_password")];
        }
        try {
            $user = \Component\Users\Service\AuthService::instance()->findUserByLoginAndPassword($login, $password, \Core\Application\FeatureService::instance()->hasUsersFeature());
            if (empty($user)) {
                \Component\Users\Service\BruteForceDetectionService::instance()->increaseFailCount($userIp);
                return ["message" => $this->t("sign_in_errors.incorrect_password")];
            }
            list($serverRequest, $response) = \Component\Users\Service\AuthService::instance()->storeSession($this->getServerRequest(), $this->getResponse(), $user);
            $this->setServerRequest($serverRequest);
            $this->setResponse($response);
            return ["success" => true];
        } catch (\ADODB_Exception $e) {
            if (stristr($e->getMessage(), "TokuDB")) {
                return ["message" => $this->t("sign_in_errors.tokudb")];
            }
            if (stristr($e->getMessage(), "Disk full")) {
                return ["message" => $this->t("sign_in_errors.disk_full")];
            }
            throw $e;
        } catch (\Core\Application\Exception\EditionError $e) {
            return ["message" => $this->t("sign_in_errors.not_pro")];
        }
    }
    public function logoutAction()
    {
        list($serverRequest, $response) = \Component\Users\Service\AuthService::instance()->clearCookieToken($this->getServerRequest(), $this->getResponse());
        $this->setServerRequest($serverRequest);
        $this->setResponse($response);
        $this->redirect("?return=" . $this->getServerRequest()->getParam("return"));
    }
}

?>